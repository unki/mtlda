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

class MainView extends DefaultView
{
    public $class_name = 'main';
    private $queue;
    private $archive;

    public function __construct()
    {
        global $mtlda, $tmpl;

        $tmpl->registerPlugin("block", "top10", array(&$this, 'showTop10List'));

        if (!$this->load()) {
            $mtlda->raiseError(__CLASS__ .', load() returned false!');
            return false;
        }

        parent::__construct();
        return true;
    }

    protected function load()
    {
        global $mtlda, $tmpl;

        try {
            $this->queue = new \Mtlda\Models\QueueModel(array(
                'by' => 'queue_time',
                'order' => 'DESC'
            ));
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load QueueModel!", true);
            return false;
        }

        try {
            $this->archive = new \Mtlda\Models\ArchiveModel(array(
                'by' => 'document_time',
                'order' => 'DESC'
            ));
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load ArchiveModel!", true);
            return false;
        }

        if ($this->queue->getItemsCount() > 0) {
            $tmpl->assign('pending_queue_items', true);
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
            $avail_items =& $this->archive->getItemsKeys();
            $items =& $this->archive;
        } elseif ($params['type'] == 'queue') {
            $avail_items =& $this->queue->getItemsKeys();
            $items =& $this->queue;
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
        $item =  $items->getItem($item_idx);

        if (method_exists($item, "hasDescendants") && $item->hasDescendants()) {
            if (($latest = $item->getLastestVersion()) === false) {
                $this->raiseError(get_class($item) .'::getLastestVersion() returned false!');
                return false;
            }
            if (!($idx = $latest->getId())) {
                $this->raiseError(get_class($latest) .'::getId() returned false!');
                return false;
            }
            if (!($guid = $latest->getGuid())) {
                $this->raiseError(get_class($latest) .'::getGuid() returned false!');
                return false;
            }
            $smarty->assign("document_safe_link", "document-{$idx}-{$guid}");
            unset($latest);
        } else {
            $smarty->assign("document_safe_link", "document-{$item->getId()}-{$item->getGuid()}");
        }

        $smarty->assign("item", $item);
        $smarty->assign("item_safe_link", "{$item->getId()}-{$item->getGuid()}");

        $index++;
        $smarty->assign("smarty.IB.{$params['type']}_list.index", $index);
        $repeat = true;

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
