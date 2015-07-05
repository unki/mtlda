<?php

define('BASE_PATH', __DIR__);

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
    if (preg_match('/^(.*)View$/', $parts[1])) {
        $parts[1] = preg_replace('/^(.*)View$/', '$1', $parts[1]);
    }

    $filename = BASE_PATH;
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
