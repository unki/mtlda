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

namespace MTLDA\Views;

use MTLDA\Models;

class MainView extends DefaultView
{
    public $class_name = 'main';
    private $queue;
    private $archive;

    public function __construct()
    {
        global $mtlda;

        parent::__construct();

        $this->registerPlugin("block", "top10", array(&$this, 'showTop10List'));

        try {
            $this->queue = new Models\QueueModel;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load QueueModel!", true);
            return false;
        }

        try {
            $this->archive = new Models\ArchiveModel;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load ArchiveModel!", true);
            return false;
        }

        if (count($this->queue->avail_items) > 0) {
            $this->assign('pending_queue_items', true);
        }

        return true;
    }

    public function showTop10List($params, $content, &$smarty, &$repeat)
    {
        global $mtlda;

        if (!isset($params['type'])) {
            $mtlda->raiseError("top10 block misses 'type' parameter!");
            return false;
        }

        if ($params['type'] == 'archive') {
            $avail_items =& $this->archive->avail_items;
            $items =& $this->archive->items;
        } elseif ($params['type'] == 'queue') {
            $avail_items =& $this->queue->avail_items;
            $items =& $this->queue->items;
        } else {
            $mtlda->raiseError("Type '{$params['type']}' is not supported!");
            return false;
        }

        $index = $smarty->getTemplateVars("smarty.IB.{$params['type']}_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($avail_items) || $index > 9) {
            $repeat = false;
            return $content;
        }

        $item_idx = $avail_items[$index];
        $item =  $items[$item_idx];

        $smarty->assign("item", $item);
        if ($params['type'] == 'archive') {
            $smarty->assign("item_safe_link", "{$item->document_idx}-{$item->document_guid}");
        } elseif ($params['type'] == 'queue') {
            $smarty->assign("item_safe_link", "queue-{$item->queue_idx}-{$item->queue_guid}");
        }

        $index++;
        $smarty->assign("smarty.IB.{$params['type']}_list.index", $index);
        $repeat = true;

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
