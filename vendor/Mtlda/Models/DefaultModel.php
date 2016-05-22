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

namespace Mtlda\Models;

abstract class DefaultModel extends \Thallium\Models\DefaultModel
{
    public function hasName()
    {
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), model has no fields defined!');
            return false;
        }

        if (static::hasField('name')) {
            $name_field = 'name';
        } elseif (static::hasField('file_name')) {
            $name_field = 'file_name';
        } else {
            static::raiseError(__METHOD__ .'(), have no clue from which field I can get the name from!');
            return false;
        }

        if (!$this->hasFieldValue($name_field)) {
            return false;
        }

        return true;
    }

    public function getName()
    {
        if (!$this->hasName()) {
            static::raiseError(__CLASS__ .'::hasName() returned false!');
            return false;
        }

        if (static::hasField('name')) {
            $name_field = 'name';
        } elseif (static::hasField('file_name')) {
            $name_field = 'file_name';
        } else {
            static::raiseError(__METHOD__ .'(), have no clue from which field I can get the name from!');
            return false;
        }

        if (($name = $this->getFieldValue($name_field)) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $name;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
