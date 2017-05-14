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

class ArchiveView extends DefaultView
{
    protected static $view_class_name = 'archive';
    protected static $item_name = 'Document';

    protected $items;
    protected $document_properties;

    /*
     * showList()
     *
     * initializes an ArchiveModel and use it as view-data.
     *
     * @return bool
     * @params int $pageno
     * @params int $items_limit
     * @throws Mtlda\Controllers\ExceptionController
     */
    public function showList($pageno = null, $items_limit = null)
    {
        global $tmpl;

        try {
            $archive = new \Mtlda\Models\ArchiveModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ArchiveModel!', true);
            return;
        }

        if (!$this->setViewData($archive)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        return parent::showList($pageno, $items_limit);
    }

    /**
     * showEdit()
     *
     * we provide no editing function for an Item.
     *
     * @param int $id
     * @param string $guid
     * @return string|bool
     * @throws \Thallium\Controllers\ExceptionController
     **/
    public function showEdit($id, $guid)
    {
        /* this model provides no edit function */
        return true;
    }

    /**
     * showItem()
     *
     * display an archived item.
     *
     * @params int $id
     * @params string $guid
     * @return bool
     * @throws Mtlda\Controllers\ExceptionController
     */
    public function showItem($id, $guid)
    {
        global $config, $tmpl;

        if (!isset($id) || empty($id) || !is_numeric($id) ||
            !isset($guid) || empty($guid) || !is_string($guid)
        ) {
            static::raiseError(__METHOD__ .'(), invalid parameters!');
            return false;
        }

        try {
            $origin = new \Mtlda\Models\DocumentModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load DocumentModel!');
            return false;
        }

        if (!isset($origin) ||
            empty($origin) ||
            !is_a($origin, 'Mtlda\Models\DocumentModel')
        ) {
            return false;
        }

        if (($this->items = $this->buildItemsList($origin)) === false) {
            static::raiseError(__CLASS__ .'::buildItemsList() returned false!');
            return false;
        }

        if (($base_path = $config->getWebPath()) === false) {
            static::raiseError(__METHOD__ .'(), web path is missing!');
            return false;
        }

        if ($config->isPdfIndexingEnabled()) {
            $tmpl->assign('pdf_indexing_is_enabled', true);
        }

        if ($config->isPdfSignatureVerificationEnabled()) {
            $tmpl->assign('pdf_signature_verification_is_enabled', true);
        }

        if ($base_path === '/') {
            $base_path = '';
        }

        try {
            $keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load KeywordsModel!');
            return false;
        }

        $tmpl->assign('latest_document_version', $origin->getLastestDocumentVersionNumber());
        $tmpl->assign('keywords_rpc_url', $base_path .'/keywords/rpc.html');
        $tmpl->assign('item', $origin);
        $tmpl->assign("item_safe_link", $origin->getIdx() ."-". $origin->getGuid());
        $tmpl->assign('keywords', $keywords->getItems());

        try {
            $this->document_properties = new \Mtlda\Models\DocumentPropertiesModel(array(
                'idx' => $origin->getIdx(),
                'guid' => $origin->getGuid()
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load DocumentPropertiesModel!');
            return false;
        }

        $tmpl->registerPlugin("block", "document_properties", array(&$this, "listDocumentProperties"), false);
        $tmpl->registerPlugin("block", "list_versions", array(&$this, "listVersions"), false);

        return parent::showItem($id, $guid);
    }

    /**
     * getItemKeywords()
     *
     * retrieve a list of keywords assigned to an archived item.
     *
     * @params int $item_idx
     * @return bool|array
     * @throws Mtlda\Controllers\ExceptionController
     */
    protected function getItemKeywords($item_idx)
    {
        global $db;

        $sth = $db->prepare(
            "SELECT
                akd_keyword_idx
            FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_archive_idx LIKE ?"
        );

        if (!$sth) {
            static::raiseError(__METHOD__ .'(), failed to prepare query!');
            return false;
        }

        if (!$db->execute($sth, array($item_idx))) {
            static::raiseError(__METHOD__ .'(), failed to execute query!');
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_COLUMN);

        if ($rows === false) {
            static::raiseError(__METHOD__ .'(), failed to fetch result!');
            return false;
        }

        if (!is_array($rows)) {
            static::raiseError(__METHOD__ .'(), PDO::fetchAll() has not returned an array!');
            return false;
        }

        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }

    /**
     * listVersions()
     *
     * provides support for the {list_archive} tag used in smarty templates.
     * it returns a reversed list of items associated to an archived item.
     *
     * @params array $params
     * @params string $content
     * @params Smarty $smarty
     * @params bool $repeat
     * @return bool|string
     * @throws Mtlda\Controllers\ExceptionController
     */
    public function listVersions($params, $content, &$smarty, &$repeat)
    {
        global $tmpl;

        $index = $smarty->getTemplateVars("smarty.IB.versions_list.index");

        if (!isset($index) || empty($index)) {
            end($this->items);
            $index = 0;
        }

        if (!isset($this->items) || empty($this->items)) {
            $repeat = false;
            return $content;
        }

        $len = count($this->items);

        if ($index >= $len) {
            $repeat = false;
            return $content;
        }

        $item = current($this->items);

        if (!is_a($item, 'Mtlda\Models\DocumentModel')) {
            static::raiseError(__METHOD__ .'(), items list contains an invalid model!');
            return false;
        }

        $smarty->assign('item', $item);
        $smarty->assign("item_safe_link", $item->getIdx() ."-". $item->getGuid());
        //$tmpl->assign('item_has_descendants', $item->hasDescendants() ? true : false);
        //$tmpl->assign('item_is_last_descendant', ($index === ($len-1)) ? true : false);

        $index++;
        $smarty->assign("smarty.IB.versions_list.index", $index);

        prev($this->items);
        $repeat = true;

        return $content;
    }

    /**
     * buildItemsList()
     *
     * build an array of items associated with an archived item.
     * this will include at least the original item as well as any derivates of it.
     *
     * @params Mtlda\Models\DocumentModel $origin
     * @params int $level
     * @return bool|array
     * @throws Mtlda\Controllers\ExceptionController
     */
    protected function buildItemsList(&$origin, $level = 0)
    {
        $items = array($origin);

        if (!$origin->hasDescendants()) {
            return $items;
        }

        if (($descendants = $origin->getDescendants()) == false) {
            static::raiseError(get_class($origin) .'::getDescendants() returned false!');
            return false;
        }

        if (!isset($descendants) || !is_array($descendants)) {
            static::raiseError(get_class($origin) .'::getDescendants() returned invalid data!');
            return false;
        }

        $len = count($descendants);
        $counter = 0;

        foreach ($descendants as $item) {
            if ($item->isDeleted()) {
                continue;
            }

            if (!$item->hasDescendants()) {
                $counter+=1;
                $items[] = $item;
                continue;
            }

            if (($item_descendants = $this->buildItemsList($item, $level+1)) === false) {
                static::raiseError(__CLASS__ .'::buildItemsList() returned false!');
                return false;
            }

            if (!isset($item_descendants) || !is_array($item_descendants)) {
                static::raiseError(__CLASS__ .'::buildItemsList() returned invalid data!');
                return false;
            }

            $items+= $item_descendants;
            $counter+= 1;
        }

        return $items;
    }

    /**
     * listDocumentProperties()
     *
     * return an array of properties known for an archived item.
     *
     * @params array $params
     * @params string $content
     * @params Smarty $smarty
     * @params bool $repeat
     * @throws Mtlda\Controllers\ExceptionController
     */
    public function listDocumentProperties($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars("smarty.IB.properties_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!$this->document_properties->hasItems()) {
            $repeat = false;
            return $content;
        }

        if ($index >= count($this->document_properties->getItemsCount())) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->document_properties->getItemsKeys()[$index];
        $item =  $this->document_properties->getItem($item_idx);

        $smarty->assign("property", $item);

        $index++;
        $smarty->assign("smarty.IB.properties_list.index", $index);
        $repeat = true;

        return $content;
    }

    public function dataList($params, $content, &$smarty, &$repeat)
    {
        try {
            $content = parent::dataList($params, $content, $smarty, $repeat);
        } catch (\Exception $e) {
            static::raiseError(__CLASS__ .'::dataList() returned false!', false, $e);
            $repeat = false;
            return false;
        }

        if (!$this->hasCurrentItem()) {
            return $content;
        }

        if (($item = $this->getCurrentItem()) === false) {
            static::raiseError(__CLASS__ .'::getCurrentItem() returned false!');
            $repeat = false;
            return false;
        }

        if ($item->hasDescendants()) {
            if (($latest = $item->getLastestVersion()) === false) {
                static::raiseError(get_class($item) .'::getLastestVersion() returned false!');
                $repeat = false;
                return false;
            }
            if (!($idx = $latest->getIdx())) {
                static::raiseError(get_class($latest) .'::getIdx() returned false!');
                $repeat = false;
                return false;
            }
            if (!($guid = $latest->getGuid())) {
                static::raiseError(get_class($latest) .'::getGuid() returned false!');
                $repeat = false;
                return false;
            }
            $smarty->assign("document_safe_link", "document-{$idx}-{$guid}");
            unset($latest);
        } else {
            $smarty->assign("document_safe_link", "document-{$item->getIdx()}-{$item->getGuid()}");
        }

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
