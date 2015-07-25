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

namespace MTLDA\Controllers;

use PDO;

class RequirementsController
{
    public function __construct()
    {
        if (!constant('MTLDA_BASE')) {
            print "Erorr - MTLDA_BASE is not defined!";
            exit(1);
        }
    }

    public function check()
    {
        global $mtlda, $config;

        $missing = false;

        if (!($dbtype = $config->getDatabaseType())) {
            $mtlda->raiseError("Error - incomplete configuration found, can not check requirements!");
            exit(1);
        }

        switch ($dbtype) {
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
            $mtlda->write("Error - unsupported database configuration, can not check requirements!");
            $missing = true;
        }

        if (!class_exists($db_class_name)) {
            $mtlda->write("PHP {$dbtype} extension is missing!");
            $missing = true;
        }

        // check for PDO database support support
        if ((array_search($db_pdo_name, PDO::getAvailableDrivers())) === false) {
            $mtlda->write("PDO {$db_pdo_name} support not available");
            $missing = true;
        }

        ini_set('track_errors', 1);
        @include_once MTLDA_BASE.'/extern/tcpdf/tcpdf.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            $mtlda->write("TCPDF can not be found!");
            $missing = true;
            unset($php_errormsg);
        }
        @include_once MTLDA_BASE ."/extern/fpdi/fpdi.php";
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            $mtlda->write("FPDI can not be found!");
            $missing = true;
            unset($php_errormsg);
        }
        if (!class_exists('imagick')) {
            $mtlda->write("imagick extension is missing!");
            $missing = true;
        }
        /*@include_once 'Pager.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            print "PEAR Pager package is missing<br />\n";
            $missing = true;
            unset($php_errormsg);
        }*/
        @include_once 'smarty3/Smarty.class.php';
        if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
            $mtlda->write("Smarty3 template engine is missing!");
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
