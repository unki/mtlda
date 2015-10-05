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

use MTLDA\Models;

class MessageBusController extends DefaultController
{
    public function __construct()
    {
        global $mtlda, $session;

        if (!$session) {
            $mtlda->raiseError(__METHOD__ ." requires SessionController to be initialized!", true);
            return false;
        }

        return true;
    }

    public function submit($messages_raw)
    {
        global $mtlda, $session;

        if (!($sessionid = $session->getSessionId())) {
            $mtlda->raiseError(get_class($session) .'::getSessionId() returned false!');
            return false;
        }

        if (empty($messages_raw)) {
            $mtlda->raiseError(__METHOD__ .', first parameter can not be empty!');
            return false;
        }

        if (!is_string($messages_raw)) {
            $mtlda->raiseError(__METHOD__ .', first parameter has to be a string!');
            return false;
        }

        if (!($json = json_decode($messages_raw))) {
            $mtlda->raiseError('json_decode() returned false!');
            return false;
        }

        if (empty($json)) {
            return true;
        }

        if (
            !isset($json->count) || empty($json->count) ||
            !isset($json->size) || empty($json->size) ||
            !isset($json->hash) || empty($json->hash) ||
            !isset($json->json) || empty($json->json)
        ) {
            $mtlda->raiseError(__METHOD__ .', submitted message object is incomplete!');
            return false;
        }

        if (strlen($json->json) != $json->size) {
            $mtlda->raiseError(__METHOD__ .', verification failed - size differs!');
            return false;
        }

        if (sha1($json->json) != $json->hash) {
            $mtlda->raiseError(__METHOD__ .', verification failed - hash differs!');
            return false;
        }

        if (!($messages = json_decode($json->json))) {
            $mtlda->raiseError('json_decode() returned false!');
            return false;
        }

        foreach ($messages as $message) {

            if (!is_object($message)) {
                $mtlda->raiseError(__METHOD__ .', \$message is not an object!');
                return false;
            }

            if (!isset($message->command) || empty($message->command)) {
                $mtlda->raiseError(__METHOD__ .', \$message does not contain a command!');
                return false;
            }

            if (!isset($message->message) || empty($message->message)) {
                $mtlda->raiseError(__METHOD__ .', \$message does not contain a body!');
                return false;
            }

            try {
                $mbmsg = new Models\MessageModel;
            } catch (\Exception $e) {
                $mtlda->raiseError('Failed to load MessageModel!');
                return false;
            }

            if (!$mbmsg->setCommand($message->command)) {
                $mtlda->raiseError(get_class($mbmsg) .'::setCommand() returned false!');
                return false;
            }

            if (!$mbmsg->setSessionId($sessionid)) {
                $mtlda->raiseError(get_class($mbmsg) .'::setSessionId() returned false!');
                return false;
            }

            $mbmsg->setProcessingFlag(false);

            if (is_object($message->message) || is_array($message->message)) {
                $msgbody = serialize($message->message);
            } else {
                $msgbody = $message->message;
            }

            if (!$mbmsg->setMessage($msgbody)) {
                $mtlda->raiseError(get_class($mbmsg) .'::setMessage() returned false!');
                return false;
            }

            if (!$mbmsg->setScope('inbound')) {
                $mtlda->raiseError(get_class($mbmsg) .'::setScope() returned false!');
                return false;
            }

            if (!$mbmsg->save()) {
                $mtlda->raiseError(get_class($mbmsg) .'::save() returned false!');
                return false;
            }
        }

        return true;
    }

    public function poll()
    {
        global $mtlda, $session;

        $messages = array();

        try {
            $msgs = new Models\MessageBusModel;
        } catch (\Exception $e) {
            $mtlda->raiseError('Failed to load MessageBusModel!');
            return false;
        }

        if (!($sessionid = $session->getSessionId())) {
            $mtlda->raiseError(get_class($session) .'::getSessionId() returned false!');
            return false;
        }

        if (($messages = $msgs->getMessagesForSession($sessionid)) === false) {
            $mtlda->raiseError(get_class($msgs) .'::getMessagesForSession() returned false!');
            return false;
        }

        $raw_messages = array();
        foreach ($messages as $message) {
            $raw_messages[] = array(
                'id' => $message->getId(),
                'guid' => $message->getGuid(),
                'command' => $message->getCommand(),
                'body' => $message->getBody()
            );

            /*if (!$message->delete()) {
                $mtlda->raiseError(get_class($message) .'::delete() returned false!');
                return false;
            }*/
        }

        if (!($json = json_encode($raw_messages))) {
            $mtlda->raiseError('json_encode() returned false!');
            return false;
        }

        $len = count($raw_messages);
        $size = strlen($json);
        $hash = sha1($json);

        $reply_raw = array(
            'count' => $len,
            'size' => $size,
            'hash' => $hash,
            'json' => $json
        );

        if (!($reply = json_encode($reply_raw))) {
            $mtlda->raiseError('json_encode() returned false!');
            return false;
        }

        return $reply;
    }

    public function getRequestMessages()
    {
        global $mtlda;

        try {
            $msgs = new Models\MessageBusModel;
        } catch (\Exception $e) {
            $mtlda->raiseError('Failed to load MessageBusModel!');
            return false;
        }

        if (($messages = $msgs->getServerRequests()) === false) {
            $mtlda->raiseError(get_class($msgs) .'::getServerRequests() returned false!');
            return false;
        }

        if (!is_array($messages)) {
            $mtlda->raiseError(get_class($msgs) .'::getServerRequests() has not returned an arary!');
            return false;
        }

        return $messages;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
