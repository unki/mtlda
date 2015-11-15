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

class QueueController extends DefaultController
{
    public function archive($id, $guid)
    {
        global $mtlda, $mbus;

        if (empty($id) || !is_numeric($id)) {
            $this->raiseError("id is invalid!");
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            $this->raiseError("guid is invalid!");
            return false;
        }

        if (!($obj = $mtlda->loadModel("queueitem", $id, $guid))) {
            $this->raiseError("Unable to load model for {$id}, {$guid}");
            return false;
        }

        if ($obj->isProcessing()) {
            return true;
        }

        if (!isset($obj->queue_file_hash) || empty($obj->queue_file_hash)) {
            $this->raiseError("Found no file hash for QueueItemModel {$id}, {$guid}!");
            return false;
        }

        try {
            $archive = new ArchiveController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load ArchiveController!");
            return false;
        }

        if (!$archive) {
            $this->raiseError("Unable to load ArchiveController!");
            return false;
        }

        if (($dupl_item = $archive->checkForDuplicateFileByHash($obj->queue_file_hash)) === false) {
            $this->raiseError("ArchiveController::checkForDuplicateFileByHash returned false!");
            return false;
        }

        if (!empty($dupl_item)) {
            $this->raiseError("There is already an item with the same file hash in the archive!");
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Invoking archive process.', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$archive->archive($obj)) {
            $this->raiseError("ArchiveController::archive() exited with an error!");
            return false;
        }

        return true;
    }

    public function archiveAll()
    {
        global $mtlda, $mbus;

        try {
            $queue = new \Mtlda\Models\QueueModel;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load QueueModel");
            return false;
        }

        $total = count($queue->avail_items);
        $counter = 1;
        $start = 20;

        $steps = floor((100-$start)/$total);

        foreach ($queue->avail_items as $key) {
            $queueitem = $queue->items[$key];
            $idx = $queueitem->queue_idx;
            $guid = $queueitem->queue_guid;

            if ($queueitem->isProcessing()) {
                continue;
            }

            if (empty($idx) || !$mtlda->isValidGuidSyntax($guid)) {
                continue;
            }

            if (!$mbus->sendMessageToClient(
                'archive-reply',
                "Archiving item {$counter} of {$total}.",
                ($start+($steps*$counter)).'%'
            )) {
                $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }

            $state = $mbus->suppressOutboundMessaging(true);
            if (!$this->archive($idx, $guid)) {
                $mtlda->raiseArchive(__CLASS__ ."::archive() returned false for QueueItem {$idx}, {$guid}!");
                return false;
            }
            $mbus->suppressOutboundMessaging($state);

            $counter++;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
