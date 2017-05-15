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

namespace Mtlda\Views;

class MainView extends DefaultView
{
    protected static $view_class_name = 'main';

    protected $queue;
    protected $archive;

    protected $queued;
    protected $archived;

    public function __construct()
    {
        global $tmpl;

        $tmpl->registerPlugin("block", "top10", array(&$this, 'showTop10List'));

        if (!$this->load()) {
            static::raiseError(__CLASS__ .', load() returned false!');
            return false;
        }

        parent::__construct();
        return true;
    }

    protected function load()
    {
        global $tmpl;

        try {
            $this->queue = new \Mtlda\Models\QueueModel(
                array(),
                array(
                    'time' => 'DESC'
                )
            );
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load QueueModel!', false, $e);
            return false;
        }

        try {
            $this->archive = new \Mtlda\Models\ArchiveModel(
                array(),
                array(
                    'time' => 'DESC'
                )
            );
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ArchiveModel!', false, $e);
            return false;
        }

        if ($this->archive->hasItems()) {
            if (($this->archived = $this->archive->getItemsKeys()) === false) {
                static::raiseError(get_class($this->archive) .'::getItems() returned false!');
                return false;
            }

            $tmpl->assign('has_archived_items', true);
        }

        if (!$this->queue->hasItems()) {
            return true;
        }

        $tmpl->assign('has_pending_queue_items', true);

        if (($this->queued = $this->queue->getItemsKeys()) === false) {
            static::raiseError(get_class($this->queue) .'::getItems() returned false!');
            return false;
        }

        return true;
    }

    public function showTop10List($params, $content, &$smarty, &$repeat)
    {
        if (!isset($params['type'])) {
            static::raiseError(__METHOD__ .'(), top10 block misses "type" parameter!', true);
            return;
        }

        if ($params['type'] === 'archive') {
            $item_idx = current($this->archived);
            $items =& $this->archive;
            $cnt = count($this->archived);
            next($this->archived);
        } elseif ($params['type'] === 'queue') {
            $item_idx = current($this->queued);
            $items =& $this->queue;
            $cnt = count($this->queued);
            next($this->queued);
        } else {
            static::raiseError(__METHOD__ ."(), type '{$params['type']}' is not supported!", true);
            return false;
        }

        if (($item = $items->getItem($item_idx)) === false) {
            static::raiseError(get_class($items) .'::getItem() returned false!');
            return false;
        }

        $index = $smarty->getTemplateVars("smarty.IB.{$params['type']}_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= $cnt || $index > 9) {
            $repeat = false;
            return $content;
        }

        if (method_exists($item, "hasDescendants") && $item->hasDescendants()) {
            if (($latest = $item->getLastestVersion()) === false) {
                static::raiseError(get_class($item) .'::getLastestVersion() returned false!');
                return false;
            }
            if (($idx = $latest->getIdx()) === false) {
                static::raiseError(get_class($latest) .'::getIdx() returned false!');
                return false;
            }
            if (($guid = $latest->getGuid()) === false) {
                static::raiseError(get_class($latest) .'::getGuid() returned false!');
                return false;
            }
            $smarty->assign("document_safe_link", "document-{$idx}-{$guid}");
            unset($latest);
        } else {
            $smarty->assign("document_safe_link", "document-{$item->getIdx()}-{$item->getGuid()}");
        }

        $smarty->assign("item", $item);
        $smarty->assign("item_safe_link", "{$item->getIdx()}-{$item->getGuid()}");

        $index++;
        $smarty->assign("smarty.IB.{$params['type']}_list.index", $index);
        $repeat = true;

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
