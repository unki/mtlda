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
        if (!static::hasFields()) {
            static::raiseError(__METHOD__ .'(), model has no fields defined!');
            return false;
        }

        if (static::hasField('name')) {
            $name_field = static::column('name');
            return $this->getName();
        }

        if (static::hasField('file_name')) {
            $file_field = static::column('file_name');
            return $this->getFileName();
        }

        static::raiseError(__METHOD__ .'(), no clue where to get the name from for '. get_called_class() .'!');
        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
