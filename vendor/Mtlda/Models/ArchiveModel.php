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

class ArchiveModel extends DefaultModel
{
    protected static $model_table_name = 'archive';
    protected static $model_column_prefix = 'document';
    protected static $model_has_items = true;
    protected static $model_items_model = 'DocumentModel';

    protected function load($bla = null)
    {
        $extend_where_query = "
                document_version LIKE 1
            AND (
                document_deleted <> 'Y'
            OR
                document_deleted IS NULL
            )";

        return parent::load($extend_where_query);
    }

    public function getExpiredDocuments()
    {
        if (!$this->hasItems()) {
            return array();
        }

        $expired = array();
        $current_date = time();

        if (empty($current_date) && !is_numeric($current_date)) {
            static::raiseError(__METHOD__ .'(), failed to get current date!');
            return false;
        }

        if (($items = $this->getItems()) === false) {
            static::raiseError(__CLASS__ .'::getItems() returned false!');
            return false;
        }

        foreach ($items as $document) {
            if (!$document->hasExpiryDate()) {
                continue;
            }

            if (($expiry_date = $document->getExpiryDate()) === false) {
                static::raiseError(get_class($document) .'::getExpiryDate() returned false!');
                return false;
            }

            if (($expiry_date = strtotime($expiry_date)) === false) {
                static::raiseError(__METHOD__ .'(), strtotime() returned false!');
                return false;
            }

            if (empty($expiry_date) || !is_numeric($expiry_date)) {
                static::raiseError(__METHOD__ .'(), strotime() has not returned a valid timestamp!');
                return false;
            }

            if ($expiry_date > $current_date) {
                continue;
            }

            array_push($expired, $document);
        }

        return $expired;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
