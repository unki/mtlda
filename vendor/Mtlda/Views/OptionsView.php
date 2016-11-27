<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
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
    protected static $view_default_mode = 'show';
    protected static $view_class_name = 'options';

    public function __construct()
    {
        global $config, $tmpl;

        if ($config->isResetDataPermitted()) {
            $tmpl->assign('reset_data_is_permitted', true);
        }

        parent::__construct();
        return true;
    }

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
        global $db, $config;

        if (!$config->isResetDataPermitted()) {
            static::raiseError(get_class($config) .'::isResetDataPermitted() returned false!');
            return false;
        }

        if (!$db->truncateDatabaseTables()) {
            static::raiseError(get_class($db) .'::truncateDatabaseTables() returned false!');
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load StorageController!');
            return false;
        }

        if (!$storage->flushArchive()) {
            static::raiseError(get_class($storage) .'::flushArchive() returned false!');
            return false;
        }

        if (!$storage->flushQueue()) {
            static::raiseError(get_class($storage) .'::flushQueue() returned false!');
            return false;
        }

        try {
            $queue = new \Mtlda\Models\QueueModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load QueueModel!');
            return false;
        }

        if (!$queue->flush()) {
            static::raiseError(get_class($queue) .'::flush() returned false!');
            return false;
        }

        return "Reset successful.";
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
