<?php

namespace MTLDA\Controllers;

class MTLDAController
{
    public function __construct()
    {
        global $db, $query;

        require_once BASE_PATH."/controllers/config.php";
        require_once BASE_PATH."/controllers/requirements.php";

        $cfg = new MTLDA_Config_Controller;

        $req = new MTLDA_Requirements_Controller;
        if (!$req->check()) {
            print "Error - not all MTLDA requirements are met. Please check!";
            exit(1);
        }

        require_once BASE_PATH."/controllers/templates.php";
        $db = new MTLDA_DB_Controller;

        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            print "Error - \$_SERVER['REQUEST_URI'] is not set!";
            exit(1);
        }

        $router = new MTLDA_HTTP_Router_Controller;
        $query = $router->parse($_SERVER['REQUEST_URI']);

        if (!isset($query->view)) {
            print "Error - parsing request URI hasn't unveiled what to view!";
            exit(1);
        }

        $view = new MTLDA_View_Controller;
        $controller = $view->get_controller_name($query->view);
        print_r($controller);


    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
