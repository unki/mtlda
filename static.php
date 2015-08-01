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

function autoload($class)
{
    require_once "controllers/exception.php";

    $class = str_replace("\\", "/", $class);
    $parts = explode('/', $class);

    if (!is_array($parts) || empty($parts)) {
        error("failed to extract class names!");
        exit(1);
    }

    # only take care outloading of our namespace
    if ($parts[0] != "MTLDA") {
        return;
    }

    // remove leading 'MTLDA'
    array_shift($parts);

    // remove *Controller from ControllerName
    if (preg_match('/^(.*)Controller$/', $parts[1])) {
        $parts[1] = preg_replace('/^(.*)Controller$/', '$1', $parts[1]);
    }
    // remove *View from ViewName
    if (preg_match('/^(.*)View$/', $parts[1])) {
        $parts[1] = preg_replace('/^(.*)View$/', '$1', $parts[1]);
    }
    // remove *Model from ModelName
    if (preg_match('/^(.*)Model$/', $parts[1])) {
        $parts[1] = preg_replace('/^(.*)Model$/', '$1', $parts[1]);
    }

    $filename = MTLDA_BASE;
    $filename.= "/";
    $filename.= implode('/', $parts);
    $filename.= '.php';
    $filename = strtolower($filename);

    if (!file_exists($filename)) {
        error("File ". $filename ." does not exist!");
        exit(1);
    }
    if (!is_readable($filename)) {
        error("File ". $filename ." is not readable!");
        exit(1);
    }

    require_once $filename;
}

function error($string)
{
    print "<br /><br />". $string ."<br /><br />\n";

    try {
        throw new MTLDA\Controllers\ExceptionController;
    } catch (ExceptionController $e) {
        print "<br /><br />\n";
        $this->write($e, LOG_WARNING);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
