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

class DocumentIndexModel extends DefaultModel
{
    protected static $model_table_name = 'document_indices';
    protected static $model_column_prefix = 'di';
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
        'text' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
    );

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

    public function hasDocumentText()
    {
        if (!$this->hasFieldValue('text')) {
            return false;
        }

        return true;
    }

    public function getDocumentText()
    {
        if (!$this->hasDocumentText()) {
            static::raiseError(__CLASS__ .'::hasDocumentText() returned false!');
            return false;
        }

        if (($text = $this->getFieldValue('text')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $text;
    }

    public function setDocumentText($text)
    {
        if (!isset($text) || empty($text)) {
            static::raiseError(__METHOD__ .'(), \$text needs to be set!');
            return false;
        }

        if (!$this->setFieldValue('text', $text)) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
