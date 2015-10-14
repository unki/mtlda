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

use Mtlda\Controllers;

class DocumentPropertiesModel extends DefaultModel
{
    public $table_name = 'document_properties';
    public $column_name = 'dp';
    public $fields = array(
            'dp_idx' => 'integer',
    );
    public $avail_items = array();
    public $items = array();

    public function __construct($id = null, $guid = null)
    {
        global $mtlda;

        parent::__construct(null);

        if (!$this->load($id, $guid)) {
            $mtlda->raiseError(__CLASS__ .', load() returned false!', true);
            return false;
        }

        return true;
    }

    public function load($id = null, $guid = null)
    {
        global $mtlda, $db;

        $idx_field = $this->column_name ."_idx";
        $params = array();

        $sql =
            "SELECT
                {$idx_field}
            FROM
                TABLEPREFIX{$this->table_name}";

        if (isset($id) && !empty($id)) {
            $sql.= " WHERE {$this->column_name}_document_idx LIKE ?";
            array_push($params, $id);
        }

        if (isset($id) && !empty($id) &&
            isset($guid) && !empty($guid)
        ) {
            $sql.= 'AND';
        } else {
            $sql.= ' WHERE ';
        }

        if (isset($guid) && !empty($guid)) {
            $sql.= " {$this->column_name}_document_guid LIKE ?";
            array_push($params, $guid);
        }

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!($db->execute($sth, $params))) {
            $db->freeStatement($sth);
            $mtlda->raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        while ($row = $sth->fetch()) {
            array_push($this->avail_items, $row->$idx_field);

            try {
                $this->items[$row->$idx_field] = new DocumentPropertyModel($row->$idx_field);
            } catch (\Exception $e) {
                $mtlda->raiseError(__METHOD__ .'(), failed to load DocumentProperty!');
                return false;
            }
        }

        $db->freeStatement($sth);
        return true;
    }

    public function delete()
    {
        global $mtlda;

        foreach ($this->items as $item) {
            if ($item->delete()) {
                continue;
            }

            $mtlda->raiseError(get_class($item) .'::delete() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
