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

class DocumentPropertyModel extends DefaultModel
{
    protected static $model_table_name = 'document_properties';
    protected static $model_column_prefix = 'dp';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'file_hash' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'property' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'value' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );

    public function setDocumentProperty($property)
    {
        global $mtlda;

        if (!isset($property) || empty($property)) {
            $mtlda->raiseError(__METHOD__ .'(), \$property needs to be set!');
            return false;
        }

        $this->dp_property = $property;
        return true;
    }

    public function setDocumentValue($value)
    {
        global $mtlda;

        if (!isset($value) || empty($value)) {
            $mtlda->raiseError(__METHOD__ .'(), \$value needs to be set!');
            return false;
        }

        $this->dp_value = $value;
        return true;
    }

    public function getDocumentProperty()
    {
        global $mtlda;

        if (!isset($this->dp_property)) {
            return false;
        }

        return $this->dp_property;
    }

    public function getDocumentValue()
    {
        global $mtlda;

        if (!isset($this->dp_value)) {
            return false;
        }

        return $this->dp_value;
    }

    public function setFileHash($hash)
    {
        if (!isset($hash) || empty($hash)) {
            static::raiseError(__METHOD__ .'(), \$hash needs to be set!');
            return false;
        }

        $this->dp_file_hash = $hash;
        return true;
    }

    public function getFileHash()
    {
        if (!isset($this->dp_file_hash)) {
            return false;
        }

        return $this->dp_file_hash;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
