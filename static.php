<?php

define('BASE_PATH', __DIR__);

function __autoload($class)
{
    $class = str_replace("\\", "/", $class);
    $parts = explode('/', $class);

    if (!is_array($parts) || empty($parts)) {
        print "Error - failed to extract class names!";
        exit(1);
    }

    if ($parts[0] != "MTLDA") {
        print "Error - are you trying to fooling me?";
        exit(1);
    }

    array_shift($parts);

    $filename = BASE_PATH;
    $filename.= "/";
    $filename.= implode('/', $parts);
    $filename = strtolower($filename);
    $filename.= '.php';

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
