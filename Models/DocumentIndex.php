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

use Mtlda\Controllers;

class DocumentIndexModel extends DefaultModel
{
    public $table_name = 'document_indices';
    public $column_name = 'di';
    public $fields = array(
        'di_idx' => 'integer',
        'di_document_idx' => 'integer',
        'di_document_guid' => 'string',
        'di_text' => 'string',
    );

    public function setDocumentIdx($idx)
    {
        global $mtlda;

        if (!isset($idx) || empty($idx)) {
            $mtlda->raiseError(__METHOD__ .'(), \$idx needs to be set!');
            return false;
        }

        $this->di_document_idx = $idx;
        return true;
    }

    public function setDocumentGuid($guid)
    {
        global $mtlda;

        if (!isset($guid) || empty($guid)) {
            $mtlda->raiseError(__METHOD__ .'(), \$guid needs to be set!');
            return false;
        }

        $this->di_document_guid = $guid;
        return true;
    }

    public function setDocumentText($text)
    {
        global $mtlda;

        if (!isset($text) || empty($text)) {
            $mtlda->raiseError(__METHOD__ .'(), \$text needs to be set!');
            return false;
        }

        $this->di_text = $text;
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
