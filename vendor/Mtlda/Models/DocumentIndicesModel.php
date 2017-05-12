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

class DocumentIndicesModel extends DefaultModel
{
    protected static $model_table_name = 'document_indices';
    protected static $model_column_prefix = 'di';
    protected static $model_has_items = true;
    protected static $model_items_model = 'DocumentIndexModel';

    public function hasIndices()
    {
        if (!isset($this->model_items) ||
            empty($this->model_items) ||
            count($this->model_items) < 1
        ) {
            return false;
        }

        return true;
    }

    public function getIndices()
    {
        if (!$this->hasIndices()) {
            static::raiseError(__METHOD__ .'(), no indices available!');
            return false;
        }

        return $this->model_items;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
