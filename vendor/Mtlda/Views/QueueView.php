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

class QueueView extends DefaultView
{
    public $class_name = 'queue';
    public $item_name = 'QueueItem';
    public $queue;

    public function __construct()
    {
        try {
            $this->queue = new \Mtlda\Models\QueueModel;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load QueueModel!", true);
            return false;
        }

        parent::__construct();
    }

    public function queueList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.item_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!isset($this->avail_items) || empty($this->avail_items)) {
            $repeat = false;
            return $content;
        }

        if ($index >= count($this->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->avail_items[$index];
        $item =  $this->items[$item_idx];

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
            $this->raiseError("Require a valid \$id to show!");
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            $this->raiseError("Require a valid \$guid to show!");
            return false;
        }

        try {
            $item = new \Mtlda\Models\QueueItemModel($id, $guid);
        } catch (\Exception $e) {
            $this->raiseError("Failed to load QueueItemModel({$id}, {$guid})!");
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load StorageController!");
            return false;
        }

        if (!$file = $storage->retrieveFile($item)) {
            $this->raiseError("StorageController::retrieveFile() returned false!");
            return false;
        }

        if (!isset($file) ||
            empty($file) ||
            !is_array($file) ||
            !isset($file['hash'], $file['content']) ||
            empty($file['hash']) ||
            empty($file['content'])
        ) {
            $this->raiseError("StorageController::retireveFile() returned an invalid file");
            return false;
        }

        if (strlen($file['content']) != $item->queue_file_size) {
            $this->raiseError("File size of retrieved file does not match archive record!");
            return false;
        }

        if ($file['hash'] != $item->queue_file_hash) {
            $this->raiseError("File hash of retrieved file does not match archive record!");
            return false;
        }

        header('Content-Type: application/pdf');
        header('Content-Length: '. strlen($file['content']));
        print $file['content'];
        return true;
    }

    public function showList($pageno = null)
    {
        global $session;

        if (!isset($pageno) ||
            empty($pageno) ||
            !is_numeric($pageno)
        ) {
            if (($current_page = $session->getVariable("{$this->class_name}_current_page")) === false) {
                $current_page = 1;
            }
        } else {
            $current_page = $pageno;
        }

        if (empty($this->queue->items)) {
            return parent::showList();
        }

        try {
            $pager = new \Mtlda\Controllers\PagingController(array(
                'items_per_page' => 10,
                'delta' => 2,
            ));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load PagingController!');
            return false;
        }

        if (!$pager->setPagingData($this->queue->items)) {
            $this->raiseError(get_class($pager) .'::setPagingData() returned false!');
            return false;
        }

        if (!$pager->setCurrentPage($current_page)) {
            $this->raiseError(get_class($pager) .'::setCurrentPage() returned false!');
            return false;
        }

        global $tmpl;
        $tmpl->assign('pager', $pager);

        if (($data = $pager->getPageData()) === false) {
            $this->raiseError(get_class($pager) .'::getPageData() returned false!');
            return false;
        }

        if (!isset($data) || empty($data) || !is_array($data)) {
            $this->raiseError(get_class($pager) .'::getPageData() returned invalid data!');
            return false;
        }

        $this->avail_items = array_keys($data);
        $this->items = $data;

        if (!$session->setVariable("{$this->class_name}_current_page", $current_page)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        return parent::showList();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
