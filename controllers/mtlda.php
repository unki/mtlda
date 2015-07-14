<?php

namespace MTLDA\Controllers;

use MTLDA\Views;

class MTLDA
{
    private $verbosity_level = LOG_WARNING;

    public function __construct()
    {
        $GLOBALS['mtlda'] =& $this;

        $GLOBALS['cfg'] =& new ConfigController;
        $req = new RequirementsController;
        $GLOBALS['db'] =& new DbController;
        $GLOBALS['router'] =& new HttpRouterController;

        global $cfg, $db, $router, $query;

        if (!$req->check()) {
            $this->raiseError("Error - not all MTLDA requirements are met. Please check!");
            exit(1);
        }

        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            $this->raiseError("Error - \$_SERVER['REQUEST_URI'] is not set!");
            exit(1);
        }

        $GLOBALS['query'] = $router->parse($_SERVER['REQUEST_URI']);
        global $query;

        if (!isset($query->view)) {
            $this->raiseError("Error - parsing request URI hasn't unveiled what to view!");
            exit(1);
        }

        $views = new ViewsController;
        if (!$page_name = $views->getViewName($query->view)) {
            $this->raiseError("Unable to find a view for ". $query->view);
        }

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
            throw new ExceptionController;
        } catch (ExceptionController $e) {
            print "<br /><br />\n";
            $this->write($e, LOG_WARNING);
            die;
        }

        $this->last_error = $string;

    } // raiseError()

    public function write($text, $loglevel = LOG_INFO, $override_output = null, $no_newline = null)
    {
        if (isset($this->cfg->logging)) {
            $logtype = $this->cfg->logging;
        }

        if (!isset($this->cfg->logging)) {
            $logtype = 'display';
        }

        if (isset($override_output)) {
            $logtype = $override_output;
        }

        if ($this->getVerbosity() < $loglevel) {
            return true;
        }

        switch($logtype) {
            default:
            case 'display':
                print $text;
                if (!$this->isCmdline()) {
                    print "<br />";
                } elseif (!isset($no_newline)) {
                    print "\n";
                }
                break;
            case 'errorlog':
                error_log($text);
                break;
            case 'logfile':
                error_log($text, 3, $this->cfg->log_file);
                break;
        }

        return true;

    } // write()

    public function isCmdline()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        return false;

    } // isCmdline()

    public function setVerbosity($level)
    {
        if (!in_array($level, array(0 => LOG_INFO, 1 => LOG_WARNING, 2 => LOG_DEBUG))) {
            $this->raiseError("Unknown verbosity level ". $level);
        }

        $this->verbosity_level = $level;

    } // setVerbosity()

    public function getVerbosity()
    {
        return $this->verbosity_level;

    } // getVerbosity()
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
