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

namespace Mtlda\Views;

use Mtlda\Models;
use Mtlda\Controllers;

class QueueView extends DefaultView
{
    public $class_name = 'queue';
    public $item_name = 'QueueItem';
    public $queue;

    public function __construct()
    {
        global $mtlda;

        try {
            $this->queue = new Models\QueueModel;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load QueueModel!", true);
            return false;
        }

        parent::__construct();
    }

    public function queueList($params, $content, &$smarty, &$repeat)
    {
        global $mtlda;

        $index = $smarty->getTemplateVars('smarty.IB.item_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->queue->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->queue->avail_items[$index];
        $item =  $this->queue->items[$item_idx];

        $smarty->assign("item", $item);
        $smarty->assign("item_safe_link", $item->queue_idx ."-". $item->queue_guid);

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }

    public function showItem($id, $guid)
    {
        global $mtlda;

        if (empty($id) || !$mtlda->isValidId($id)) {
            $mtlda->raiseError("Require a valid \$id to show!");
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            $mtlda->raiseError("Require a valid \$guid to show!");
            return false;
        }

        try {
            $item = new Models\QueueItemModel($id, $guid);
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load QueueItemModel({$id}, {$guid})!");
            return false;
        }

        try {
            $storage = new Controllers\StorageController;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load StorageController!");
            return false;
        }

        if (!$file = $storage->retrieveFile($item)) {
            $mtlda->raiseError("StorageController::retrieveFile() returned false!");
            return false;
        }

        if (
            !isset($file) ||
            empty ($file) ||
            !is_array($file) ||
            !isset($file['hash'], $file['content']) ||
            empty($file['hash']) ||
            empty($file['content'])
        ) {
            $mtlda->raiseError("StorageController::retireveFile() returned an invalid file");
            return false;
        }

        if (strlen($file['content']) != $item->queue_file_size) {
            $mtlda->raiseError("File size of retrieved file does not match archive record!");
            return false;
        }

        if ($file['hash'] != $item->queue_file_hash) {
            $mtlda->raiseError("File hash of retrieved file does not match archive record!");
            return false;
        }

        header('Content-Type: application/pdf');
        header('Content-Length: '. strlen($file['content']));
        print $file['content'];
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
