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

class KeywordModel extends DefaultModel
{
    public $table_name = 'keywords';
    public $column_name = 'keyword';
    public $fields = array(
        'keyword_idx' => 'integer',
        'keyword_guid' => 'string',
        'keyword_name' => 'string',
    );

    public function __construct($id = null, $guid = null)
    {
        global $mtlda, $db;

        if (!$this->permitRpcUpdates(true)) {
            $mtlda->raiseError("permitRpcUpdates() returned false!");
            return false;
        }

        try {
            $this->addRpcEnabledField('keyword_name');
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed on invoking addRpcEnabledField() method");
            return false;
        }

        // are we creating a new item?
        if (!isset($id) && !isset($guid)) {
            parent::__construct(null);
            return true;
        }

        // get $id from db
        $sql = "
            SELECT
                keyword_idx
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
        ";

        $arr_query = array();
        if (isset($id)) {
            $sql.= "
                keyword_idx LIKE ?
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
                keyword_guid LIKE ?
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
            $mtlda->raiseError("Unable to find keyword with guid value {$guid}");
            return false;
        }

        if (!isset($row->keyword_idx) || empty($row->keyword_idx)) {
            $mtlda->raiseError("Unable to find keyword entry with guid value {$guid}");
            return false;
        }

        $db->freeStatement($sth);
        parent::__construct($row->keyword_idx);

        return true;
    }

    protected function preDelete()
    {
        global $mtlda, $db;

        $result = $db->query(
            "DELETE FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_keyword_idx LIKE '{$this->keyword_idx}'"
        );

        if ($result === false) {
            $mtlda->raiseError("Deleting keyword assignments failed!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
