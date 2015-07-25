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
    public $item_name = 'ArchiveItem';
    public $archive;

    public function __construct()
    {
        $this->archive = new Models\ArchiveModel;

        parent::__construct();
    }

    public function showEdit($id, $hash)
    {
        $item = new Models\ArchiveItemModel($id, $hash);

        parent::showEdit($item);
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

        $smarty->assign("item", $item);
        $smarty->assign("item_safe_link", $item->archive_idx ."-". $item->archive_guid);

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
