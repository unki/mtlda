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
        parent::__construct(null);
        if (!$this->load($sort_order)) {
            $this->raiseError(__METHOD__ .'(), load() returned false!', true);
            return false;
        }

        return true;
    }

    public function load($sort_order = null)
    {
        global $db;

        if (isset($sort_order) && !empty($sort_order)) {
            if (!is_array($sort_order)) {
                $this->raiseError(__METHOD__ .'(), \$sort_order is not an array!');
                return false;
            }

            if (!isset($sort_order['by']) ||
                empty($sort_order['by']) ||
                !is_string($sort_order['by']) ||
                !isset($sort_order['order']) ||
                empty($sort_order['order']) ||
                !is_string($sort_order['order'])
            ) {
                $this->raiseError(__METHOD__ .'(), \$sort_order is invalid!');
                return false;
            }

            if (!preg_match('/[a-zA-Z_]/', $sort_order['by'])) {
                $this->raiseError(__METHOD__ .'(), \$by looks invalid!');
                return false;
            }

            if (!in_array(strtoupper($sort_order['order']), array('ASC', 'DESC'))) {
                $this->raiseError(__METHOD__ .'(), \$order is invalid!');
                return false;
            }
        }

        $idx_field = $this->column_name ."_idx";
        $guid_field = $this->column_name ."_guid";
        $latest_version_field = $this->column_name ."_latest_version";

        $sql =
            "SELECT
                {$idx_field},
                {$guid_field}
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
            $this->raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!($db->execute($sth))) {
            $db->freeStatement($sth);
            $this->raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        while ($row = $sth->fetch()) {
            try {
                $document = new \Mtlda\Models\DocumentModel(
                    $row->$idx_field,
                    $row->$guid_field
                );
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ .'(), failed to load DocumentModel!');
                return false;
            }

            array_push($this->avail_items, $document->getId());
            $this->items[$document->getId()] = $document;
        }

        $db->freeStatement($sth);
        return true;
    }

    public function getExpiredDocuments()
    {
        if (!isset($this->items)) {
            $this->raiseError(__METHOD__ .'(), $this->items not correctly set!');
            return false;
        }

        if (empty($this->items) || !is_array($this->items)) {
            return array();
        }

        $expired = array();
        $current_date = time();

        if (empty($current_date) && !is_numeric($current_date)) {
            $this->raiseError(__METHOD__ .'(), failed to get current date!');
            return false;
        }

        foreach ($this->items as $document) {
            if (!$document->hasExpiryDate()) {
                continue;
            }

            if (($expiry_date = $document->getExpiryDate()) === false) {
                $this->raiseError(get_class($document) .'::getExpiryDate() returned false!');
                return false;
            }

            if (($expiry_date = strtotime($expiry_date)) === false) {
                $this->raiseError(__METHOD__ .'(), strtotime() returned false!');
                return false;
            }

            if (empty($expiry_date) || !is_numeric($expiry_date)) {
                $this->raiseError(__METHOD__ .'(), strotime() has not returned a valid timestamp!');
                return false;
            }

            if ($expiry_date > $current_date) {
                continue;
            }

            array_push($expired, $document);
        }

        return $expired;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
