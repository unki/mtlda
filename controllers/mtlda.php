<?php

namespace MTLDA\Controllers;

use MTLDA\Views;

class MTLDA
{
    public function __construct()
    {
        global $db, $query;

        $cfg = new ConfigController;

        $req = new RequirementsController;
        if (!$req->check()) {
            print "Error - not all MTLDA requirements are met. Please check!";
            exit(1);
        }

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

        $views = new ViewsController;
        $page_name = $views->getViewName($query->view);

        $view = $views->load($page_name);

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
