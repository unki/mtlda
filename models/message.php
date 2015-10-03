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

namespace MTLDA\Models ;

class MessageModel extends DefaultModel
{
    public $table_name = 'message_bus';
    public $column_name = 'msg';
    public $fields = array(
        'msg_idx' => 'integer',
        'msg_guid' => 'integer',
        'msg_session_id' => 'string',
        'msg_command' => 'string',
        'msg_body' => 'string',
    );

    public function setCommand($command)
    {
        global $mtlda;

        if (empty($command)) {
            $mtlda->raiseError(__METHOD__ .', an empty command is not allowed!');
            return false;
        }

        if (!is_string($command)) {
            $mtlda->raiseError(__METHOD__ .', parameter has to be a string!');
            return false;
        }

        $this->msg_command = $command;
        return true;
    }

    public function setMessage($message)
    {
        global $mtlda;

        if (empty($message)) {
            $mtlda->raiseError(__METHOD__ .', an empty message is not allowed!');
            return false;
        }

        if (!is_string($message)) {
            $mtlda->raiseError(__METHOD__ .', parameter has to be a string!');
            return false;
        }

        $this->msg_body = $message;
        return true;
    }

    public function setSessionId($sessionid)
    {
        global $mtlda;

        if (empty($sessionid)) {
            $mtlda->raiseError(__METHOD__ .', an empty session id is not allowed!');
            return false;
        }

        if (!is_string($sessionid)) {
            $mtlda->raiseError(__METHOD__ .', parameter has to be a string!');
            return false;
        }

        $this->msg_session_id = $sessionid;
        return true;
    }


    public function getCommand()
    {
        return $this->msg_command;
    }

    public function getBody()
    {
        return $this->msg_body;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
