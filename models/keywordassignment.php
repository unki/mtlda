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

use \PDO;

class KeywordAssignmentModel extends DefaultModel
{
    public $table_name = 'assign_keywords_to_document';
    public $column_name = 'akd';
    public $fields = array(
        'akd_idx' => 'integer',
        'akd_guid' => 'string',
        'akd_archive_idx' => 'integer',
        'akd_keyword_idx' => 'integer',
    );

    public function setArchive($idx)
    {
        global $mtlda;

        if (!is_numeric($idx)) {
            $mtlda->raiseError(__METHOD__ .', first parameter needs to be numeric!');
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

        if (!is_numeric($idx)) {
            $mtlda->raiseError(__METHOD__ .', first parameter needs to be numeric!');
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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
