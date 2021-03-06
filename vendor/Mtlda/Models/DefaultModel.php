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

namespace Mtlda\Models;

abstract class DefaultModel extends \Thallium\Models\DefaultModel
{
    public function hasName()
    {
        if (!static::hasModelFields()) {
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

    public function createClone($fields_override = array())
    {
        if (isset($fields_override) && !is_array($fields_override)) {
            static::raiseError(__METHOD__ .'(), $fields_override has to be an array!', true);
            return false;
        }

        if (method_exists($this, 'preClone') && is_callable(array($this, 'preClone'))) {
            if (!$this->preClone()) {
                static::raiseError(__CLASS__ .'::preClone() method returned false!', true);
                return;
            }
        }

        try {
            $tempItem = clone $this;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), clone of '. get_class($this) .' failed!', true);
            return false;
        }

        foreach ($fields_override as $field => $value) {
            if (!isset($field) || empty($field) || !is_string($field)) {
                static::raiseError(__METHOD__ .'(), $fields_override contains an invalid field!', true);
                return false;
            }

            if (!$tempItem->hasField($field)) {
                static::raiseError(__METHOD__ .'(), $fields_override refers an unknown field!', true);
                return false;
            }

            if (!$tempItem->setFieldValue($field, $value)) {
                static::raiseError(get_class($tempItem) .'::setFieldValue() returned false!', true);
                return false;
            }
        }

        if (method_exists($this, 'afterClone') && is_callable(array($this, 'afterClone'))) {
            if (!$this->afterClone($this, $tempItem)) {
                static::raiseError(__CLASS__ .'::afterClone() method returned false!', true);
                return;
            }
        }

        return $tempItem;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
