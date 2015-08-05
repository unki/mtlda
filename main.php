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

require_once "static.php";

spl_autoload_register("autoload");

use MTLDA\Controllers as Controllers;

if (
    isset($_SERVER) &&
    isset($_SERVER['argv']) &&
    isset($_SERVER['argv'][1]) &&
    $_SERVER['argv'][1] == 'incoming'
) {

    try {
        $mtlda = new Controllers\MTLDA('queue_only');
    } catch (Exception $e) {
        print $e->getMessage();
        exit(1);
    }
    exit(0);
}

try {
    $mtlda = new Controllers\MTLDA();
} catch (Exception $e) {
    print $e->getMessage();
    exit(1);
}

if (!$mtlda->startup()) {
    exit(1);
}

exit(0);

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
