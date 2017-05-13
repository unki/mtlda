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

class PdfSignatureController extends DefaultController
{
    public function __construct()
    {
        global $config;

        if (!$config->isPdfSignatureVerificationEnabled()) {
            static::raiseError(__METHOD__ .'(), PDF-signature-verification is not enabled in config.ini!', true);
            return;
        }

        return;
    }

    public function verify($path)
    {
        $desc = array(
            0 => array('pipe','r'), /* STDIN */
            1 => array('pipe','w'), /* STDOUT */
            2 => array('pipe','w'), /* STDERR */
        );

        $retval = '';
        $error = '';

        if (($process = proc_open("/usr/bin/pdfsig {$path}", $desc, $pipes)) === false) {
            static::raiseError(__METHOD__ .'(), proc_open() returned false!');
            return false;
        }

        if (!is_resource($process)) {
            static::raiseError(__METHOD__ .'(), proc_open() has not returned a ressource!');
            return false;
        }

        $stdin = $pipes[0];
        $stdout = $pipes[1];
        $stderr = $pipes[2];

        while (!feof($stdout)) {
            $retval.= trim(fgets($stdout));
        }

        while (!feof($stderr)) {
            $error.= trim(fgets($stderr));
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit_code = proc_close($process);

        if (!empty($error)) {
            return null;
        }

        if (!preg_match('/File.+does not contain any signatures/i', $php_errormsg)) {
            return null;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
