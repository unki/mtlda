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

use MTLDA\Controllers;
use MTLDA\Models;

class ImportController extends DefaultController
{
    public function __construct()
    {

    } // __construct()

    public function __destruct()
    {

    } // _destruct()

    public function handleQueue()
    {
        global $mtlda, $config, $audit;

        if ($config->isCreatePreviewImageOnImport()) {
            try {
                $imagectrl = new Controllers\ImageController;
            } catch (\Exception $e) {
                $mtlda->raiseError("Unable to load ImageController");
                return false;
            }
        }

        if (
            $config->isPdfSigningEnabled() &&
            (($sign_pos = $config->getPdfSigningIconPosition()) === false)
        ) {
            $mtlda->raiseError("PDF-Signing is enabled but no signing-icon-position found!");
            return false;
        }

        $files = array();

        if (!$this->scanDirectory($this::INCOMING_DIRECTORY, $files)) {
            $mtlda->raiseError(__CLASS__ .'::scanDirectory() returned false!');
            return false;
        }

        if (!isset($files) || empty($files)) {
            return true;
        }

        foreach ($files as $file) {

            if (!($file['guid'] = $mtlda->createGuid())) {
                $mtdla->raiseError(__TRAIT__ ." no valid GUID returned by createGuid()!");
                return false;
            }

            try {
                $queueitem = new Models\QueueItemModel;
            } catch (Exception $e) {
                $mtlda->raiseError("Unable to load QueueItemModel");
                return false;
            }

            $queueitem->queue_guid = $file['guid'];
            $queueitem->queue_file_name = $file['filename'];
            $queueitem->queue_file_size = $file['size'];
            $queueitem->queue_file_hash = $file['hash'];
            $queueitem->queue_state = 'new';
            $queueitem->queue_time = microtime(true);

            if ($config->isPdfSigningEnabled()) {
                $queueitem->queue_signing_icon_position = $sign_pos;
            }

            if (!$queueitem->save()) {
                $queueitem->delete();
                $mtlda->raiseError("Saving QueueItemModel failed!");
                return false;
            }

            $in_file = $file['fqpn'];
            $work_file = $this::WORKING_DIRECTORY .'/'. $file['filename'];

            if (rename($in_file, $work_file) === false) {
                $queueitem->delete();
                $mtdla->raiseError("Rename {$in_file} to {$work_file} failed!");
                return false;
            }

            $json_str = json_encode(
                array(
                    'file_name' => $file['filename'],
                    'file_size' => $file['size'],
                    'file_hash' => $file['hash'],
                    'state' => 'new'
                )
            );

            if (!$json_str) {
                $queueitem->delete();
                $mtlda->raiseError("json_encode() returned false!");
                return false;
            }

            try {
                $audit->log(
                    $json_str,
                    "import",
                    "queue",
                    $file['guid']
                );
            } catch (Exception $e) {
                $queueitem->delete();
                $mtlda->raiseError("AuditController:log() returned false!");
                return false;
            }

            if ($config->isCreatePreviewImageOnImport()) {
                if (!$imagectrl->createPreviewImage($queueitem, false)) {
                    $mtlda->raiseError("ImageController::savePreviewImage() returned false");
                    $queueitem->delete();
                    return false;
                }
            }
        }

        return true;
    }

    public function scanDirectory($path, &$files)
    {
        global $mtlda, $audit;

        if (($dir = opendir($path)) === false) {
            $mtlda->raiseError("Failed to access ". $this::INCOMING_DIRECTORY);
            return false;
        }

        while ($item = readdir($dir)) {

            // ignore special files and if $file is empty
            if (empty($item) || $item == '.' || $item == '..') {
                continue;
            }

            // ignore file/directories starting with . (aka hidden)
            if (preg_match("/^\..*/", $item)) {
                continue;
            }

            if (is_dir($path .'/'. $item)) {
                if (!$this->scanDirectory($path .'/'. $item, $files)) {
                    $mtlda->raiseError(__CLASS__ .'::scanDirectory() returned false!');
                    return false;
                }
                continue;
            }

            $file = array();
            $file['filename'] = basename($item);
            $file['fqpn'] = $path .'/'. $file['filename'];


            if (!file_exists($file['fqpn'])) {
                $mtdla->raiseError("File {$file['fqpn']} does not exist!");
                return false;
            }

            if (!is_file($file['fqpn'])) {
                $mtlda->raiseError("{$file['fqpn']} is not a file!");
                return false;
            }

            if (!is_readable($file['fqpn'])) {
                $mtdla->raiseError("{$file['fqpn']} is not readable!");
                return false;
            }

            try {
                $audit->log(
                    "{$file['filename']}",
                    "new",
                    "queue"
                );
            } catch (\Exception $e) {
                $mtlda->raiseError("AuditController::log() raised an exception!");
                return false;
            }

            if (($file['hash'] = sha1_file($file['fqpn'])) === false) {
                $mtdla->raiseError("SHA1 value of {$file['fqpn']} can not be calculated!");
                return false;
            }

            if (($file['size'] = filesize($file['fqpn'])) === false) {
                $mtdla->raiseError("Filesize of {$file['fqpn']} is not available!");
                return false;
            }

            array_push($files, $file);
        }

        return true;
    }

    public function flush()
    {
        global $mtlda;

        if (!file_exists($this::INCOMING_DIRECTORY)) {
            $mtlda->raiseError($this::INCOMING_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_dir($this::INCOMING_DIRECTORY)) {
            $mtlda->raiseError($this::INCOMING_DIRECTORY ." is not a directory!");
            return false;
        }

        if (!$this->unlinkDirectory($this::INCOMING_DIRECTORY)) {
            $mtlda->raiseError(__CLASS__ ."::unlinkDirectory() returned false!");
            return false;
        }

        return true;
    }

    private function unlinkDirectory($dir)
    {
        global $mtlda;

        if (($files = scandir($dir)) === false) {
            $mtlda->raiseError("scandir on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {

            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                $mtlda->raiseError("realpath() on ". $dir .'/'. $file ." returned false!");
                return false;
            }

            if (!$this->isBelowIncomingDirectory(dirname($fqfn))) {
                $mtlda->raiseError("will only handle requested within ". $this::INCOMING_DIRECTORY ."!");
                return false;
            }

            if (is_dir($fqfn)) {
                if (!$this->unlinkDirectory($fqfn)) {
                    return false;
                }
            }

            if (!unlink($fqfn)) {
                $mtlda->raiseError("unlink() on {$fqfn} returned false!");
                return false;
            }
        }

        if (!rmdir($dir)) {
            $mtlda->raiseError("rmdir() on {$dir} returned false!");
            return false;
        }

        return true;
    }

    private function isBelowIncomingDirectory($dir)
    {
        global $mtlda;

        if (empty($dir)) {
            $mtlda->raiseError("\$dir can not be empty!");
            return false;
        }

        $dir = strtolower(realpath($dir));
        $dir_top = strtolower(realpath($this::INCOMING_DIRECTORY));

        $dir_top_reg = preg_quote($dir_top, '/');

        // check if $dir is within $dir_top
        if (!preg_match('/^'. preg_quote($dir_top, '/') .'/', $dir)) {
            return false;
        }

        if ($dir == $dir_top) {
            return true;
        }

        $cnt_dir = count(explode('/', $dir));
        $cnt_dir_top = count(explode('/', $dir_top));

        if ($cnt_dir > $cnt_dir_top) {
            return true;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
