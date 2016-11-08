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

class UploadController extends DefaultController
{
    public function __construct()
    {
        global $session, $config;

        if (!$config->isHttpUploadEnabled()) {
            static::raiseError(__CLASS__ .', HTTP upload is not enabled in configuration!', true);
            return false;
        }

        if (!$session) {
            static::raiseError(__CLASS__ .', requires SessionController to be initialized first!', true);
            return false;
        }

        return true;
    }

    public function perform()
    {
        global $mtlda, $jobs;

        if (!isset($_FILES) || empty($_FILES) || !is_array($_FILES)) {
            static::raiseError("\$_FILES is empty");
            return false;
        }

        if (!isset($_FILES['mtlda_upload']) ||
            empty($_FILES['mtlda_upload'] ||
            !is_array($_FILES['mtlda_upload']))
        ) {
            static::raiseError("\$_FILES['mtlda_upload'] is empty");
            return false;
        }

        $upload = $_FILES['mtlda_upload'];

        if (empty($upload['name']) || !is_array($upload['name'])) {
            static::raiseError("noramally 'name' should be an array!");
            return false;
        }

        foreach (array_keys($upload['name']) as $file_id) {
            if (!isset(
                $upload['name'][$file_id],
                $upload['type'][$file_id],
                $upload['tmp_name'][$file_id],
                $upload['error'][$file_id],
                $upload['size'][$file_id]
            )) {
                static::raiseError("\$upload[{$file_id}] is incomplete!");
                return false;
            }

            $file = array(
                'name'      => $upload['name'][$file_id],
                'type'      => $upload['type'][$file_id],
                'tmp_name'  => $upload['tmp_name'][$file_id],
                'error'     => $upload['error'][$file_id],
                'size'      => $upload['size'][$file_id]
            );

            if (!$this->handleUploadedFile($file)) {
                static::raiseError("UploadController::handleUploadedFile() returned false!");
                return false;
            }
        }

        if (!$jobs->createJob('import-request')) {
            static::raiseError(get_class($jobs) .'::createJob() returned false!');
            return false;
        }

        print "ok";
        return true;
    }

    public function handleUploadedFile($file)
    {
        if (empty($file) || !is_array($file)) {
            static::raiseError("\$file is invalid!");
            return false;
        }

        if (isset($file['error']) && $file['error'] != 0) {
            if (($error_message = $this->getUploadErrorMessage($file['error'])) === false) {
                $error_message = 'An unspecific error occured!';
            }
            static::raiseError("\$file has been marked erroneous ({$error_message})!");
            return false;
        }

        if (!isset($file['name']) || empty($file['name'])) {
            static::raiseError("no file name has been provided!");
            return false;
        }

        if (!isset($file['size']) || empty($file['size'])) {
            static::raiseError("no valid faile size ({$file['size']}) has been provided!");
            return false;
        }

        if (!preg_match("/\.pdf$/i", $file['name'])) {
            static::raiseError("only files with a suffix .pdf are supported!");
            return false;
        }

        if (isset($file['type']) &&
            !empty($file['type']) &&
            strtolower($file['type']) !== 'application/pdf' &&
            strtolower($file['type']) !== 'application/octet-stream' &&
            strtolower($file['type']) !== 'application/x-octet-stream'
        ) {
            static::raiseError("file type {$file['type']} is not supported!");
            return false;
        }

        if (!file_exists($file['tmp_name'])) {
            static::raiseError("uploaded file should be available at {$file['tmp_name']} but can not be found!");
            return false;
        }

        clearstatcache(true, $file['tmp_name']);

        if (($filesize = filesize($file['tmp_name'])) === false || empty($filesize)) {
            static::raiseError("failed to detected file size of {$file['tmp_name']}");
            return false;
        }

        if (($filesize != $file['size'])) {
            static::raiseError(
                "provided upload size ({$file['size']}) is not equal the actual file size ({$filesize})!"
            );
            return false;
        }

        $dest = $this::INCOMING_DIRECTORY .'/'. $file['name'];
        $dest_queue = $this::WORKING_DIRECTORY .'/'. $file['name'];

        if (file_exists($dest)) {
            static::raiseError("A file with the name {$file['name']} is already present in the incoming directory!");
            return false;
        }

        if (file_exists($dest_queue)) {
            static::raiseError("An item with the name {$file['name']} is already queued!");
            return false;
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            static::raiseError("Strangly is_uploaded_file() reports that {$file['tmp_name']} is not an uploaded file!");
            return false;
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            static::raiseError("Moving {$file['tmp_name']} to ". $this::INCOMING_DIRECTORY ." failed!");
            return false;
        }

        return true;
    }

    public function getUploadErrorMessage($error_code)
    {
        if (!isset($error_code) ||
            empty($error_code) ||
            !is_integer($error_code)
        ) {
            return false;
        }

        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Uploaded file\'s filesize is larger than upload_max_filesize limit in php.ini!';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                return 'Uploaded file\'s filesize is larger than given HTML form MAX_FILE_SIZE!';
                break;
            case UPLOAD_ERR_PARTIAL:
                return 'File has not been uploaded completely!';
                break;
            case UPLOAD_ERR_NO_FILE:
                return 'No file has been uploaded!';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Upload tmp directory does not exist!';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                return 'Uploaded file can not be stored in filesystem!';
                break;
            case UPLOAD_ERR_EXTENSION:
                return 'An PHP extension has interrupted upload process!';
                break;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
