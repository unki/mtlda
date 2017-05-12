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

class QueueController extends DefaultController
{
    public function archive($id, $guid)
    {
        global $mtlda, $mbus;

        if (!isset($id) || empty($id) || !is_numeric($id)) {
            static::raiseError(__METHOD__ .'(), id is invalid!');
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            static::raiseError(__METHOD__ .'(), guid is invalid!');
            return false;
        }

        if (!($obj = $mtlda->loadModel("queueitem", $id, $guid))) {
            static::raiseError(__METHOD__ ."(), unable to load QueueItemModel for {$id}, {$guid}");
            return false;
        }

        if ($obj->isProcessing()) {
            return true;
        }

        if (!$obj->hasFileHash()) {
            static::raiseError(__METHOD__ .'(), no hash-value for file!');
            return false;
        }

        if (($file_hash = $obj->getFileHash()) === false) {
            static::raiseError(get_class($obj) .'::getFileHash() returned false!');
            return false;
        }

        if (empty($file_hash) || !is_string($file_hash)) {
            static::raiseError(__METHOD__ ."(), no valid hash-value for QueueItemModel {$id}, {$guid}!");
            return false;
        }

        try {
            $archive = new \Mtlda\Controllers\ArchiveController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ArchiveController!');
            return false;
        }

        if (!isset($archive) || empty($archive) || !is_object($archive) ||
            !is_a($archive, 'Mtlda\Controllers\ArchiveController')
        ) {
            static::raiseError(__METHOD__ .'(), unable to load ArchiveController!');
            return false;
        }

        if (($dupl_item = $archive->checkForDuplicateFileByHash($file_hash)) === false) {
            static::raiseError(get_class($archive) .'::checkForDuplicateFileByHash() returned false!');
            return false;
        }

        if (!empty($dupl_item)) {
            static::raiseError(
                __METHOD__ .'(), there is already an item with the same file hash present in the archive!'
            );
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Invoking archive process.', '20%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$archive->archive($obj)) {
            static::raiseError(get_class($archive) .'::archive() exited with an error!');
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
            static::raiseError(__METHOD__ .'(), failed to load QueueModel!');
            return false;
        }

        $total = $queue->getItemsCount();
        $counter = 1;
        $start = 20;

        $steps = floor((100-$start)/$total);

        foreach ($queue->getItemsKeys() as $key) {
            if (!$queue->hasItem($key)) {
                static::raiseError(get_class($queue) .'::hasItem() returned false!');
                return false;
            }

            if (($queueitem = $queue->getItem($key)) === false) {
                static::raiseError(get_clasS($queue) .'::getItem() returned false!');
                return false;
            }

            if (!is_a($queueitem, 'Mtlda\Models\QueueItemModel')) {
                static::raiseError(get_class($queue) .'::getItem() has not returned a QueueItemModel!');
                return false;
            }

            if (($idx = $queueitem->getIdx()) === false) {
                static::raiseError(get_class($queueitem) .'::getIdx() returned false!');
                return false;
            }

            if (($guid = $queueitem->getGuid()) === false) {
                static::raiseError(get_class($queueitem) .'::getGuid() returned false!');
                return false;
            }

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
                static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
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
