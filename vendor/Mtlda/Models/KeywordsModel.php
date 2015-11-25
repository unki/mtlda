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

class KeywordsModel extends DefaultModel
{
    public $table_name = 'keywords';
    public $column_name = 'keyword';
    public $fields = array(
        'keyword_idx' => 'integer',
    );
    public $avail_items = array();
    public $items = array();

    public function __construct()
    {
        if (!$this->load()) {
            $this->raiseError(__CLASS__ .'::load() returned false!', true);
            return false;
        }

        try {
            $this->permitRpcUpdates(true);
            $this->addRpcAction('delete');
        } catch (\Exception $e) {
            $this->raiseError(__CLASS__ .', failed to set RPC parameters!', true, $e);
            return false;
        }

        return true;
    }

    public function load()
    {
        global $db;

        $idx_field = $this->column('idx');
        $guid_field = $this->column('guid');

        $result = $db->query(
            "SELECT
                {$idx_field},
                {$guid_field}
            FROM
                TABLEPREFIX{$this->table_name}"
        );

        if ($result === false) {
            $this->raiseError(__METHOD__ .'(), failed to fetch keywords!');
            return false;
        }

        while ($row = $result->fetch()) {
            try {
                $keyword = new \Mtlda\Models\KeywordModel(
                    $row->$idx_field,
                    $row->$guid_field
                );
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ .'(), failed to load KeywordModel!');
                return false;
            }
            array_push($this->avail_items, $row->$idx_field);
            $this->items[$row->$idx_field] = $keyword;
        }

        return true;
    }

    public function flush()
    {
        global $mtlda, $db, $audit;

        // delete each KeywordModel
        foreach ($this->items as $item) {
            if (!$item->getId() || !$item->getGuid()) {
                $this->raiseError(__METHOD__ .'(), invalid $item found!');
                return false;
            }

            $keyword = $mtlda->loadModel("keyword", $item->getId(), $item->getGuid());

            if (!$keyword) {
                $this->raiseError(
                    "Error loading KeywordModel idx:{$item->getId()} guid:{$item->getGuid()}!"
                );
                return false;
            }

            if (!$keyword->delete()) {
                $this->raiseError(
                    "Error deleting KeywordModel idx:{$item->getId()} guid:{$item->getGuid()}!"
                );
                return false;
            }
        }

        // finally truncate the table
        $result = $db->query(
            "TRUNCATE TABLE TABLEPREFIX{$this->table_name}"
        );

        if ($result === false) {
            $this->raiseError(__METHOD__ ."(), failed to truncate '{$this->table_name}' table!");
            return false;
        }

        try {
            $audit->log(
                "flushing",
                "flushed",
                "keywords"
            );
        } catch (\Exception $e) {
            $this->raiseError(get_class($audit) .'::log() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
