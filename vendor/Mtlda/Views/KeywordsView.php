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
        global $db;

        parent::__construct();

        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            $this->raiseError(__CLASS_ .', failed to load KeywordsModel', true);
            return false;
        }

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

        if (empty($this->keywords->items)) {
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

        if (!$pager->setPagingData($this->keywords->items)) {
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

    public function keywordsList($params, $content, &$smarty, &$repeat)
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
            $this->raiseError("Failed to load KeywordModel!");
            return false;
        }

        $tmpl->assign('item', $item);
        $tmpl->assign("item_safe_link", "keyword-". $item->keyword_idx ."-". $item->keyword_guid);
        return $tmpl->fetch("keywords_edit.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
