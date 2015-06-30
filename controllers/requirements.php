<?php

namespace MTLDA\Controllers;

use PDO;

class RequirementsController
{
    public function __construct()
    {
        if (!constant('BASE_PATH')) {
            print "Erorr - BASE_PATH is not defined!";
            exit(1);
        }
    }

    public function check()
    {
        global $config;

        $missing = false;

        if (!isset($config['database']) && !isset($config['database']['type'])) {
            print "Error - incomplete configuration found, can not check requirements!";
            exit(1);
        }

        switch($config['database']['type']) {
            case 'mysql':
                $db_class_name = "mysqli";
                $db_pdo_name = "mysql";
                break;
            case 'sqlite3':
                $db_class_name = "Sqlite3";
                $db_pdo_name = "sqlite";
                break;
            default:
                $db_class_name = null;
                $db_pdo_name = null;
                break;
        }

        if (!$db_class_name) {
            print "Error - unsupported database configuration, can not check requirements!";
            $missing = true;
        }

        if (!class_exists($db_class_name)) {
            print "PHP ". $config['database']['type'] ." extension is missing<br />\n";
            $missing = true;
        }

        // check for PDO database support support
        if ((array_search($db_pdo_name, PDO::getAvailableDrivers())) === false) {
            print "PDO ". $db_pdo_name ." support not available<br />\n";
            $missing = true;
        }

        ini_set('track_errors', 1);
        /*@include_once 'Net/IPv4.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "PEAR Net_IPv4 package is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }*/
        /*@include_once 'Pager.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "PEAR Pager package is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }*/
        @include_once 'smarty3/Smarty.class.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "Smarty3 template engine is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }

        ini_restore('track_errors');

        if (!empty($missing)) {
            return false;
        }

        return true;

    }
}


// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
