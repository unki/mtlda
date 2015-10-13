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

namespace Mtlda\Models ;

class ArchiveModel extends DefaultModel
{
    public $table_name = 'archive';
    public $column_name = 'document';
    public $fields = array(
            'document_idx' => 'integer',
            );
    public $avail_items = array();
    public $items = array();

    public function __construct($sort_order = null)
    {
        global $mtlda;

        parent::__construct(null);
        if (!$this->load($sort_order)) {
            $mtlda->raiseError(__METHOD__ .'(), load() returned false!', true);
            return false;
        }

        return true;
    }

    public function load($sort_order = null)
    {
        global $mtlda, $db;

        if (isset($sort_order) && !empty($sort_order)) {

            if (!is_array($sort_order)) {
                $mtlda->raiseError(__METHOD__ .'(), \$sort_order is not an array!');
                return false;
            }

            if (
                !isset($sort_order['by']) ||
                empty($sort_order['by']) ||
                !is_string($sort_order['by']) ||
                !isset($sort_order['order']) ||
                empty($sort_order['order']) ||
                !is_string($sort_order['order'])
            ) {
                $mtlda->raiseError(__METHOD__ .'(), \$sort_order is invalid!');
                return false;
            }

            if (!preg_match('/[a-zA-Z_]/', $sort_order['by'])) {
                $mtlda->raiseError(__METHOD__ .'(), \$by looks invalid!');
                return false;
            }

            if (!in_array(strtoupper($sort_order['order']), array('ASC', 'DESC'))) {
                $mtlda->raiseError(__METHOD__ .'(), \$order is invalid!');
                return false;
            }
        }

        $idx_field = $this->column_name ."_idx";
        $guid_field = $this->column_name ."_guid";
        $latest_version_field = $this->column_name ."_latest_version";

        $sql =
            "SELECT
                *
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                document_version LIKE 1";

        if (!empty($sort_order)) {
            $sql.=
                ' ORDER BY '.
                $db->quote($sort_order['by']) .
                ' ' . $db->quote($sort_order['order']);
        }

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!($db->execute($sth))) {
            $db->freeStatement($sth);
            $mtlda->raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        while ($row = $sth->fetch()) {
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

        $db->freeStatement($sth);
        return true;
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
