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

class QueueController extends DefaultController
{
    public function archive($id, $guid)
    {
        global $mtlda;

        if (empty($id) || !is_numeric($id)) {
            $mtlda->raiseError("id is invalid!");
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            $mtlda->raiseError("guid is invalid!");
            return false;
        }

        if (!($obj = $mtlda->loadModel("queueitem", $id, $guid))) {
            $mtlda->raiseError("Unable to load model for {$id}, {$guid}");
            return false;
        }

        if (!isset($obj->queue_file_hash) || empty($obj->queue_file_hash)) {
            $mtlda->raiseError("Found no file hash for QueueItemModel {$id}, {$guid}!");
            return false;
        }

        try {
            $archive = new ArchiveController;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load ArchiveController!");
            return false;
        }

        if (!$archive) {
            $mtlda->raiseError("Unable to load ArchiveController!");
            return false;
        }

        if (($dupl_item = $archive->checkForDuplicateFileByHash($obj->queue_file_hash)) === false) {
            $mtlda->raiseError("ArchiveController::checkForDuplicateFileByHash returned false!");
            return false;
        }

        if (!empty($dupl_item)) {
            $mtlda->raiseError("There is already an item with the same file hash in the archive!");
            return false;
        }

        try {
            $storage = new StorageController;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load StorageController!");
            return false;
        }

        if (!$storage) {
            $mtlda->raiseError("Unable to load StorageController!");
            return false;
        }

        if (!$storage->archive($obj)) {
            $mtlda->raiseError("StorageController::archive() exited with an error!");
            return false;
        }

        return true;
    }

    public function archiveAll()
    {
        global $mtlda;

        try {
            $queue = new Models\QueueModel;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load QueueModel");
            return false;
        }

        foreach ($queue->avail_items as $key) {
            $queueitem = $queue->items[$key];
            $idx = $queueitem->queue_idx;
            $guid = $queueitem->queue_guid;
            if (empty($idx) || !$mtlda->isValidGuidSyntax($guid)) {
                continue;
            }
            if (!$this->archive($idx, $guid)) {
                $mtlda->raiseArchive(__CLASS__ ."::archive() returned false for QueueItem {$idx}, {$guid}!");
                return false;
            }
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
