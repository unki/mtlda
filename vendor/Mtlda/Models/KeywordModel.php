<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017> <Andreas Unterkircher>
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

class KeywordModel extends DefaultModel
{
    protected static $model_table_name = 'keywords';
    protected static $model_column_prefix = 'keyword';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'name' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addRpcEnabledField('name');
        $this->addRpcAction('delete');
        return true;
    }

    protected function preDelete()
    {
        global $mtlda, $db;

        if (!$this->hasIdx()) {
            static::raiseError(__CLASS__ .'::hasIdx() returned false!');
            return false;
        }

        if (($keyword_idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        $result = $db->query(
            "DELETE FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_keyword_idx LIKE '{$keyword_idx}'"
        );

        if ($result === false) {
            $mtlda->raiseError("Deleting keyword assignments failed!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
