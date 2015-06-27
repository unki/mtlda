<?php

require_once BASE_PATH."/controllers/config.php";
require_once BASE_PATH."/controllers/db.php";
require_once BASE_PATH."/controllers/http_router.php";


class MTLDA_Controller {

    public function __construct()
    {
        global $db, $query;

        $cfg = new MTLDA_Config_Controller;
        $db = new MTLDA_DB_Controller;

        if(!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            print "Error - \$_SERVER['REQUEST_URI'] is not set!";
            exit(1);
        }

        $router = new MTLDA_HTTP_Router_Controller;
        $query = $router->parse($_SERVER['REQUEST_URI']);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
