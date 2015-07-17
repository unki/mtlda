<?php

require_once "static.php";

spl_autoload_register("autoload");

use MTLDA\Controllers as Controllers;

if (isset($_SERVER) && isset($_SERVER['argv'])) {

    if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'incoming') {
        $mtlda = new Controllers\MTLDA('queue_only');
        exit(0);
    }
}

$mtlda = new Controllers\MTLDA();
$mtlda->startup();

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
