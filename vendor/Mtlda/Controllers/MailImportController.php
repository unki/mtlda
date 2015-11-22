<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015>  <Andreas Unterkircher>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 */

namespace Mtlda\Controllers;

class MailImportController extends DefaultController
{
    private $mail_cfg;
    private $connect_string;

    private $imap_session;
    private $is_conected = false;

    public function __construct()
    {
        global $config;

        if (!$config->isMailImportEnabled()) {
            $this->raiseError("Mail import isn't enabled in Mtlda configuration!", true);
            return false;
        }

        if (!$this->mail_cfg = $config->getMailImportConfiguration()) {
            $this->raiseError("Unable to retrieve mail import configuration!", true);
            return false;
        }

        if (empty($this->mail_cfg) || !is_array($this->mail_cfg)) {
            $this->raiseError("Invalid mail import configuration retrieved!", true);
            return false;
        }

        if (!isset($this->mail_cfg['mbox_server']) || empty($this->mail_cfg['mbox_server']) ||
            !isset($this->mail_cfg['mbox_username']) || empty($this->mail_cfg['mbox_username']) ||
            !isset($this->mail_cfg['mbox_password']) || empty($this->mail_cfg['mbox_password']) ||
            !isset($this->mail_cfg['mbox_type']) || empty($this->mail_cfg['mbox_type'])
        ) {
            $this->raiseError("Incomplete mail import configuration found!", true);
            return false;
        }

        if (!in_array(strtolower($this->mail_cfg['mbox_type']), array('imap', 'pop3'))) {
            $this->raiseError("Mailbox type {$this->mail_cfg['mbox_type']} is not supported!", true);
            return false;
        }
    }

    public function __destruct()
    {
        if (!$this->isConnected()) {
            return true;
        }

        $this->disconnect();
        return true;
    }

