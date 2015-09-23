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
        global $mtlda, $config;

        if ($this->item_name == "Document") {
            try {
                $this->item = new Models\DocumentModel($id, $hash);
            } catch (Exception $e) {
                $mtlda->raiseError("Failed to load DocumentModel!");
                return false;
            }
        }

        if (!isset($this->item) || empty($this->item)) {
            return false;
        }

        $descendants = $this->item->getDescendants();

        if (!$descendants) {
            $descendants = array();
        }

        if (!($base_path = $config->getWebPath())) {
            $mtlda->raiseError("Web path is missing!");
            return false;
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        try {
            $keywords = new Models\KeywordsModel;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load KeywordsModel!");
            return false;
        }

        if (($assigned_keywords = $this->getItemKeywords($this->item->document_idx)) === false) {
            $mtlda->raiseError(__CLASS__ ."::getItemKeywords() returned false!");
            return false;
        }

        $this->assign('latest_document_version', $this->item->getLastestDocumentVersionNumber());
        $this->assign('keywords_rpc_url', $base_path .'/keywords/rpc.html');
        $this->assign('item_versions', $descendants);
        $this->assign('item', $this->item);
        $this->assign('keywords', $keywords->items);
        $this->assign('assigned_keywords', $assigned_keywords);
        $this->assign("item_safe_link", "document-". $this->item->document_idx ."-". $this->item->document_guid);
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
            $mtlda->raiseError(__TRAIT__ .", failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($item_idx))) {
            $mtlda->raiseError(__TRAIT__ .", failed to execute query!");
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_COLUMN);

        if ($rows === false) {
            $mtlda->raiseError(__TRAIT__ .", failed to fetch result!");
            return false;
        }

        if (!is_array($rows)) {
            $mtlda->raiseError(__TRAIT__ .", PDO::fetchAll has not returned an array!");
            return false;
        }

        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
