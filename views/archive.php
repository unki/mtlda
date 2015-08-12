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

class ArchiveView extends Templates
{
    public $class_name = 'archive';
    public $item_name = 'Document';
    public $archive;
    private $item;

    public function __construct()
    {
        $this->archive = new Models\ArchiveModel;

        parent::__construct();
    }

    public function showEdit()
    {
        /* this model provides no edit function */
    }

    public function archiveList($params, $content, &$smarty, &$repeat)
    {
        global $mtlda;

        $index = $smarty->getTemplateVars('smarty.IB.item_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->archive->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->archive->avail_items[$index];
        $item =  $this->archive->items[$item_idx];

        if (
            isset($item->document_latest_version) &&
            !empty($item->document_latest_version) &&
            is_array($item->document_latest_version)
        ) {
            $latest = $item->document_latest_version;
            $smarty->assign("document_safe_link", "document-{$latest['idx']}-{$latest['guid']}");
            unset($latest);
        } else {
            $smarty->assign("document_safe_link", "document-{$item->document_idx}-{$item->document_guid}");
        }
        $smarty->assign("item", $item);
        $smarty->assign("item_safe_link", "{$item->document_idx}-{$item->document_guid}");

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }

    public function showItem($id, $hash)
    {
        if ($this->item_name == "Document") {
            $this->item = new Models\DocumentModel($id, $hash);
        }

        if (!isset($this->item) || empty($this->item)) {
            return false;
        }

        $descendants = $this->item->getDescendants();

        if (!$descendants) {
            $descendants = array();
        }

        $this->assign('item_versions', $descendants);
        $this->assign('item', $this->item);
        $this->assign("item_safe_link", "document-". $this->item->document_idx ."-". $this->item->document_guid);
        return parent::showItem($id, $hash);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
