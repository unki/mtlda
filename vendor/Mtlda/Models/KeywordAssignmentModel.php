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

    public function setArchive($idx)
    {
        global $mtlda;

        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            $mtlda->raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        $this->akd_archive_idx = $idx;
        return true;
    }

    public function getArchive()
    {
        if (!isset($this->akd_archive_idx)) {
            return false;
        }

        return $this->akd_archive_idx;
    }

    public function setKeyword($idx)
    {
        global $mtlda;

        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            $mtlda->raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        $this->akd_keyword_idx = $idx;
        return true;
    }

    public function getKeyword()
    {
        if (!isset($this->akd_keyword_idx)) {
            return false;
        }

        return $this->akd_keyword_idx;
    }

    public function setQueue($idx)
    {
        global $mtlda;

        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            $mtlda->raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        $this->akd_queue_idx = $idx;
        return true;
    }

    public function getQueue()
    {
        if (!isset($this->akd_queue_idx)) {
            return false;
        }

        return $this->akd_queue_idx;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
