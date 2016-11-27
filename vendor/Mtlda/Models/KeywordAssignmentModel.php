<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
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

class KeywordAssignmentModel extends DefaultModel
{
    protected static $model_table_name = 'assign_keywords_to_document';
    protected static $model_column_prefix = 'akd';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'archive_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'queue_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'keyword_idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
    );

    public function hasArchive()
    {
        if (!$this->hasFieldValue('archive_idx')) {
            return false;
        }

        return true;
    }

    public function getArchive()
    {
        if (!$this->hasArchive()) {
            static::raiseError(__CLASS__ .'::hasArchive() returned false!');
            return false;
        }

        if (($archive_idx = $this->getFieldValue('archive_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $archive_idx;
    }

    public function setArchive($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('archive_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasKeyword()
    {
        if (!$this->hasFieldValue('keyword_idx')) {
            return false;
        }

        return true;
    }

    public function getKeyword()
    {
        if (!$this->hasKeyword()) {
            static::raiseError(__CLASS__ .'::hasKeyword() returned false!');
            return false;
        }

        if (($keyword_idx = $this->getFieldValue('keyword_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $keyword_idx;
    }

    public function setKeyword($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('keyword_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasQueue()
    {
        if (!$this->hasFieldValue('queue_idx')) {
            return false;
        }

        return true;
    }

    public function getQueue()
    {
        if (!$this->hasQueue()) {
            static::raiseError(__CLASS__ .'::hasQueue() returned false!');
            return false;
        }

        if (($queue_idx = $this->getFieldValue('queue_idx')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $queue_idx;
    }

    public function setQueue($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            static::raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('queue_idx', $idx)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
