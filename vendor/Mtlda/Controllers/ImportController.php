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
                $imagectrl = new \Mtlda\Controllers\ImageController;
            } catch (\Exception $e) {
                $this->raiseError("Unable to load ImageController");
                return false;
            }
        }

        if ($config->isPdfSigningEnabled() &&
            (($sign_pos = $config->getPdfSigningIconPosition()) === false)
        ) {
            $this->raiseError("PDF-Signing is enabled but no signing-icon-position found!");
            return false;
        }

        try {
            $storage = new StorageController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load StorageController!");
            return false;
        }

        $files = array();

        if (!$this->scanDirectory($this::INCOMING_DIRECTORY, $files)) {
            $this->raiseError(__CLASS__ .'::scanDirectory() returned false!');
            return false;
        }

        if (!isset($files) || empty($files)) {
            return true;
        }

        foreach ($files as $file) {
            if (!($guid = $mtlda->createGuid())) {
                $this->raiseError(__METHOD__ ." no valid GUID returned by createGuid()!");
                return false;
            }

            try {
                $queueitem = new \Mtlda\Models\QueueItemModel;
            } catch (\Exception $e) {
                $this->raiseError("Unable to load QueueItemModel");
                return false;
            }

            $queueitem->queue_guid = $guid;
            $queueitem->queue_file_name = $file['filename'];
            $queueitem->queue_file_size = $file['size'];
            $queueitem->queue_file_hash = $file['hash'];
            $queueitem->queue_state = 'new';
            $queueitem->queue_time = microtime(true);

            if ($config->isPdfSigningEnabled()) {
                $queueitem->queue_signing_icon_position = $sign_pos;
            }

            $in_file = $file['fqpn'];
            $in_dir = dirname($in_file);

            if (!file_exists($in_file)) {
                $this->raiseError(__METHOD__ ."(), file {$in_file} does not exist!");
                return false;
            }

            if (!($dsc_file = preg_replace('/\.pdf$/i', '.dsc', $in_file))) {
                $this->raiseError(__METHOD__ .'(), preg_replace() returned false!');
                return false;
            }

            if (!$work_file = $queueitem->getFilePath()) {
                $this->raiseError("QueueItem::getFilePath() returned false!");
                return false;
            }

            if (isset($dsc_file) && !empty($dsc_file) && file_exists($dsc_file)) {
                if (!($description = file_get_contents($dsc_file))) {
                    $this->raiseError(__METHOD__ ."(), file_get_contents({$dsc_file}) returned false!");
                    return false;
                }
                if (isset($description) &&
                    !empty($description) &&
                    is_string($description)
                ) {
                    $queueitem->queue_description = $description;
                }
            }

            // create the target directory structure
            if (!$storage->createDirectoryStructure(dirname($work_file))) {
                $this->raiseError("StorageController::createDirectoryStructure() returned false!");
                return false;
            }

            if (copy($in_file, $work_file) === false) {
                $this->raiseError("copy({$in_file}, {$work_file}) returned false!");
                return false;
            }

            if (!$queueitem->save()) {
                $queueitem->delete();
                $this->raiseError("Saving QueueItemModel failed!");
                return false;
            }

            /*if (!unlink($in_file)) {
                $this->raiseError("Failed to remove {$in_file}!");
                return false;
            }*/

            if (!$this->unlinkDirectory($in_dir)) {
                $this->raiseError(__CLASS__ .'::unlinkDirectory() returned false!');
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
                $this->raiseError("json_encode() returned false!");
                return false;
            }

            try {
                $audit->log(
                    $json_str,
                    "import",
                    "queue",
                    $guid
                );
            } catch (\Exception $e) {
                $queueitem->delete();
                $this->raiseError("AuditController:log() returned false!");
                return false;
            }

            if ($config->isCreatePreviewImageOnImport()) {
                if (!$imagectrl->createPreviewImage($queueitem, false)) {
                    $this->raiseError("ImageController::savePreviewImage() returned false");
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
            $this->raiseError("Failed to access ". $this::INCOMING_DIRECTORY);
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
                    $this->raiseError(__CLASS__ .'::scanDirectory() returned false!');
                    return false;
                }
                continue;
            }

            $file = array();
            $file['filename'] = basename($item);
            $file['fqpn'] = $path .'/'. $file['filename'];

            // right now we only care about PDF files.
            if (!preg_match('/\.pdf$/i', $file['filename'])) {
                continue;
            }

            if (!file_exists($file['fqpn'])) {
                $this->raiseError("File {$file['fqpn']} does not exist!");
                return false;
            }

            if (!is_file($file['fqpn'])) {
                $this->raiseError("{$file['fqpn']} is not a file!");
                return false;
            }

            if (!is_readable($file['fqpn'])) {
                $this->raiseError("{$file['fqpn']} is not readable!");
                return false;
            }

            try {
                $audit->log(
                    "{$file['filename']}",
                    "new",
                    "queue"
                );
            } catch (\Exception $e) {
                $this->raiseError("AuditController::log() raised an exception!");
                return false;
            }

            if (($file['hash'] = sha1_file($file['fqpn'])) === false) {
                $this->raiseError("SHA1 value of {$file['fqpn']} can not be calculated!");
                return false;
            }

            clearstatcache(true, $file['fqpn']);

            if (($file['size'] = filesize($file['fqpn'])) === false) {
                $this->raiseError("Filesize of {$file['fqpn']} is not available!");
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
            $this->raiseError($this::INCOMING_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_dir($this::INCOMING_DIRECTORY)) {
            $this->raiseError($this::INCOMING_DIRECTORY ." is not a directory!");
            return false;
        }

        if (!$this->unlinkDirectory($this::INCOMING_DIRECTORY)) {
            $this->raiseError(__CLASS__ ."::unlinkDirectory() returned false!");
            return false;
        }

        return true;
    }

    private function unlinkDirectory($dir)
    {
        global $mtlda;

        if (($files = scandir($dir)) === false) {
            $this->raiseError("scandir on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {
            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                $this->raiseError("realpath() on ". $dir .'/'. $file ." returned false!");
                return false;
            }

            if (!$this->isBelowIncomingDirectory(dirname($fqfn))) {
                $this->raiseError("will only handle requested within ". $this::INCOMING_DIRECTORY ."!");
                return false;
            }

            if (is_dir($fqfn)) {
                if (!$this->unlinkDirectory($fqfn)) {
                    return false;
                }
            }

            if (!unlink($fqfn)) {
                $this->raiseError("unlink() on {$fqfn} returned false!");
                return false;
            }
        }

        if (!rmdir($dir)) {
            $this->raiseError("rmdir() on {$dir} returned false!");
            return false;
        }

        return true;
    }

    private function isBelowIncomingDirectory($dir)
    {
        global $mtlda;

        if (empty($dir)) {
            $this->raiseError("\$dir can not be empty!");
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
