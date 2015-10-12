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

define('MTLDA_BASE', __DIR__);

define('SIGN_TOP_LEFT', 1);
define('SIGN_TOP_CENTER', 2);
define('SIGN_TOP_RIGHT', 3);
define('SIGN_MIDDLE_LEFT', 4);
define('SIGN_MIDDLE_CENTER', 5);
define('SIGN_MIDDLE_RIGHT', 6);
define('SIGN_BOTTOM_LEFT', 7);
define('SIGN_BOTTOM_CENTER', 8);
define('SIGN_BOTTOM_RIGHT', 9);

if (!constant('LOG_ERR')) {
    define('LOG_ERR', 1);
}
if (!constant('LOG_WARNING')) {
    define('LOG_WARNING', 2);
}
if (!constant('LOG_INFO')) {
    define('LOG_INFO', 3);
}
if (!constant('LOG_DEBUG')) {
    define('LOG_DEBUG', 4);
}

function autoload($class)
{
    $prefixes = array(
        'Mtlda',
        'fpdi',
        'tcpdf',
    );

    $class = str_replace("\\", "/", $class);
    $parts = explode('/', $class);

    if (!is_array($parts) || empty($parts)) {
        return;
    }

    if ($parts[0] == 'Mtlda') {

        // remove leading 'Mtlda'
        //array_shift($parts);

        // remove *Controller from ControllerName
        if (preg_match('/^(.*)Controller$/', $parts[2])) {
            $parts[2] = preg_replace('/^(.*)Controller$/', '$1', $parts[2]);
        }
        // remove *View from ViewName
        if (preg_match('/^(.*)View$/', $parts[2])) {
            $parts[2] = preg_replace('/^(.*)View$/', '$1', $parts[2]);
        }
        // remove *Model from ModelName
        if (preg_match('/^(.*)Model$/', $parts[2])) {
            $parts[2] = preg_replace('/^(.*)Model$/', '$1', $parts[2]);
        }
    }

    $filename = MTLDA_BASE;
    $filename.= "/vendor/";
    if (isset($subdir) || !empty($subdir)) {
        $filename.= $subdir;
    }
    $filename.= implode('/', $parts);
    $filename.= '.php';

    if (!file_exists($filename)) {
        return;
    }
    if (!is_readable($filename)) {
        return;
    }

    require_once $filename;
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
