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

class DocumentIndexModel extends DefaultModel
{
    public $table_name = 'document_indices';
    public $column_name = 'di';
    public $fields = array(
        'di_idx' => 'integer',
        'di_guid' => 'string',
        'di_file_hash' => 'string',
        'di_text' => 'string',
    );

    public function setFileHash($hash)
    {
        if (!isset($hash) || empty($hash)) {
            $this->raiseError(__METHOD__ .'(), \$hash needs to be set!');
            return false;
        }

        $this->di_file_hash = $hash;
        return true;
    }

    public function setDocumentText($text)
    {
        if (!isset($text) || empty($text)) {
            $this->raiseError(__METHOD__ .'(), \$text needs to be set!');
            return false;
        }

        $this->di_text = $text;
        return true;
    }

    public function getFileHash()
    {
        if (!isset($this->di_file_hash)) {
            return false;
        }

        return $this->di_file_hash;
    }

    public function getDocumentText()
    {
        if (!isset($this->di_text)) {
            return false;
        }

        return $this->di_text;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
