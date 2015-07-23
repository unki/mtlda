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

function autoload($class)
{
    $class = str_replace("\\", "/", $class);
    $parts = explode('/', $class);

    if (!is_array($parts) || empty($parts)) {
        print "Error - failed to extract class names!";
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
        print "Error - file ". $filename ." does not exist!";
        exit(1);
    }
    if (!is_readable($filename)) {
        print "Error - file ". $filename ." is not readable!";
        exit(1);
    }

    require_once $filename;
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
