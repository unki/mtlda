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

abstract class DefaultModel extends \Thallium\Models\DefaultModel
{
    public function getName()
    {
        if (!isset($this->column_name) ||
            empty($this->column_name)
        ) {
            $this->raiseError(__METHOD__ .'(), can not continue without column name!');
            return false;
        }

        if (!isset($this->fields) ||
            empty($this->fields)
        ) {
            $this->raiseError(__METHOD__ .'(), model has no fields defined!');
            return false;
        }

        $name_field = $this->column_name .'_name';

        if (in_array($name_field, array_keys($this->fields))) {
            return $this->$name_field;
        }

        $file_field = $this->column_name .'_file_name';

        if (in_array($file_field, array_keys($this->fields))) {
            return $this->$file_field;
        }

        $this->raiseError(__METHOD__ .'(), no clue where to get the name from!');
        return false;
    }

    public function raiseError($string, $stop_execution = false, $exception = null)
    {
        global $mtlda;

        $mtlda->raiseError(
            $string,
            $stop_execution,
            $exception
        );

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
