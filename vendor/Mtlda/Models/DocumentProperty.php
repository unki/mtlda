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

class DocumentPropertyModel extends DefaultModel
{
    public $table_name = 'document_properties';
    public $column_name = 'dp';
    public $fields = array(
        'dp_idx' => 'integer',
        'dp_guid' => 'string',
        'dp_document_idx' => 'integer',
        'dp_document_guid' => 'string',
        'dp_property' => 'string',
        'dp_value' => 'string',
    );

    public function setDocumentIdx($idx)
    {
        global $mtlda;

        if (!isset($idx) || empty($idx)) {
            $mtlda->raiseError(__METHOD__ .'(), \$idx needs to be set!');
            return false;
        }

        $this->dp_document_idx = $idx;
        return true;
    }

    public function setDocumentGuid($guid)
    {
        global $mtlda;

        if (!isset($guid) || empty($guid)) {
            $mtlda->raiseError(__METHOD__ .'(), \$guid needs to be set!');
            return false;
        }

        $this->dp_document_guid = $guid;
        return true;
    }

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
    public function getDocumentIdx()
    {
        global $mtlda;

        if (!isset($this->dp_document_idx)) {
            return false;
        }

        return $this->dp_document_idx;
    }

    public function getDocumentGuid()
    {
        global $mtlda;

        if (!isset($this->dp_document_guid)) {
            return false;
        }

        return $this->dp_document_guid;
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

    protected function clear()
    {
        global $mtlda, $db;

        if (
            !($idx = $this->getDocumentIdx()) ||
            !($guid = $this->getDocumentGuid())
        ) {
            return true;
        }

        $sql =
            "DELETE FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                {$this->column_name}_document_idx LIKE ?
            AND
                {$this->column_name}_document_guid LIKE ?";

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError(__METHOD__ .'(), failed to prepare query!');
            return false;
        }

        if (!($db->execute($sth, array($idx, $guid)))) {
            $db->freeStatement($sth);
            $mtlda->raiseError(__METHOD__ .'(), failed to execute query!');
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
