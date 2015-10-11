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

namespace MTLDA\Controllers;

class MailImportController extends DefaultController
{
    private $mail_cfg;
    private $connect_string;

    private $imap_session;
    private $is_conected = false;

    public function __construct()
    {
        global $mtlda, $config;

        if (!$config->isMailImportEnabled()) {
            $mtlda->raiseError("Mail import isn't enabled in MTLDA configuration!", true);
            return false;
        }

        if (!$this->mail_cfg = $config->getMailImportConfiguration()) {
            $mtlda->raiseError("Unable to retrieve mail import configuration!", true);
            return false;
        }

        if (empty($this->mail_cfg) || !is_array($this->mail_cfg)) {
            $mtlda->raiseError("Invalid mail import configuration retrieved!", true);
            return false;
        }

        if (
            !isset($this->mail_cfg['mbox_server']) || empty($this->mail_cfg['mbox_server']) ||
            !isset($this->mail_cfg['mbox_username']) || empty($this->mail_cfg['mbox_username']) ||
            !isset($this->mail_cfg['mbox_password']) || empty($this->mail_cfg['mbox_password']) ||
            !isset($this->mail_cfg['mbox_type']) || empty($this->mail_cfg['mbox_type'])
        ) {
            $mtlda->raiseError("Incomplete mail import configuration found!", true);
            return false;
        }

        if (!in_array(strtolower($this->mail_cfg['mbox_type']), array('imap', 'pop3'))) {
            $mtlda->raiseError("Mailbox type {$this->mail_cfg['mbox_type']} is not supported!", true);
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
        global $mtlda, $config, $mbus;

        if (!$mbus->sendMessageToClient('mailimport-reply', 'Establishing connection to mailbox.', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$this->connect()) {
            $mtlda->raiseError(__CLASS__ .'::connect() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('mailimport-reply', 'Checking mailbox for new E-mails.', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }


        if (($msg_cnt = $this->checkForMails()) === false) {
            $mtlda->raiseError(__CLASS__ .'::checkForMails() returned false!');
        }

        if (!($msg_cnt > 0)) {
            return true;
        }

        if (!$list = $this->retrieveListOfMails($msg_cnt)) {
            $mtlda->raiseError(__CLASS__ .'::retrieveListOfMails() returned false!');
        }

        // if no mails are pending in the mailbox
        if (!(count($list) >= 1)) {
            return true;
        }

        // calculate percentage for status message
        $percentage_step = round((90-20)/$msg_cnt);

        foreach ($list as $id => $mail) {

            $mail_no = $id+1;

            if (
                !isset($mail->message_id) || empty($mail->message_id) ||
                !isset($mail->msgno) || empty($mail->msgno)
            ) {
                $mtlda->raiseError("Fetched mail is incomplete!");
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
                $mtlda->raiseError(__CLASS__ .'::retrieveMail() returned false!');
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
                $mtlda->raiseError(__CLASS__ .'::parseMail() returned false!');
                break;
            }

            if ($config->getMailImportMailDestinyIsDelete()) {
                if (!$this->deleteMail($mail->msgno)) {
                    $mtlda->raiseError(__CLASS__ .'::deleteMail() returned false!');
                    return false;
                }
                continue;
            }

            if (!$this->flagMailSeen($mail->msgno)) {
                $mtlda->raiseError(__CLASS__ .':flagMailSeen() returned false!');
                return false;
            }
        }

        if (!$this->disconnect()) {
            $mtlda->raiseError(__CLASS__ .'::disconnect() returned false!');
            return false;
        }

        // now launch the import controller
        try {
            $import = new ImportController;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load ImportController!");
            return false;
        }

        if (!$import->handleQueue()) {
            $mtlda->raiseError("ImportController::handleQueue() returned false!");
            return false;
        }

        unset($import);
        return true;
    }

    private function connect()
    {
        global $mtlda;

        if (strtolower($this->mail_cfg['mbox_type']) == 'imap') {
            $this->connect_string = "{{$this->mail_cfg['mbox_server']}:143}INBOX";
        } elseif (strtolower($this->mail_cfg['mbox_type']) == 'pop3') {
            $this->connect_string = "{{$this->mail_cfg['mbox_server']}:110/pop3}INBOX";
        }

        if (!isset($this->connect_string)) {
            $mtlda->raiseError("Do not know how to connect!");
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
            $mtlda->raiseError("Unable to connect!<br />". imap_last_error());
            return false;
        }

        if (!is_resource($this->imap_session)) {
            $mtlda->raiseError("What ever has been opened, it is not a resource!");
            return false;
        }

        $this->setConnected();
        return true;
    }

    private function disconnect()
    {
        global $mtlda, $config;

        if (!$this->isConnected()) {
            return true;
        }

        // for POP3 we are going to actually delete messages,
        // for IMAP it is good enough for us to have them marked
        // deleted without expunging them.
        if (
            strtolower($this->mail_cfg['mbox_type']) == 'pop3' ||
            ( strtolower($this->mail_cfg['mbox_type']) == 'imap' &&
                $config->getMailImportImapMailboxExpunge()
            )
        ) {
            if (!imap_expunge($this->imap_session)) {
                $mtlda->raiseError("imap_expunge() returned false!". imap_last_error());
                return false;
            }
        }

        if (!imap_close($this->imap_session)) {
            $mtlda->raiseError("imap_close() failed!". imap_last_error());
            return false;
        }

        $this->setDisconnected();
        return true;
    }

    private function checkForMails()
    {
        global $mtlda;

        if (!$this->isConnected()) {
            $mtlda->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!$status = imap_status($this->imap_session, $this->connect_string, SA_UNSEEN)) {
            $mtlda->raiseError("imap_status() failed!". imap_last_error());
            return false;
        }

        if (!isset($status->flags) || $status->flags != SA_UNSEEN) {
            $mtlda->raiseError("Server responded with something we have not requested!");
            return false;
        }

        if (!isset($status->unseen) || !is_numeric($status->unseen)) {
            $mtlda->raiseError("Server responded invalid!");
            return false;
        }

        return $status->unseen;
    }

    private function retrieveListOfMails($msg_cnt)
    {
        global $mtlda;

        if (!$this->isConnected()) {
            $mtlda->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!($msg_cnt >= 1)) {
            return false;
        }

        if (!$list = imap_fetch_overview($this->imap_session, "1:{$msg_cnt}")) {
            $mtlda->raiseError("imap_fetch_overview() failed!");
            return false;
        }

        if (!is_array($list)) {
            $mtlda->raiseError("imap_fetch_overview() returned no array!". imap_last_error());
            return false;
        }

        return $list;
    }

    private function retrieveMail($msgno)
    {
        global $mtlda;

        if (!$this->isConnected()) {
            $mtlda->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!($msgno >= 1)) {
            return false;
        }

        if (!$structure = imap_fetchstructure($this->imap_session, $msgno)) {
            $mtlda->raiseError("Unable to retrieve structure!". imap_last_error());
            return false;
        }

        if (empty($structure) || !is_object($structure)) {
            $mtlda->raiseError("imap_fetchstructure() hasn't returned an object!". imap_last_error());
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
        global $mtlda;

        if (!is_array($msg)) {
            $mtlda->raiseError(__METHOD__ .', first parameter should be an array!');
            return false;
        }

        $structure = $msg['structure'];
        $msgno = $msg['msgno'];

        // if mail does not contain MIME parts, we will not find any attachment in it.
        if (!isset($structure->parts) || empty($structure->parts)) {
            return true;
        }

        $attachments = array();

        foreach ($structure->parts as $id => $part) {

            // we can skip text-only parts
            if ($part->type == 0) {
                continue;
            }

            // the actual MIME part id is +1 then the array key
            $id+=1;

            if (!$this->parseMimePart($part, $id, $attachments)) {
                $mtlda->raiseError(__CLASS__ .'::parseMimePart() returned false!');
                return false;
            }
        }

        foreach ($attachments as $attachment) {

            if (!isset($attachment['filename']) || empty($attachment['filename'])) {
                $mtlda->raiseError("Something is wrong. No filename is known for this mime part!");
                return false;
            }

            // just if someone tries to fool us...
            $filename = $attachment['filename'];

            // if file is not suffixed by a .pdf, we skip it
            if (!preg_match('/\.pdf$/i', $filename)) {
                continue;
            }

            if (($attachment_body = $this->fetchMimePartBody($msgno, $attachment)) === false) {
                $mtlda->raiseError(__CLASS__ .'::fetchMimePartBody() returned false!');
                return false;
            }

            if (empty($attachment_body)) {
                $mtlda->raiseError("No body fetched for mime part!");
                return false;
            }

            $indir_ready = false;

            do {
                $destdir = $this::INCOMING_DIRECTORY .'/'. uniqid();

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
                $dest = $this::INCOMING_DIRECTORY .'/'. $filename;
            }

            if (file_exists($dest)) {
                $mtlda->raiseError(
                    "A file with the name {$file['name']} is already present in the incoming directory!"
                );
                return false;
            }

            if (!file_put_contents($dest, $attachment_body)) {
                $mtlda->raiseError("file_put_contents() returned false!");
                return false;
            }
        }

        return true;
    }

    private function parseMimePart(&$part, $id, &$attachments)
    {
        global $mtlda;

        if (!is_object($part)) {
            $mtlda->raiseError(__METHOD__ .', first parameter should be an object!');
            return false;
        }

        if (!is_array($attachments)) {
            $mtlda->raiseError(__METHOD__ .', third parameter should be an object!');
            return false;
        }

        // if we have parts nested in parts, we have to run through recursive
        if (isset($part->parts)) {

            foreach ($part->parts as $subid => $subpart) {

                // we can skip text-only parts
                if ($part->type == 0) {
                    continue;
                }

                // the actual MIME part id is +1 then the array key
                $subid+=1;

                if (
                    isset($part->ifsubtype) &&
                    $part->ifsubtype &&
                    isset($part->subtype) &&
                    strtoupper($part->subtype) == 'RFC822'
                ) {
                    $nextid = $id;
                } else {
                    $nextid = $id .'.'. $subid;
                }

                if (!$this->parseMimePart($subpart, $nextid, $attachments)) {
                    $mtlda->raiseError(__CLASS__ .'::parseMimePart() returned false!');
                    return false;
                }
            }
            return true;
        }

        if (
            isset($part->ifdparameters) &&
            !empty($part->ifdparameters) &&
            isset($part->dparameters) &&
            !empty($part->dparameters)
        ) {
            if (!$this->parseMimePartParameters($part->dparameters, $attachments, $part->encoding, $id)) {
                $mtlda->raiseError(__CLASS__ .'::parseMimePartParameters() returned false!');
                return false;
            }
        }

        if (
            isset($part->ifparameters) &&
            !empty($part->ifparameters) &&
            isset($part->parameters) &&
            !empty($part->parameters)
        ) {
            if (!$this->parseMimePartParameters($part->parameters, $attachments, $part->encoding, $id)) {
                $mtlda->raiseError(__CLASS__ .'::parseMimePartParameters() returned false!');
                return false;
            }
        }

        return true;
    }

    private function parseMimePartParameters(&$partparam, &$attachments, $encoding, $id)
    {
        global $mtlda;

        if (!is_array($partparam)) {
            $mtlda->raiseError(__METHOD__ .', first parameter has to be an array!');
            return false;
        }

        if (!is_array($attachments)) {
            $mtlda->raiseError(__METHOD__ .', second parameter has to be an array!');
            return false;
        }

        if (!is_numeric($encoding)) {
            $mtlda->raiseError(__METHOD__ .', third parameter has to be a number!');
            return false;
        }

        foreach ($partparam as $param) {

            if (strtolower($param->attribute) != 'filename') {
                continue;
            }

            array_push($attachments, array(
                'id' => $id,
                'filename' => basename($param->value),
                'encoding' => $encoding
            ));
        }

        return true;
    }

    private function fetchMimePartBody($msgno, $attachment)
    {
        global $mtlda;

        if (!$this->isConnected()) {
            $mtlda->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!is_numeric($msgno)) {
            $mtlda->raiseError(__METHOD__ .', first parameter should be an array!');
            return false;
        }

        if (!is_array($attachment)) {
            $mtlda->raiseError(__METHOD__ .', second parameter should be an array!');
            return false;
        }

        if (!$body = imap_fetchbody($this->imap_session, $msgno, $attachment['id'])) {
            $mtlda->raiseError(
                "imap_fetchbody() returned false!<br />Msgno: {$msgno}<br />".
                "Attachment: {$attachment['id']}<br />". imap_last_error()
            );
            return false;
        }

        if (empty($body)) {
            $mtlda->raiseError('imap_fetchbody() returned no valid content!');
            return false;
        }

        // 3 = BASE64
        if ($attachment['encoding'] == 3) {
            if (!$body = base64_decode($body)) {
                $mtlda->raiseError("base64_decode() returned false!");
                return false;
            }
            return $body;
        // 4 = QUOTED-PRINTABLE
        } elseif ($attachment['encoding'] == 4) {
            return quoted_printable_decode($body);
        }

        $mtlda->raiseError("Unsupported encoding: {$attachment['encoding']}!");
        return false;
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
        global $mtlda;

        if (!$this->isConnected()) {
            $mtlda->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!imap_delete($this->imap_session, $msgno)) {
            $mtlda->raiseError("imap_delete() returned false!". imap_last_error());
            return false;
        }

        return true;
    }

    private function flagMailSeen($msgno)
    {
        global $mtlda;

        if (!$this->isConnected()) {
            $mtlda->raiseError("Need to be connected to the mail server to proceed!");
            return false;
        }

        if (!imap_setflag_full($this->imap_session, $msgno, '\Seen')) {
            $mtlda->raiseError("imap_setflag_full() returned false!". imap_last_error());
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
