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
    public $class_name = 'archive';
    public $item_name = 'Document';
    public $archive;
    private $item;
    private $keywords;
    private $document_properties;

    public function __construct()
    {
        global $mtlda, $tmpl;

        try {
            $this->archive = new \Mtlda\Models\ArchiveModel;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load ArchiveModel!", true);
            return false;
        }

        parent::__construct();

        $tmpl->registerPlugin("function", "list_versions", array(&$this, "listVersions"), false);
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

        if ($item->hasDescendants()) {
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
        global $mtlda, $config, $tmpl;

        if ($this->item_name != "Document") {
            $mtlda->raiseError(__METHOD__ .' can only work with documents!');
            return false;
        }

        try {
            $this->item = new \Mtlda\Models\DocumentModel($id, $hash);
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!isset($this->item) || empty($this->item)) {
            return false;
        }

        $descendants = array();

        if ($this->item->hasDescendants()) {
            if (!$descendants = $this->item->getDescendants()) {
                $mtlda->raiseError(get_class($this->item) .'::getDescendants() returned false!');
                return false;
            }
        }

        if (!($base_path = $config->getWebPath())) {
            $mtlda->raiseError("Web path is missing!");
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
            $mtlda->raiseError("Failed to load KeywordsModel!");
            return false;
        }

        if (($assigned_keywords = $this->getItemKeywords($this->item->document_idx)) === false) {
            $mtlda->raiseError(__CLASS__ ."::getItemKeywords() returned false!");
            return false;
        }

        $assigned_keywords = implode(',', $assigned_keywords);

        $tmpl->assign('latest_document_version', $this->item->getLastestDocumentVersionNumber());
        $tmpl->assign('keywords_rpc_url', $base_path .'/keywords/rpc.html');
        $tmpl->assign('item_versions', $descendants);
        $tmpl->assign('item', $this->item);
        $tmpl->assign('keywords', $this->keywords->items);
        $tmpl->assign('assigned_keywords', $assigned_keywords);
        $tmpl->assign("item_safe_link", "document-". $this->item->document_idx ."-". $this->item->document_guid);

        try {
            $this->document_properties = new \Mtlda\Models\DocumentPropertiesModel(
                $this->item->getId(),
                $this->item->getGuid()
            );
        } catch (\Exception $e) {
            $mtlda->raiseError(__METHOD__ .'(), failed to load DocumentPropertiesModel!');
            return false;
        }

        $tmpl->registerPlugin("block", "document_properties", array(&$this, "listDocumentProperties"), false);
        return parent::showItem($id, $hash);
    }

    private function getItemKeywords($item_idx)
    {
        global $mtlda, $db;

        $sth = $db->prepare(
            "SELECT
                akd_keyword_idx
            FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_archive_idx LIKE ?"
        );

        if (!$sth) {
            $mtlda->raiseError(__METHOD__ .", failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($item_idx))) {
            $mtlda->raiseError(__METHOD__ .", failed to execute query!");
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_COLUMN);

        if ($rows === false) {
            $mtlda->raiseError(__METHOD__ .", failed to fetch result!");
            return false;
        }

        if (!is_array($rows)) {
            $mtlda->raiseError(__METHOD__ .", PDO::fetchAll has not returned an array!");
            return false;
        }

        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }

    public function listVersions($params, &$smarty)
    {
        global $mtlda, $query;

        if (!$this->item->hasDescendants()) {
            return true;
        }

        $content = "";

        if (!$content = $this->buildVersionsList()) {
            $mtlda->raiseError(get_class($this->item) .'::buildVersionsList() returned false!');
            return false;
        }

        return $content;
    }

    private function buildVersionsList($descendants = null, $level = 0)
    {
        global $mtlda, $tmpl;

        $content = "";

        if (!isset($descendants)) {
            if (!$descendants = $this->item->getDescendants()) {
                $mtlda->raiseError(get_class($this->item) .'::getDescendants() returned false!');
                return false;
            }
        }

        $len = count($descendants);
        $counter = 0;

        foreach ($descendants as $item) {
            $tmpl->assign('item', $item);
            $tmpl->assign('item_safe_link', 'document-'. $item->document_idx .'-'. $item->document_guid);

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
                $mtlda->raiseError(__CLASS__ .'::fetch() returned false!');
                return false;
            }

            $content.= $src;

            if (!$item->hasDescendants()) {
                $counter+=1;
                continue;
            }

            if (!$item_descendants = $item->getDescendants()) {
                $mtlda->raiseError(get_class($item) .'::getDescendants() returned false!');
                return false;
            }

            if (!$item_content = $this->buildVersionsList($item_descendants, $level+1)) {
                $mtlda->raiseError(get_class($this->item) .'::buildVersionsList() returned false!');
                return false;
            }

            $content.= $item_content;
            $counter+=1;
        }

        if (empty($content)) {
            return false;
        }

        return $content;
    }

    public function listDocumentProperties($params, $content, &$smarty, &$repeat)
    {
        global $mtlda;

        $index = $smarty->getTemplateVars("smarty.IB.properties_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->document_properties->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->document_properties->avail_items[$index];
        $item =  $this->document_properties->items[$item_idx];

        $smarty->assign("property", $item);

        $index++;
        $smarty->assign("smarty.IB.properties_list.index", $index);
        $repeat = true;

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