    public function fetch()
    {
        global $config, $mbus;

        if (!$mbus->sendMessageToClient('mailimport-reply', 'Establishing connection to mailbox.', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$this->connect()) {
            $this->raiseError(__CLASS__ .'::connect() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('mailimport-reply', 'Checking mailbox for new E-mails.', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (($msg_cnt = $this->checkForMails()) === false) {
            $this->raiseError(__CLASS__ .'::checkForMails() returned false!');
        }

        if (!($msg_cnt > 0)) {
            return true;
        }

        if (!$list = $this->retrieveListOfMails($msg_cnt)) {
            $this->raiseError(__CLASS__ .'::retrieveListOfMails() returned false!');
        }

        // if no mails are pending in the mailbox
        if (!(count($list) >= 1)) {
            return true;
        }

        // calculate percentage for status message
        $percentage_step = round((90-20)/$msg_cnt);

        foreach ($list as $id => $mail) {
            $mail_no = $id+1;

            if (!isset($mail->message_id) || empty($mail->message_id) ||
                !isset($mail->msgno) || empty($mail->msgno)
            ) {
                $this->raiseError("Fetched mail is incomplete!");
                break;
            }

            if (!$mbus->sendMessageToClient(
                'mailimport-reply',
                "Retrieving E-mail {$mail_no} of {$msg_cnt}.",
                (20+($percentage_step*$id)) .'%'
            )) {
                $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }

            if (!$msg = $this->retrieveMail($mail->msgno)) {
                $this->raiseError(__CLASS__ .'::retrieveMail() returned false!');
                break;
            }

            if (!$mbus->sendMessageToClient(
                'mailimport-reply',
                "Extracting documents from E-mail {$mail_no} of {$msg_cnt}.",
                (20+($percentage_step*$id)) .'%'
            )) {
                $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }

            if (!$this->parseMail($msg)) {
                $this->raiseError(__CLASS__ .'::parseMail() returned false!');
                break;
            }

            if (!$this->flagMailSeen($mail->msgno)) {
                $this->raiseError(__CLASS__ .':flagMailSeen() returned false!');
                return false;
            }

            if ($config->getMailImportMailDestinyIsDelete()) {
                if (!$this->deleteMail($mail->msgno)) {
                    $this->raiseError(__CLASS__ .'::deleteMail() returned false!');
                    return false;
                }
            }
        }

        if (!$this->disconnect()) {
            $this->raiseError(__CLASS__ .'::disconnect() returned false!');
            return false;
        }

        // now launch the import controller
        try {
            $import = new ImportController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load ImportController!");
            return false;
        }

        $state = $mbus->suppressOutboundMessaging(true);
        if (!$import->handleQueue()) {
            $this->raiseError("ImportController::handleQueue() returned false!");
            return false;
        }
        $mbus->suppressOutboundMessaging($state);

        unset($import);
        return true;
    }

    private function connect()
    {
        if (strtolower($this->mail_cfg['mbox_type']) == 'imap') {
            $this->connect_string = "{{$this->mail_cfg['mbox_server']}:143}INBOX";
        } elseif (strtolower($this->mail_cfg['mbox_type']) == 'pop3') {
            $this->connect_string = "{{$this->mail_cfg['mbox_server']}:110/pop3}INBOX";
        }

        if (!isset($this->connect_string)) {
            $this->raiseError("Do not know how to connect!");
            return false;
        }

        $this->imap_session = imap_open(
            $this->connect_string,
            $this->mail_cfg['mbox_username'],
            $this->mail_cfg['mbox_password'],
            OP_SILENT, /* to avoid the Mailbox-is-empty notice message */
            3,
            array('DISABLE_AUTHENTICATOR' => 'GSSAPI')
        );

        if ($this->imap_session === false) {
            $this->raiseError("Unable to connect!<br />". imap_last_error());
            return false;
        }

        if (!is_resource($this->imap_session)) {
            $this->raiseError("What ever has been opened, it is not a resource!");
            return false;
        }

        $this->setConnected();
        return true;
    }

    private function disconnect()
    {
        global $config;

        if (!$this->isConnected()) {
            return true;
        }

        // for POP3 we are going to actually delete messages,
        // for IMAP it is good enough for us to have them marked
        // deleted without expunging them.
        if (strtolower($this->mail_cfg['mbox_type']) == 'pop3' ||
            ( strtolower($this->mail_cfg['mbox_type']) == 'imap' &&
                $config->getMailImportImapMailboxExpunge()
            )
        ) {
            if (!imap_expunge($this->imap_session)) {
                $this->raiseError("imap_expunge() returned false!". imap_last_error());
                return false;
            }
        }

        if (!imap_close($this->imap_session)) {
            $this->raiseError("imap_close() failed!". imap_last_error());
            return false;
        }

        $this->setDisconnected();
        return true;
    }

    private function checkForMails()
    {
        if (!$this->isConnected()) {
            $this->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!$status = imap_status($this->imap_session, $this->connect_string, SA_UNSEEN)) {
            $this->raiseError("imap_status() failed!". imap_last_error());
            return false;
        }

        if (!isset($status->flags) || $status->flags != SA_UNSEEN) {
            $this->raiseError("Server responded with something we have not requested!");
            return false;
        }

        if (!isset($status->unseen) || !is_numeric($status->unseen)) {
            $this->raiseError("Server responded invalid!");
            return false;
        }

        return $status->unseen;
    }

    private function retrieveListOfMails($msg_cnt)
    {
        if (!$this->isConnected()) {
            $this->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!($msg_cnt >= 1)) {
            return false;
        }

        if (!$list = imap_fetch_overview($this->imap_session, "1:{$msg_cnt}")) {
            $this->raiseError("imap_fetch_overview() failed!");
            return false;
        }

        if (!is_array($list)) {
            $this->raiseError("imap_fetch_overview() returned no array!". imap_last_error());
            return false;
        }

        return $list;
    }

    private function retrieveMail($msgno)
    {
        if (!$this->isConnected()) {
            $this->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!($msgno >= 1)) {
            return false;
        }

        if (!$structure = imap_fetchstructure($this->imap_session, $msgno)) {
            $this->raiseError("Unable to retrieve structure!". imap_last_error());
            return false;
        }

        if (empty($structure) || !is_object($structure)) {
            $this->raiseError("imap_fetchstructure() hasn't returned an object!". imap_last_error());
            return false;
        }

        $mail = array(
            'msgno' => $msgno,
            'structure' => $structure
        );

        return $mail;
    }

    private function parseMail(&$msg)
    {
        global $config;

        if (!is_array($msg)) {
            $this->raiseError(__METHOD__ .'(), first parameter should be an array!');
            return false;
        }

        $structure = $msg['structure'];
        $msgno = $msg['msgno'];

        // if mail does not contain MIME parts, we will not find any attachment in it.
        if (!isset($structure->parts) || empty($structure->parts)) {
            return true;
        }

        $attachments = array();
        $descriptions = array();

        foreach ($structure->parts as $id => $part) {
            // the actual MIME part id is +1 then the array key
            $id+=1;

            if ($part->type == 0 && $config->isUseEmailBodyAsDescription()) {
                if (!$this->parseMimeText($part, $id, $descriptions)) {
                    $this->raiseError(__METHOD__ .'(), parseMimeText() returned false!');
                    return false;
                }
                continue;
            }

            if (!$this->parseMimePart($part, $id, $attachments, $descriptions)) {
                $this->raiseError(__CLASS__ .'::parseMimePart() returned false!');
                return false;
            }
        }

        if (empty($attachments) && empty($descriptions)) {
            return true;
        }

        $description = array();

        if (!empty($descriptions)) {
            if (($description = $this->fetchDescriptions($msgno, $descriptions)) === false) {
                $this->raiseError(__METHOD__ .'(), fetchDescriptions() returned false!');
                return false;
            }
        }

        if (!empty($attachments)) {
            if (!$this->fetchAttachments($msgno, $attachments, $description)) {
                $this->raiseError(__METHOD__ .'(), fetchAttachments() returned false!');
                return false;
            }
        }

        return true;
    }

    private function parseMimePart(&$part, $id, &$attachments, &$descriptions)
    {
        global $config;

        if (!is_object($part)) {
            $this->raiseError(__METHOD__ .'(), first parameter should be an object!');
            return false;
        }

        if (!is_array($attachments)) {
            $this->raiseError(__METHOD__ .'(), third parameter should be an array!');
            return false;
        }

        if (!is_array($descriptions)) {
            $this->raiseError(__METHOD__ .'(), forth parameter should be an array!');
            return false;
        }

        // if we have parts nested in parts, we have to run through recursive
        if (isset($part->parts)) {
            foreach ($part->parts as $subid => $subpart) {
                // the actual MIME part id is +1 then the array key
                $subid+=1;

                if ($config->isUseEmailBodyAsDescription() &&
                    $part->type == 0
                ) {
                    if (!$this->parseMimeText($part, $subid, $descriptions)) {
                        $this->raiseError(__METHOD__ .'(), parseMimeText() returned false!');
                        return false;
                    }
                    continue;
                }

                if (isset($part->ifsubtype) &&
                    $part->ifsubtype &&
                    isset($part->subtype) &&
                    strtoupper($part->subtype) == 'RFC822'
                ) {
                    $nextid = $id;
                } else {
                    $nextid = $id .'.'. $subid;
                }

                if (!$this->parseMimePart($subpart, $nextid, $attachments, $descriptions)) {
                    $this->raiseError(__CLASS__ .'::parseMimePart() returned false!');
                    return false;
                }
            }
            return true;
        }

        $parameters = array();

        if (isset($part->ifdparameters) &&
            !empty($part->ifdparameters) &&
            isset($part->dparameters) &&
            !empty($part->dparameters)
        ) {
            $this->arrayAppend($parameters, $part->dparameters);
        }

        if (isset($part->ifparameters) &&
            !empty($part->ifparameters) &&
            isset($part->parameters) &&
            !empty($part->parameters)
        ) {
            $this->arrayAppend($parameters, $part->parameters);
        }

        // no parameters? then we are done.
        if (!isset($parameters) || empty($parameters)) {
            return true;
        }

        if (!$this->parseMimePartParameters($parameters, $attachments, $part->encoding, $id)) {
            $this->raiseError(__CLASS__ .'::parseMimePartParameters() returned false!');
            return false;
        }

        return true;
    }

    private function parseMimePartParameters(&$partparam, &$attachments, $encoding, $id)
    {
        if (!is_array($partparam)) {
            $this->raiseError(__METHOD__ .'(), first parameter has to be an array!');
            return false;
        }

        if (!is_array($attachments)) {
            $this->raiseError(__METHOD__ .'(), second parameter has to be an array!');
            return false;
        }

        if (!is_numeric($encoding)) {
            $this->raiseError(__METHOD__ .'(), third parameter has to be a number!');
            return false;
        }

        $info = array();

        foreach ($partparam as $param) {
            if (!isset($param->attribute) ||
                empty($param->attribute) ||
                !is_string($param->attribute)
            ) {
                $this->raiseError(__METHOD__ .'(), attribute property is not set!');
                return false;
            }

            if (!isset($param->value) ||
                empty($param->value) ||
                !is_string($param->value)
            ) {
                $this->raiseError(__METHOD__ .'(), value property is not set!');
                return false;
            }

            $attribute = strtoupper($param->attribute);

            if ($attribute != 'NAME' &&
                !preg_match('/^FILENAME([\*].*)/', $attribute)) {
                continue;
            }

            $filename = $param->value;

            // the value may be specially encoded.
            if (substr($attribute, -1) == '*' || $attribute == 'NAME') {
                // strip of the asterisk from the attributes name
                if ($attribute == 'FILENAME*') {
                    $attribute = substr($attribute, 0, -1);
                }

                if (!($filename_decoded = $this->imapDecodeString($filename))) {
                    $this->raiseError(__METHOD__ .'(), imapDecodeString() returned false!');
                    return false;
                }

                if (!isset($filename_decoded) ||
                    empty($filename_decoded) ||
                    !is_object($filename_decoded) ||
                    !isset($filename_decoded->text) ||
                    empty($filename_decoded->text) ||
                    !isset($filename_decoded->charset) ||
                    empty($filename_decoded->charset)
                ) {
                    $this->raiseError(__METHOD__ .'(), imapDecodeString() returned an invalid object!');
                    return false;
                }

                $filename = $filename_decoded->text;
            }

            // just to be sure...
            if (!isset($filename) || empty(basename($filename))) {
                $this->raiseError(__METHOD__ .'(), should get here only if decoding went wrong!');
                return false;
            }

            if (isset($info[$attribute])) {
                $this->raiseError(__METHOD__ ."(), strangly \$info[{$attribute}] is already set!");
                return false;
            }

            $info[$attribute] = $filename;
        }

        // if we were unable locating a filename, we skip this mime part
        if (!isset($info) ||
            empty($info) ||
            (
                (!isset($info['NAME']) || empty($info['NAME'])) &&
                (!isset($info['FILENAME']) || empty($info['FILENAME']))
            )
        ) {
            return true;
        }

        // we select NAME over FILENAME attributes
        if (isset($info['NAME']) && !empty($info['NAME'])) {
            $filename = $info['NAME'];
        } elseif (isset($info['FILENAME']) && !empty($info['FILENAME'])) {
            $filename = $info['FILENAME'];
        } else {
            $this->raiseError(__METHOD__ .'(), what shall I do without knowning the filename!');
            return false;
        }

        array_push($attachments, array(
            'id' => $id,
            'filename' => basename($filename),
            'encoding' => $encoding
        ));

        return true;
    }

    private function fetchMimePartBody($msgno, $attachment)
    {
        if (!$this->isConnected()) {
            $this->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!is_numeric($msgno)) {
            $this->raiseError(__METHOD__ .'(), first parameter should be an array!');
            return false;
        }

        if (!is_array($attachment)) {
            $this->raiseError(__METHOD__ .'(), second parameter should be an array!');
            return false;
        }

        if (!$body = imap_fetchbody($this->imap_session, $msgno, $attachment['id'])) {
            $this->raiseError(
                "imap_fetchbody() returned false!<br />Msgno: {$msgno}<br />".
                "Attachment: {$attachment['id']}<br />". imap_last_error()
            );
            return false;
        }

        if (empty($body)) {
            $this->raiseError('imap_fetchbody() returned no valid content!');
            return false;
        }

        // 3 = BASE64
        if ($attachment['encoding'] == 3) {
            if (!$body = base64_decode($body)) {
                $this->raiseError("base64_decode() returned false!");
                return false;
            }
            return $body;
        // 4 = QUOTED-PRINTABLE
        } elseif ($attachment['encoding'] == 4) {
            return quoted_printable_decode($body);
        }

        $this->raiseError("Unsupported encoding: {$attachment['encoding']}!");
        return false;
    }

    private function fetchMimeTextBody($msgno, $description)
    {
        if (!$this->isConnected()) {
            $this->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!is_numeric($msgno)) {
            $this->raiseError(__METHOD__ .'(), first parameter should be an array!');
            return false;
        }

        if (!is_array($description)) {
            $this->raiseError(__METHOD__ .'(), second parameter should be an array!');
            return false;
        }

        if (!$body = imap_fetchbody($this->imap_session, $msgno, $description['id'])) {
            $this->raiseError(
                "imap_fetchbody() returned false!<br />Msgno: {$msgno}<br />".
                "Attachment: {$description['id']}<br />". imap_last_error()
            );
            return false;
        }

        if (empty($body)) {
            $this->raiseError('imap_fetchbody() returned no valid content!');
            return false;
        }

        // 3 = BASE64
        if ($description['encoding'] == 3) {
            if (!$body = base64_decode($body)) {
                $this->raiseError("base64_decode() returned false!");
                return false;
            }
        // 4 = QUOTED-PRINTABLE
        } elseif ($description['encoding'] == 4) {
            $body = quoted_printable_decode($body);
        }

        if (strtolower($description['charset']) == 'utf-8' ||
            strtolower($description['charset']) == 'utf8'
        ) {
            return $body;
        }

        try {
            $body = mb_convert_encoding($body, 'UTF-8', $description['charset']);
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), mb_convert_encoding() raised an error!', false, $e);
            return false;
        }

        if (empty($body)) {
            $this->raiseError(__METHOD__ .'(), mb_convert_encoding() was unsuccessful on recording body to UTF-8!');
            return false;
        }

        return $body;
    }

    private function isConnected()
    {
        if (!isset($this->is_connected) || empty($this->is_connected)) {
            return false;
        }

        if (!is_resource($this->imap_session)) {
            return false;
        }

        return true;
    }

    private function setConnected()
    {
        $this->is_connected = true;
    }

    private function setDisconnected()
    {
        $this->is_connected = false;
    }

    private function deleteMail($msgno)
    {
        global $config;

        if (!$this->isConnected()) {
            $this->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!imap_delete($this->imap_session, $msgno)) {
            $this->raiseError("imap_delete() returned false!". imap_last_error());
            return false;
        }

        if (strtolower($this->mail_cfg['mbox_type']) == 'imap') {
            if ($config->getMailImportImapMailboxExpunge()) {
                if (!imap_expunge($this->imap_session)) {
                    $this->raiseError("imap_expunge() returned false!". imap_last_error());
                    return false;
                }
            }
        }

        return true;
    }

    private function flagMailSeen($msgno)
    {
        if (!$this->isConnected()) {
            $this->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!imap_setflag_full($this->imap_session, $msgno, '\Seen')) {
            $this->raiseError("imap_setflag_full() returned false!". imap_last_error());
            return false;
        }

        return true;
    }

    private function imapDecodeString($value)
    {
        if (!isset($value) ||
            empty($value) ||
            !is_string($value)
        ) {
            $this->raiseError(__METHOD__ .'(), \$value is invalid!');
            return false;
        }

        $value_decoded = imap_mime_header_decode($value);

        if (!isset($value_decoded) ||
            !is_array($value_decoded) ||
            empty($value_decoded) ||
            !isset($value_decoded[0]) ||
            empty($value_decoded[0])
        ) {
            $this->raiseError('imap_mime_header_decode() has responded invalid!');
            return false;
        }

        return $value_decoded[0];
    }

    private function arrayAppend(&$dst_parameters, &$src_parameters)
    {
        if (!isset($dst_parameters) ||
            !is_array($dst_parameters)
        ) {
            $this->raiseError(__METHOD__ .'(), \$dst_parameters is invalid!');
            return false;
        }

        if (!isset($src_parameters) ||
            !is_array($src_parameters)
        ) {
            $this->raiseError(__METHOD__ .'(), \$src_parameters is invalid!');
            return false;
        }

        if (empty($src_parameters)) {
            return true;
        }

        foreach ($src_parameters as $parameter) {
            $dst_parameters[] = $parameter;
        }

        return true;
    }

    private function parseMimeText(&$part, $id, &$descriptions)
    {
        if (!is_object($part)) {
            $this->raiseError(__METHOD__ .'(), first parameter should be an object!');
            return false;
        }

        if (!is_array($descriptions)) {
            $this->raiseError(__METHOD__ .'(), third parameter should be an object!');
            return false;
        }

        $parameters = array();

        if (isset($part->ifdparameters) &&
            !empty($part->ifdparameters) &&
            isset($part->dparameters) &&
            !empty($part->dparameters)
        ) {
            $this->arrayAppend($parameters, $part->dparameters);
        }

        if (isset($part->ifparameters) &&
            !empty($part->ifparameters) &&
            isset($part->parameters) &&
            !empty($part->parameters)
        ) {
            $this->arrayAppend($parameters, $part->parameters);
        }

        // no parameters? then we are done.
        if (!isset($parameters) || empty($parameters)) {
            return true;
        }

        if (!$this->parseMimeTextParameters($parameters, $descriptions, $part->encoding, $id)) {
            $this->raiseError(__CLASS__ .'::parseMimePartParameters() returned false!');
            return false;
        }

        return true;
    }

    private function parseMimeTextParameters(&$partparam, &$descriptions, $encoding, $id)
    {
        if (!is_array($partparam)) {
            $this->raiseError(__METHOD__ .'(), first parameter has to be an array!');
            return false;
        }

        if (!is_array($descriptions)) {
            $this->raiseError(__METHOD__ .'(), second parameter has to be an array!');
            return false;
        }

        if (!is_numeric($encoding)) {
            $this->raiseError(__METHOD__ .'(), third parameter has to be a number!');
            return false;
        }

        $info = array();

        foreach ($partparam as $param) {
            if (!isset($param->attribute) ||
                empty($param->attribute) ||
                !is_string($param->attribute)
            ) {
                $this->raiseError(__METHOD__ .'(), attribute property is not set!');
                return false;
            }

            if (!isset($param->value) ||
                empty($param->value) ||
                !is_string($param->value)
            ) {
                $this->raiseError(__METHOD__ .'(), value property is not set!');
                return false;
            }

            $attribute = strtoupper($param->attribute);

            if ($attribute != 'CHARSET') {
                continue;
            }

            $info[$attribute] = $param->value;
        }

        // if we were unable locating a charset, we skip this mime part
        if (!isset($info) ||
            empty($info) ||
            (!isset($info['CHARSET']) || empty($info['CHARSET']))
        ) {
            return true;
        }

        array_push($descriptions, array(
            'id' => $id,
            'charset' => $info['CHARSET'],
            'encoding' => $encoding,
        ));

        return true;
    }

    protected function fetchAttachments(&$msgno, &$attachments, $description)
    {
        foreach ($attachments as $attachment) {
            if (!isset($attachment['filename']) || empty($attachment['filename'])) {
                $this->raiseError("Something is wrong. No filename is known for this mime part!");
                return false;
            }

            $filename = $attachment['filename'];

            // if file is not suffixed by a .pdf, we skip it
            if (!preg_match('/\.pdf$/i', $filename)) {
                continue;
            }

            if (($attachment_body = $this->fetchMimePartBody($msgno, $attachment)) === false) {
                $this->raiseError(__CLASS__ .'::fetchMimePartBody() returned false!');
                return false;
            }

            if (empty($attachment_body)) {
                $this->raiseError("No body fetched for mime part!");
                return false;
            }

            $indir_ready = false;

            do {
                $destdir = self::INCOMING_DIRECTORY .'/'. uniqid();

                if (file_exists($destdir)) {
                    continue;
                }

                if (!mkdir($destdir, 0700)) {
                    continue;
                }
            } while (!file_exists($destdir));

            $dest = $destdir .'/'. $filename;

            // if $dest is already present, we add a bit random to the filename
            if (file_exists($dest)) {
                if (preg_match('/([[:print:]]+)\.([[:print:]]+)/', $filename, $matches)) {
                    $filename = "{$matches[1]}-". uniqid() .".{$matches[2]}";
                } else {
                    $filename = $filename .'-'. uniqid();
                }
                $dest = self::INCOMING_DIRECTORY .'/'. $filename;
            }

            if (file_exists($dest)) {
                $this->raiseError(
                    "A file with the name {$file['name']} is already present in the incoming directory!"
                );
                return false;
            }

            if (!file_put_contents($dest, $attachment_body)) {
                $this->raiseError(__METHOD__ .'(), file_put_contents() failed on saving attachment!');
                return false;
            }

            if (empty($description) || !is_array($description)) {
                continue;
            }

            $text = implode($description);
            if (!($dest = preg_replace('/\.pdf$/i', '.dsc', $dest))) {
                $this->raiseError(__METHOD__ .'(), preg_replace() failed!');
                return false;
            }
            if (!file_put_contents($dest, $text)) {
                $this->raiseError(__METHOD__ .'(), file_put_contents() failed on saving description!');
                return false;
            }
        }
        return true;
    }

    protected function fetchDescriptions(&$msgno, &$descriptions)
    {
        $body = array();

        foreach ($descriptions as $description) {
            if (!isset($description['charset']) || empty($description['charset'])) {
                $this->raiseError(__METHOD__ .'(), something is wrong. No charset is known for this mime part!');
                return false;
            }

            if (($description_body = $this->fetchMimeTextBody($msgno, $description)) === false) {
                $this->raiseError(__CLASS__ .'::fetchMimeTextBody() returned false!');
                return false;
            }

            if (empty($description_body)) {
                $this->raiseError(__METHOD__ .'(), no body fetched for mime part!');
                return false;
            }

            array_push($body, $description_body);
        }

        return $body;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
