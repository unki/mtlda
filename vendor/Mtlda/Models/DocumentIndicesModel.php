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

class DocumentIndicesModel extends DefaultModel
{
    public $table_name = 'document_indices';
    public $column_name = 'di';
    public $fields = array(
            'di_idx' => 'integer',
    );
    public $avail_items = array();
    public $items = array();

    public function __construct($hash = null)
    {
        if (!$hash) {
            parent::__construct();
            return true;
        }

        if (!$this->load($hash)) {
            $this->raiseError(__CLASS__ .', load() returned false!', true);
            return false;
        }

        return true;
    }

    public function load($hash)
    {
        global $db;

        if (!$hash) {
            return parent::load();
        }
    
        $idx_field = $this->column_name ."_idx";

        $sql =
            "SELECT
                {$idx_field}
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                {$this->column_name}_file_hash LIKE ?";

        if (!($sth = $db->prepare($sql))) {
            $this->raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!($db->execute($sth, array($hash)))) {
            $db->freeStatement($sth);
            $this->raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        while ($row = $sth->fetch()) {
            array_push($this->avail_items, $row->$idx_field);
            try {
                $this->items[$row->$idx_field] = new DocumentIndexModel($row->$idx_field);
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ .'(), failed to load DocumentProperty!');
                return false;
            }
        }

        $db->freeStatement($sth);
        return true;
    }

    public function delete()
    {
        foreach ($this->items as $item) {
            if (!$item->delete()) {
                $this->raiseError(get_class($item) .'::delete() returned false!');
                return false;
            }
        }

        return true;
    }

    public function getIndices()
    {
        if (!isset($this->items)) {
            return false;
        }

        return $this->items;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
