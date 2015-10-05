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
        'msg_scope' => 'string',
        'msg_submit_time' => 'timestamp',
        'msg_session_id' => 'string',
        'msg_command' => 'string',
        'msg_body' => 'string',
        'msg_value' => 'string',
        'msg_in_processing' => 'string',
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
        global $mtlda;

        if (!isset($this->msg_command)) {
            $mtlda->raiseError(__METHOD__ .', \$msg_command has not been set yet!');
            return false;
        }

        return $this->msg_command;
    }

    public function getBody()
    {
        global $mtlda;

        if (!isset($this->msg_body)) {
            $mtlda->raiseError(__METHOD__ .', \$msg_body has not been set yet!');
            return false;
        }

        return $this->msg_body;
    }

    public function setScope($scope)
    {
        global $mtlda;

        if (!is_string($scope)) {
            $mtlda->raiseError(__METHOD__ .', parameter has to be a string!');
            return false;
        }

        if (!in_array($scope, array('inbound', 'outbound'))) {
            $mtlda->raiseError(__METHOD__ .', allowed values for scope are "inbound" and "outbound" only!');
            return false;
        }

        $this->msg_scope = $scope;
        return true;
    }

    public function getScope()
    {
        global $mtlda;

        if (!isset($this->msg_scope)) {
            $mtlda->raiseError(__METHOD__ .', \$msg_scope has not been set yet!');
            return false;
        }

        return $this->msg_scope;
    }

    public function isClientMessage()
    {
        global $mtlda;

        if (!($scope = $this->getScope())) {
            $mtlda->raiseError(__CLASS__ .'::getScope() returned false!');
            return false;
        }

        if ($scope != 'inbound') {
            return false;
        }

        return true;
    }

    public function isServerMessage()
    {
        global $mtlda;

        if (!($scope = $this->getScope())) {
            $mtlda->raiseError(__CLASS__ .'::getScope() returned false!');
            return false;
        }

        if ($scope != 'outbound') {
            return false;
        }

        return true;
    }

    public function setProcessingFlag($value = true)
    {
        if (!$value) {
            $this->msg_in_processing = 'N';
            return true;
        }

        $this->msg_in_processing = 'Y';
        return true;
    }

    public function getProcessingFlag()
    {
        if (!isset($this->msg_in_processing)) {
            return 'N';
        }

        return $this->msg_in_processing;
    }

    public function isProcessing()
    {
        global $mtlda;

        if (!isset($this->getProcessingFlag)) {
            return false;
        }

        if ($this->msg_in_processing != 'Y') {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
