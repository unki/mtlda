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
        global $mtlda, $db, $config, $audit;

        $sth = $db->prepare("
                INSERT INTO mtlda_queue (
                    queue_guid,
                    queue_file_name,
                    queue_file_size,
                    queue_file_hash,
                    queue_state,
                    queue_time
                    ) VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                        )
                ");

        if (( $importdir = opendir($this::INCOMING_DIRECTORY)) === false) {
            print "Error!: failed to access ". $this::INCOMING_DIRECTORY;
            die();
        }

        if ($config->isCreatePreviewImageOnImport()) {

            try {
                $imagectrl = new Controllers\ImageController;
            } catch (Exception $e) {
                $mtlda->raiseError("Unable to load ImageController");
                return false;
            }
        }

        while ($file = readdir($importdir)) {

            $in_file = $this::INCOMING_DIRECTORY ."/". $file;
            $work_file = $this::WORKING_DIRECTORY ."/". $file;

            // ignore special files and if $file is empty
            if (empty($file) || $file == '.' || $file == '..') {
                continue;
            }

            // ignore file/directories starting with .
            if (preg_match("/^\..*/", $file)) {
                continue;
            }

            if (!file_exists($in_file)) {
                $mtdla->raiseError(__TRAIT__ ." file {$in_file} does not exist!");
                return false;
            }

            if (!is_file($in_file)) {
                $mtlda->raiseError(__TRAIT__ ." {$in_file} is no file!");
                return false;
            }

            if (!is_readable($in_file)) {
                $mtdla->raiseError(__TRAIT__ ." {$in_file} is not readable!");
                return false;
            }

            try {
                $audit->log(
                    "{$in_file}",
                    "new",
                    "queue"
                );
            } catch (Exception $e) {
                $mtlda->raiseError("AuditController::log() raised an exception!");
                return false;
            }

            if (($hash = sha1_file($in_file)) === false) {
                $mtdla->raiseError(__TRAIT__ ." SHA1 value of {$in_file} can not be calculated!");
                return false;
            }

            if (($size = filesize($in_file)) === false) {
                $mtdla->raiseError(__TRAIT__ ." filesize of {$in_file} is not available!");
                return false;
            }

            if (!($guid = $mtlda->createGuid())) {
                $mtdla->raiseError(__TRAIT__ ." no valid GUID returned by createGuid()!");
                return false;
            }

            try {
                $queueitem = new Models\QueueItemModel;
            } catch (Exception $e) {
                $mtlda->raiseError("Unable to load QueueItemModel");
                return false;
            }

            $queueitem->queue_guid = $guid;
            $queueitem->queue_file_name = $file;
            $queueitem->queue_file_size = $size;
            $queueitem->queue_file_hash = $hash;
            $queueitem->queue_state = 'new';
            $queueitem->queue_time = microtime(true);

            if ($config->isPdfSigningEnabled()) {

                if ($pos = $config->getPdfSigningIconPosition()) {
                    $queueitem->queue_signing_icon_position = $pos;
                } else {
                    $mtlda->raiseError(__TRAIT__ ." PDF-Signing is enabled but no signing-icon-position found!");
                    return false;
                }
            }

            if (!$queueitem->save()) {
                $queueitem->delete();
                $mtlda->raiseError(__TRAIT__ ." saving QueueItemModel failed!");
                return false;
            }

            if (rename($in_file, $work_file) === false) {
                $queueitem->delete();
                $mtdla->raiseError(__TRAIT__ ." rename {$in_file} to {$work_file} failed!");
                return false;
            }

            $json_str = json_encode(
                array(
                    'file_name' => $file,
                    'file_size' => $size,
                    'file_hash' => $hash,
                    'state' => 'state'
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
                    $guid
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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
