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

use MTLDA\Models;

class UploadController extends DefaultController
{
    public function __construct()
    {
        global $mtlda, $session;

        if (!$session) {
            $mtlda->raiseError(__TRAIT__ ." requires SessionController to be initialized!");
            return false;
        }
    }

    public function perform()
    {
        global $mtlda;

        if (!isset($_FILES) || empty($_FILES) || !is_array($_FILES)) {
            return true;
        }

        foreach ($_FILES as $file) {
            if (!$this->handleUploadedFile($file)) {
                $mtlda->raiseError("UploadController::handleUploadedFile() returned false!");
                return false;
            }
        }

        $mtlda->loadController("Incoming", "incoming");
        global $incoming;

        if (!$incoming->handleQueue()) {
            $this->raiseError("IncomingController::handleQueue returned false!");
            return false;
        }

        unset($incoming);

        print "ok";
        return true;
    }

    public function handleUploadedFile($file)
    {
        global $mtlda;

        if (empty($file) || !is_array($file)) {
            $mtlda->raiseError("\$file is invalid!");
            return false;
        }

        if (isset($file['error']) && $file['error'] != 0) {
            $mtlda->raiseError("\$file has been marked erroneous ({$file['error']})!");
            return false;
        }

        if (!isset($file['name']) || empty($file['name'])) {
            $mtlda->raiseError("no file name has been provided!");
            return false;
        }

        if (!isset($file['size']) || empty($file['size'])) {
            $mtlda->raiseError("no valid faile size ({$file['size']}) has been provided!");
            return false;
        }

        if (!preg_match("/\.pdf$/i", $file['name'])) {
            $mtlda->raiseError("only files with a suffix .pdf are supported!");
            return false;
        }

        if (isset($file['type']) && $file['type'] != "application/pdf") {
            $mtlda->raiseError("file type {$file['type']} is not supported!");
            return false;
        }

        if (!file_exists($file['tmp_name'])) {
            $mtlda->raiseError("uploaded file should be available at {$file['tmp_name']} but can not be found!");
            return false;
        }

        if (($filesize = filesize($file['tmp_name'])) === false || empty($filesize)) {
            $mtlda->raiseError("failed to detected file size of {$file['tmp_name']}");
            return false;
        }

        if (($filesize != $file['size'])) {
            $mtdla->raiseError(
                "provided upload size ({$file['size']}) is not equal the actual file size ({$filesize})!"
            );
            return false;
        }

        $dest = $this::INCOMING_DIRECTORY .'/'. $file['name'];

        if (file_exists($dest)) {
            $mtlda->raiseError("There is already a file in the queue with the same name!");
            return false;
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $mtlda->raiseError("Moving {$file['tmp_name']} to {$this->incoming_directory} failed!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
