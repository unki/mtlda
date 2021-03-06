<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017> <Andreas Unterkircher>
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
    public function check()
    {
        if (!parent::check()) {
            return false;
        }

        if (!$this->checkApplications()) {
            return false;
        }

        return true;
    }

    public function checkPhp()
    {
        global $mtlda, $config;

        $missing = false;

        if (!parent::checkPhp()) {
            $missing = true;
        }

        if (!(function_exists("curl_init"))) {
            static::raiseError("cURL support is missing!");
            $missing = true;
        }

        if ($config->isPdfSigningEnabled()) {
            if (!(function_exists("openssl_pkey_get_private"))) {
                static::raiseError("OpenSSL support is missing!");
                $missing = true;
            }

            if (!class_exists("SoapClient")) {
                static::raiseError("SOAP support is missing!");
                $missing = true;
            }
        }

        if ($config->isMailImportEnabled()) {
            if (!function_exists("imap_open")) {
                static::raiseError("IMAP extension is missing (also provides POP3 support)!");
                $missing = true;
            }
            if ($config->isUseEmailBodyAsDescription()) {
                if (!function_exists("mb_convert_encoding")) {
                    static::raiseError("Multibyte string support is missing!");
                    $missing = true;
                }
            }
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

        if (!parent::checkExternalLibraries()) {
            $missing = true;
        }

        ini_set('track_errors', 1);

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
            static::raiseError("Mtlda::getProcessUserId() returned false!");
            return false;
        }

        if (!$gid = $mtlda->getProcessGroupId()) {
            static::raiseError("Mtlda::getProcessGroupId() returned false!");
            return false;
        }

        $directories = array(
            \Mtlda\Controllers\DefaultController::CONFIG_DIRECTORY => 'r',
            \Mtlda\Controllers\DefaultController::CACHE_DIRECTORY => 'w',
            \Mtlda\Controllers\DefaultController::ARCHIVE_DIRECTORY => 'w',
            \Mtlda\Controllers\DefaultController::INCOMING_DIRECTORY => 'w',
            \Mtlda\Controllers\DefaultController::WORKING_DIRECTORY => 'w',
            \Mtlda\Controllers\DefaultController::CACHE_DIRECTORY.'/image_cache' => 'w',
        );

        if (!file_exists(\Mtlda\Controllers\DefaultController::DATA_DIRECTORY)) {
            static::raiseError(\Mtlda\Controllers\DefaultController::DATA_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_writeable(\Mtlda\Controllers\DefaultController::DATA_DIRECTORY)) {
            static::raiseError(
                \Mtlda\Controllers\DefaultController::DATA_DIRECTORY ." is not writeable for {$uid}:{$gid}!"
            );
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

    public function checkApplications()
    {
        global $mtlda, $config;
        $missing = false;

        if ($config->isPdfSignatureVerificationEnabled() && !file_exists('/usr/bin/pdfsig')) {
            $mtlda->write("Error - /usr/bin/pdfsig is missing!", LOG_ERR);
            $missing = true;
        }

        if ($missing) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
