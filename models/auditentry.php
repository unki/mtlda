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

class AuditEntryModel extends DefaultModel
{
    public $table_name = 'audit';
    public $column_name = 'audit';
    public $fields = array(
            'audit_idx' => 'integer',
            'audit_guid' => 'string',
            'audit_type' => 'string',
            'audit_scene' => 'string',
            'audit_message' => 'string',
            'audit_time' => 'timestamp',
            );

    public function __construct($id = null, $guid = null)
    {
        global $mtlda, $db;

        // are we creating a new item?
        if (!isset($id) && !isset($guid)) {
            parent::__construct(null);
            return true;
        }

        // get $id from db
        $sql = "
            SELECT
                audit_idx
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
        ";

        $arr_query = array();
        if (isset($id)) {
            $sql.= "
                audit_idx LIKE ?
            ";
            $arr_query[] = $id;
        }
        if (isset($id) && isset($guid)) {
            $sql.= "
                AND
            ";
        }
        if (isset($guid)) {
            $sql.= "
                audit_guid LIKE ?
            ";
            $arr_query[] = $guid;
        };

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError("DatabaseController::prepare() returned false!");
            return false;
        }

        if (!$db->execute($sth, $arr_query)) {
            $mtlda->raiseError("DatabaseController::execute() returned false!");
            return false;
        }

        if (!($row = $sth->fetch())) {
            $mtlda->raiseError("Unable to find archive item with guid value {$guid}");
            return false;
        }

        if (!isset($row->archive_idx) || empty($row->archive_idx)) {
            $mtlda->raiseError("Unable to find audit entry with guid value {$guid}");
            return false;
        }

        $db->freeStatement($sth);

        parent::__construct($row->audit_idx);

        return true;
    }

    public function preSave()
    {
        global $mtlda;

        if (!($time = microtime(true))) {
            $mtlda->raiseError("microtime() returned false!");
            return false;
        }

        $this->audit_time = $time;

        return true;
    }

    public function setGuid($guid)
    {
        global $mtlda;

        if (empty($guid)) {
            return true;
        }

        if (!$mtlda->isValidGuidSyntax($guid)) {
            $mtlda->raiseError("MTLDA::isValidGuidSyntax returned false!");
            return false;
        }

        $this->audit_guid = $guid;
        return true;
    }

    public function setMessage($message)
    {
        global $mtlda;

        if (empty($message)) {
            $mtlda->raiseError(__TRAIT__ .", \$message can not be empty!");
            return false;
        }
        if (!is_string($message)) {
            $mtlda->raiseError(__TRAIT__ .", \$message must be a string!");
            return false;
        }

        if (strlen($message) > 8192) {
            $mtlda->raiseError(__TRAIT__ .", \$message is to long!");
            return false;
        }

        $this->audit_message = $message;
        return true;
    }

    public function setEntryType($entry_type)
    {
        global $mtlda;

        if (empty($entry_type)) {
            $mtlda->raiseError(__TRAIT__ .", \$entry_type can not be empty!");
            return false;
        }
        if (!is_string($entry_type)) {
            $mtlda->raiseError(__TRAIT__ .", \$entry_type must be a string!");
            return false;
        }

        if (strlen($entry_type) > 255) {
            $mtlda->raiseError(__TRAIT__ .", \$entry_type is to long!");
            return false;
        }

        $this->audit_type = $entry_type;
        return true;

    }

    public function setScene($scene)
    {
        global $mtlda;

        if (empty($scene)) {
            $mtlda->raiseError(__TRAIT__ .", \$scene can not be empty!");
            return false;
        }
        if (!is_string($scene)) {
            $mtlda->raiseError(__TRAIT__ .", \$scene must be a string!");
            return false;
        }

        if (strlen($scene) > 255) {
            $mtlda->raiseError(__TRAIT__ .", \$scene is to long!");
            return false;
        }

        $this->audit_scene = $scene;
        return true;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
