<?php

namespace MTLDA\Controllers;

use MTLDA\Views;

class MTLDAController
{
    public function __construct()
    {
        global $db, $query;

        require_once BASE_PATH."/controllers/config.php";
        require_once BASE_PATH."/controllers/requirements.php";

        $cfg = new ConfigController;

        $req = new RequirementsController;
        if (!$req->check()) {
            print "Error - not all MTLDA requirements are met. Please check!";
            exit(1);
        }

        require_once BASE_PATH."/views/templates.php";
        $db = new DbController;

        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            print "Error - \$_SERVER['REQUEST_URI'] is not set!";
            exit(1);
        }

        $router = new HttpRouterController;
        $query = $router->parse($_SERVER['REQUEST_URI']);

        if (!isset($query->view)) {
            print "Error - parsing request URI hasn't unveiled what to view!";
            exit(1);
        }

        $view = new ViewController;
        $controller = $view->getViewName($query->view);
        print_r($controller);


    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
