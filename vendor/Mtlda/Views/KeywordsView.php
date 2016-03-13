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
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'keywords';

    public function __construct()
    {
        global $db;

        parent::__construct();

        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            static::raiseError(__CLASS_ .', failed to load KeywordsModel', true);
            return;
        }

        return;
    }

    public function showList($pageno = null, $items_limit = null)
    {
        global $session;

        if (!isset($pageno) || empty($pageno) || !is_numeric($pageno)) {
            if (($current_page = $session->getVariable(static::$view_class_name .'_current_page')) === false) {
                $current_page = 1;
            }
        } else {
            $current_page = $pageno;
        }

        if (!isset($items_limit) || is_null($items_limit) || !is_numeric($items_limit)) {
            if (($current_items_limit = $session->getVariable(
                static::$view_class_name .'_current_items_limit'
            )) === false) {
                $current_items_limit = -1;
            }
        } else {
            $current_items_limit = $items_limit;
        }

        if (!$this->keywords->hasItems()) {
            return parent::showList();
        }

        try {
            $pager = new \Mtlda\Controllers\PagingController(array(
                'delta' => 2,
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PagingController!');
            return false;
        }

        if (!$pager->setPagingData($this->keywords->getItems())) {
            static::raiseError(get_class($pager) .'::setPagingData() returned false!');
            return false;
        }

        if (!$pager->setCurrentPage($current_page)) {
            static::raiseError(get_class($pager) .'::setCurrentPage() returned false!');
            return false;
        }

        if (!$pager->setItemsLimit($current_items_limit)) {
            static::raiseError(get_class($pager) .'::setItemsLimit() returned false!');
            return false;
        }

        global $tmpl;
        $tmpl->assign('pager', $pager);

        if (($data = $pager->getPageData()) === false) {
            static::raiseError(get_class($pager) .'::getPageData() returned false!');
            return false;
        }

        if (!isset($data) || empty($data) || !is_array($data)) {
            static::raiseError(get_class($pager) .'::getPageData() returned invalid data!');
            return false;
        }

        $this->avail_items = array_keys($data);
        $this->items = $data;

        if (!$session->setVariable(static::$view_class_name .'_current_page', $current_page)) {
            static::raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        if (!$session->setVariable(static::$view_class_name .'_current_items_limit', $current_items_limit)) {
            static::raiseError(get_class($session) .'::setVariable() returned false!');
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
        $smarty->assign("item_safe_link", "keyword-{$item->getId()}-{$item->getGuid()}");

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
            $item = new \Mtlda\Models\KeywordModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } else {
            $item = new \Mtlda\Models\KeywordModel;
        }

        if (!isset($item) || empty($item)) {
            static::raiseError("Failed to load KeywordModel!");
            return false;
        }

        $tmpl->assign('item', $item);
        $tmpl->assign("item_safe_link", "keyword-". $item->getId() ."-". $item->getGuid());
        return $tmpl->fetch("keywords_edit.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
