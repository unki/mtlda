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

namespace Mtlda\Views;

class OptionsView extends DefaultView
{
    public $default_mode = 'show';
    public $class_name = 'options';

    public function show()
    {
        global $db, $query, $tmpl;

        if (isset($query) &&
            !empty($query) &&
            isset($query->params) &&
            !empty($query->params) &&
            is_array($query->params) &&
            isset($query->params[0]) &&
            !empty($query->params[0]) &&
            $query->params[0] == 'truncate'
        ) {
            return $this->truncate();
        }

        return $tmpl->fetch("options.tpl");
    }

    private function truncate()
    {
        global $mtlda, $db;

        if (!$db->truncateDatabaseTables()) {
            $mtlda->raiseError("DatabaseController::truncateDatabaseTables() returned false!");
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load StorageController!");
            return false;
        }

        if (!$storage->flushArchive()) {
            $mtlda->raiseError("StorageController::flushArchive() returned false!");
            return false;
        }

        if (!$storage->flushQueue()) {
            $mtlda->raiseError("StorageController::flushQueue() returned false!");
            return false;
        }

        try {
            $import = new \Mtlda\Controllers\ImportController;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load ImportController!");
            return false;
        }

        if (!$import->flush()) {
            $mtlda->raiseError("ImportController::flush() returned false!");
            return false;
        }

        return "Reset successful.";
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
