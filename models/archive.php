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

class ArchiveModel extends DefaultModel
{
    public $table_name = 'archive';
    public $column_name = 'document';
    public $fields = array(
            'document_idx' => 'integer',
            );
    public $avail_items = array();
    public $items = array();

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->load();

        return true;
    }

    public function load()
    {
        global $mtlda, $db;

        $idx_field = $this->column_name ."_idx";
        $guid_field = $this->column_name ."_guid";
        $latest_version_field = $this->column_name ."_latest_version";

        $result = $db->query(
            "SELECT
                *
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                document_version LIKE 1"
        );

        if (!$result) {
            $mtlda->raiseError("Failed to load archive list.");
            return false;
        }

        while ($row = $result->fetch()) {
            $latest_document = $this->getLatestDocumentVersion(
                $row->$idx_field,
                $row->$guid_field
            );

            if (!empty($latest_document) && is_array($latest_document)) {
                $row->$latest_version_field = $latest_document;
            }
            array_push($this->avail_items, $row->$idx_field);
            $this->items[$row->$idx_field] = $row;
        }
    }

    private function getLatestDocumentVersion($idx, $guid)
    {
        global $mtlda, $db;

        $result = $db->query(
            "SELECT
                document_idx,
                document_guid
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                document_derivation LIKE '{$idx}'
            AND
                document_derivation_guid LIKE '{$guid}'
            ORDER BY
                document_version DESC
            LIMIT 0,1"
        );

        if (!$result) {
            $this->mtlda->raiseError("Failed to retrive latest document version");
            return false;
        }

        if (!$row = $result->fetch()) {
            return true;
        }

        return array('idx' => $row->document_idx, 'guid' => $row->document_guid);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
