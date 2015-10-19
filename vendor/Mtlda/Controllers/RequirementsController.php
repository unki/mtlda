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

namespace Mtlda\Controllers;

class RequirementsController extends \Thallium\Controllers\RequirementsController
{
    const DATA_DIRECTORY = APP_BASE ."/data";
    const ARCHIVE_DIRECTORY = self::DATA_DIRECTORY ."/archive";
    const INCOMING_DIRECTORY = self::DATA_DIRECTORY ."/incoming";
    const WORKING_DIRECTORY = self::DATA_DIRECTORY ."/working";
    const ARCHIVE_NESTING_DEPTH = 5;

    public function checkPhp()
    {
        global $mtlda, $config;

        $missing = false;

        if (!parent::checkPhp()) {
            $missing = true;
        }

        if (!(function_exists("curl_init"))) {
            $mtlda->raiseError("cURL support is missing!");
            $missing = true;
        }

        if ($config->isPdfSigningEnabled()) {
            if (!(function_exists("openssl_pkey_get_private"))) {
                $mtlda->raiseError("OpenSSL support is missing!");
                $missing = true;
            }

            if (!class_exists("SoapClient")) {
                $mtlda->raiseError("SOAP support is missing!");
                $missing = true;
            }
        }

        if ($config->isMailImportEnabled()) {
            if (!function_exists("imap_open")) {
                $mtlda->raiseError("IMAP extension is missing (also provides POP3 support)!");
                $missing = true;
            }
        }

        if ($missing) {
            return false;
        }

        return true;
    }

    public function checkDatabaseSupport()
    {
        global $mtlda, $config;

        $missing = false;

        if (!($dbtype = $config->getDatabaseType())) {
            $mtlda->raiseError("Error - incomplete configuration found, can not check requirements!");
            return false;
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
            $mtlda->write("Error - unsupported database configuration, can not check requirements!", LOG_ERR);
            $missing = true;
        }

        if (!class_exists($db_class_name)) {
            $mtlda->write("PHP {$dbtype} extension is missing!", LOG_ERR);
            $missing = true;
        }

        // check for PDO database support support
        if ((array_search($db_pdo_name, \PDO::getAvailableDrivers())) === false) {
            $mtlda->write("PDO {$db_pdo_name} support not available", LOG_ERR);
            $missing = true;
        }

        if ($missing) {
            return false;
        }

        return true;
    }

    public function checkExternalLibraries()
    {
        global $mtlda, $config;

        $missing = false;

        ini_set('track_errors', 1);

        if (!parent::checkExternalLibraries()) {
            $missing = true;
        }

        if ($config->isPdfSigningEnabled()) {
            @include_once APP_BASE.'/vendor/tcpdf/tcpdf.php';
            if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
                $mtlda->write("TCPDF can not be found!", LOG_ERR);
                $missing = true;
                unset($php_errormsg);
            }
            @include_once APP_BASE ."/vendor/fpdi/fpdi.php";
            if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
                $mtlda->write("FPDI can not be found!", LOG_ERR);
                $missing = true;
                unset($php_errormsg);
            }
        }

        if ($config->isPdfIndexingEnabled()) {
            @include_once APP_BASE ."/vendor/Smalot/PdfParser/Parser.php";
            if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
                $mtlda->write("PdfParser can not be found!", LOG_ERR);
                $missing = true;
                unset($php_errormsg);
            }
            @include_once APP_BASE.'/vendor/tcpdf/tcpdf_parser.php';
            if (isset($php_errormsg) && preg_match('/Failed opening.*for inclusion/i', $php_errormsg)) {
                $mtlda->write("TCPDF_PARSER can not be found!", LOG_ERR);
                $missing = true;
                unset($php_errormsg);
            }
        }

        ini_restore('track_errors');

        if (!class_exists('imagick')) {
            $mtlda->write("imagick extension is missing!", LOG_ERR);
            $missing = true;
        }

        if ($missing) {
            return false;
        }

        return true;
    }

    public function checkDirectoryPermissions()
    {
        global $mtlda;
        $missing = false;

        if (!$uid = $mtlda->getProcessUserId()) {
            $mtlda->raiseError("Mtlda::getProcessUserId() returned false!");
            return false;
        }

        if (!$gid = $mtlda->getProcessGroupId()) {
            $mtlda->raiseError("Mtlda::getProcessGroupId() returned false!");
            return false;
        }

        $directories = array(
            self::CONFIG_DIRECTORY => 'r',
            self::CACHE_DIRECTORY => 'w',
            self::ARCHIVE_DIRECTORY => 'w',
            self::INCOMING_DIRECTORY => 'w',
            self::WORKING_DIRECTORY => 'w',
            self::CACHE_DIRECTORY.'/image_cache' => 'w',
        );

        if (!file_exists(self::DATA_DIRECTORY)) {
            $mtlda->raiseError(self::DATA_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_writeable(self::DATA_DIRECTORY)) {
            $mtlda->raiseError(self::DATA_DIRECTORY ." is not writeable for {$uid}:{$gid}!");
            return false;
        }

        foreach ($directories as $dir => $perm) {
            if (!file_exists($dir) && !mkdir($dir, 0700)) {
                $mtlda->write("failed to create {$dir} directory!", LOG_ERR);
                $missing = true;
                continue;
            }

            if (file_exists($dir) && !is_readable($dir)) {
                $mtlda->write("{$dir} is not readable for {$uid}:{$gid}!", LOG_ERR);
                $missing = true;
                continue;
            }

            if (file_exists($dir) && $perm == 'w' && !is_writeable($dir)) {
                $mtlda->write("{$dir} is not writeable for {$uid}:{$gid}!", LOG_ERR);
                $missing = true;
                continue;
            }
        }

        if ($missing) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
