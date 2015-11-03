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

class KeywordsView extends DefaultView
{
    public $default_mode = 'list';
    public $class_name = 'keywords';

    public function __construct()
    {
        global $mtlda, $db;

        parent::__construct();

        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            $this->raiseError(__CLASS_ .', failed to load KeywordsModel', true);
            return false;
        }

        return true;
    }

    public function showList()
    {
        return parent::showList();
    }

    public function keywordsList($params, $content, &$smarty, &$repeat)
    {
        global $mtlda;

        $index = $smarty->getTemplateVars('smarty.IB.item_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->keywords->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->keywords->avail_items[$index];
        $item =  $this->keywords->items[$item_idx];

        $smarty->assign("item", $item);
        $smarty->assign("item_safe_link", "keyword-{$item->keyword_idx}-{$item->keyword_guid}");

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }

    public function showEdit($id = null, $hash = null)
    {
        global $mtlda, $tmpl;

        if (!isset($id) &&
            !empty($id) &&
            isset($guid) &&
            !empty($guid) &&
            $mtlda->isValidGuidSyntax($guid)
        ) {
            $item = new \Mtlda\Models\KeywordModel($id, $guid);
        } else {
            $item = new \Mtlda\Models\KeywordModel;
        }

        if (!isset($item) || empty($item)) {
            $mtlda->raiseError("Failed to load KeywordModel!");
            return false;
        }

        $tmpl->assign('item', $item);
        $tmpl->assign("item_safe_link", "keyword-". $item->keyword_idx ."-". $item->keyword_guid);
        return $tmpl->fetch("keywords_edit.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
