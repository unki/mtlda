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

class ArchiveView extends DefaultView
{
    protected static $view_class_name = 'archive';
    public $item_name = 'Document';
    protected $item;
    protected $keywords;
    protected $document_properties;
    protected $archive_avail_items = array();
    protected $archive_items = array();

    public function __construct()
    {
        global $tmpl;

        try {
            $archive = new \Mtlda\Models\ArchiveModel;
        } catch (\Exception $e) {
            static::raiseError("Failed to load ArchiveModel!", true);
            return;
        }

        if (!$this->setViewData($archive)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        $tmpl->registerPlugin("function", "list_versions", array(&$this, "listVersions"), false);
        parent::__construct();

        return;
    }

    public function showEdit($id, $guid)
    {
        /* this model provides no edit function */
        return true;
    }

    public function showItem($id, $guid)
    {
        global $config, $tmpl;

        if ($this->item_name != "Document") {
            static::raiseError(__METHOD__ .' can only work with documents!');
            return false;
        }

        try {
            $this->item = new \Mtlda\Models\DocumentModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!isset($this->item) || empty($this->item)) {
            return false;
        }

        $descendants = array();

        if ($this->item->hasDescendants()) {
            if (!$descendants = $this->item->getDescendants()) {
                static::raiseError(get_class($this->item) .'::getDescendants() returned false!');
                return false;
            }
        }

        if (!($base_path = $config->getWebPath())) {
            static::raiseError("Web path is missing!");
            return false;
        }

        if ($config->isPdfIndexingEnabled()) {
            $tmpl->assign('pdf_indexing_is_enabled', true);
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            static::raiseError("Failed to load KeywordsModel!");
            return false;
        }

        $tmpl->assign('latest_document_version', $this->item->getLastestDocumentVersionNumber());
        $tmpl->assign('keywords_rpc_url', $base_path .'/keywords/rpc.html');
        $tmpl->assign('item_versions', $descendants);
        $tmpl->assign('item', $this->item);
        $tmpl->assign('keywords', $this->keywords->getItems());
        $tmpl->assign("item_safe_link", "document-". $this->item->getId() ."-". $this->item->getGuid());

        try {
            $this->document_properties = new \Mtlda\Models\DocumentPropertiesModel(array(
                'idx' => $this->item->getId(),
                'guid' => $this->item->getGuid()
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load DocumentPropertiesModel!');
            return false;
        }

        $tmpl->registerPlugin("block", "document_properties", array(&$this, "listDocumentProperties"), false);
        return parent::showItem($id, $guid);
    }

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
            static::raiseError(__METHOD__ .", failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($item_idx))) {
            static::raiseError(__METHOD__ .", failed to execute query!");
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_COLUMN);

        if ($rows === false) {
            static::raiseError(__METHOD__ .", failed to fetch result!");
            return false;
        }

        if (!is_array($rows)) {
            static::raiseError(__METHOD__ .", PDO::fetchAll has not returned an array!");
            return false;
        }

        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }

    public function listVersions($params, &$smarty)
    {
        global $query;

        if (!$this->item->hasDescendants()) {
            return true;
        }

        $content = "";

        if (($content = $this->buildVersionsList()) === false) {
            static::raiseError(get_class($this->item) .'::buildVersionsList() returned false!');
            return false;
        }

        return $content;
    }

    protected function buildVersionsList($descendants = null, $level = 0)
    {
        global $tmpl;

        $content = "";

        if (!isset($descendants)) {
            if (!$descendants = $this->item->getDescendants()) {
                static::raiseError(get_class($this->item) .'::getDescendants() returned false!');
                return false;
            }
        }

        $len = count($descendants);
        $counter = 0;

        foreach ($descendants as $item) {
            if ($item->isDeleted()) {
                continue;
            }
            $tmpl->assign('item', $item);
            $tmpl->assign('item_safe_link', 'document-'. $item->getId() .'-'. $item->getGuid());

            if ($item->hasDescendants()) {
                $tmpl->assign('item_has_descendants', true);
            } else {
                $tmpl->assign('item_has_descendants', false);
            }

            if ($level > 0 && $counter == ($len-1)) {
                $tmpl->assign('item_is_last_descendant', true);
            } else {
                $tmpl->assign('item_is_last_descendant', false);
            }

            if (!$src = $tmpl->fetch('archive_show_item.tpl')) {
                static::raiseError(__CLASS__ .'::fetch() returned false!');
                return false;
            }

            $content.= $src;

            if (!$item->hasDescendants()) {
                $counter+=1;
                continue;
            }

            if (!$item_descendants = $item->getDescendants()) {
                static::raiseError(get_class($item) .'::getDescendants() returned false!');
                return false;
            }

            if (($item_content = $this->buildVersionsList($item_descendants, $level+1)) === false) {
                static::raiseError(get_class($this->item) .'::buildVersionsList() returned false!');
                return false;
            }

            $content.= $item_content;
            $counter+=1;
        }

        return $content;
    }

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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
