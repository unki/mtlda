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

class KeywordsModel extends DefaultModel
{
    public $table_name = 'keywords';
    public $column_name = 'keyword';
    public $fields = array(
        'keyword_idx' => 'integer',
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
        global $db;

        $idx_field = $this->column('idx');

        $result = $db->query(
            "SELECT
            *
            FROM
            TABLEPREFIX{$this->table_name}"
        );

        if ($result === false) {
            $mtlda->raiseError("failed to fetch keywords!");
            return false;
        }

        while ($row = $result->fetch()) {
            array_push($this->avail_items, $row->$idx_field);
            $this->items[$row->$idx_field] = $row;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
