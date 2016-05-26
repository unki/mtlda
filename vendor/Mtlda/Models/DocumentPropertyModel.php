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

    public function hasDocumentProperty()
    {
        if (!$this->hasFieldValue('property')) {
            return false;
        }

        return true;
    }

    public function getDocumentProperty()
    {
        if (!$this->hasDocumentProperty()) {
            static::raiseError(__CLASS__ .'::hasDocumentProperty() returned false!');
            return false;
        }

        if (($property = $this->getFieldValue('property')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $property;
    }

    public function setDocumentProperty($property)
    {
        if (!isset($property) || empty($property)) {
            static::raiseError(__METHOD__ .'(), \$property needs to be set!');
            return false;
        }

        if (!$this->setFieldValue('property', $property)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasDocumentValue()
    {
        if (!$this->hasFieldValue('value')) {
            return false;
        }

        return true;
    }

    public function getDocumentValue()
    {
        if (!$this->hasDocumentValue()) {
            static::raiseError(__CLASS__ .'::hasDocumentValue() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('value')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setDocumentValue($value)
    {
        if (!isset($value) || empty($value)) {
            static::raiseError(__METHOD__ .'(), \$value needs to be set!');
            return false;
        }

        if (!$this->setFieldValue('value', $value)) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasFileHash()
    {
        if (!$this->hasFieldValue('file_hash')) {
            return false;
        }

        return true;
    }

    public function getFileHash()
    {
        if (!$this->hasFileHash()) {
            static::raiseError(__CLASS__ .'::hasFileHash() returned false!');
            return false;
        }

        if (($hash = $this->getFieldValue('file_hash')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $hash;
    }

    public function setFileHash($hash)
    {
        if (!isset($hash) || empty($hash)) {
            static::raiseError(__METHOD__ .'(), \$hash needs to be set!');
            return false;
        }

        if (!$this->setFieldValue('file_hash', $hash)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
