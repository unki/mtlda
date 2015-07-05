<?php

namespace MTLDA\Controllers;

use MTLDA\Views;

class MTLDA
{
    public function __construct()
    {
        $GLOBALS['mtlda'] = &$this;

        $GLOBALS['cfg'] = new ConfigController;
        $req = new RequirementsController;
        $GLOBALS['db'] = new DbController;
        $GLOBALS['router'] = new HttpRouterController;

        global $cfg, $db, $router, $query;

        if (!$req->check()) {
            print "Error - not all MTLDA requirements are met. Please check!";
            exit(1);
        }

        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            print "Error - \$_SERVER['REQUEST_URI'] is not set!";
            exit(1);
        }

        $GLOBALS['query'] = $router->parse($_SERVER['REQUEST_URI']);

        global $query;

        if (!isset($query->view)) {
            print "Error - parsing request URI hasn't unveiled what to view!";
            exit(1);
        }

        $views = new ViewsController;
        $page_name = $views->getViewName($query->view);

        $view = $views->load($page_name);

    }

    public function raiseError($string)
    {
        if (defined('DB_NOERROR')) {
            $this->last_error = $string;
            return;
        }

        print "<br /><br />". $string ."<br /><br />\n";

        try {
            throw new MtldaExceptionController;
        } catch (MtldaExceptionController $e) {
            print "<br /><br />\n";
            $this->_print($e, MSLOG_WARN);
            die;
        }

        $this->last_error = $string;

    } // raiseError()
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
