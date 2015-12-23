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

class QueueView extends DefaultView
{
    public $class_name = 'queue';
    public $item_name = 'QueueItem';
    protected $queue;
    protected $keywords;
    protected $import;
    protected $dateSuggestions;
    protected $archiveItem;

    public function __construct()
    {
        global $tmpl;

        try {
            $this->queue = new \Mtlda\Models\QueueModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load QueueModel!', true);
            return false;
        }

        try {
            $this->import = new \Mtlda\Controllers\ImportController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load ImportController!', true);
            return false;
        }

        if (!$tmpl->addSupportedMode('archive')) {
            $this->raiseError(get_class($tmpl) .'::addSupportedMode() returned false!', true);
            return false;
        }

        if (!$tmpl->addSupportedMode('split')) {
            $this->raiseError(get_class($tmpl) .'::addSupportedMode() returned false!', true);
            return false;
        }

        if (!$this->addContent('archiver')) {
            $this->raiseError(__CLASS__ .'::addContent() returned false!', true);
            return false;
        }

        if (!$this->addContent('splitter')) {
            $this->raiseError(__CLASS__ .'::addContent() returned false!', true);
            return false;
        }

        parent::__construct();
    }

    public function queueList($params, $content, &$smarty, &$repeat)
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
        $smarty->assign("item_safe_link", $item->getId() ."-". $item->getGuid());

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }

    public function showItem($id, $guid)
    {
        global $mtlda;

        if (empty($id) || !$mtlda->isValidId($id)) {
            $this->raiseError("Require a valid \$id to show!");
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            $this->raiseError("Require a valid \$guid to show!");
            return false;
        }

        try {
            $item = new \Mtlda\Models\QueueItemModel($id, $guid);
        } catch (\Exception $e) {
            $this->raiseError("Failed to load QueueItemModel({$id}, {$guid})!");
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load StorageController!");
            return false;
        }

        if (!$file = $storage->retrieveFile($item)) {
            $this->raiseError("StorageController::retrieveFile() returned false!");
            return false;
        }

        if (!isset($file) ||
            empty($file) ||
            !is_array($file) ||
            !isset($file['hash'], $file['content']) ||
            empty($file['hash']) ||
            empty($file['content'])
        ) {
            $this->raiseError("StorageController::retireveFile() returned an invalid file");
            return false;
        }

        if (strlen($file['content']) != $item->getFileSize()) {
            $this->raiseError("File size of retrieved file does not match archive record!");
            return false;
        }

        if ($file['hash'] != $item->getFileHash()) {
            $this->raiseError("File hash of retrieved file does not match archive record!");
            return false;
        }

        header('Content-Type: application/pdf');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: '. strlen($file['content']));
        print $file['content'];
        return true;
    }

    public function showList($pageno = null, $items_limit = null)
    {
        global $session, $tmpl;

        if (($pending = $this->import->pendingItems()) === false) {
            $this->raiseError(get_class($import) .'::pendingItems() returned false!');
            return false;
        }

        if (isset($pending) || is_numeric($pending)) {
            $tmpl->assign('pending_incoming_items', $pending);
        }

        if (!isset($pageno) || empty($pageno) || !is_numeric($pageno)) {
            if (($current_page = $session->getVariable("{$this->class_name}_current_page")) === false) {
                $current_page = 1;
            }
        } else {
            $current_page = $pageno;
        }

        if (!isset($items_limit) || is_null($items_limit) || !is_numeric($items_limit)) {
            if (($current_items_limit = $session->getVariable("{$this->class_name}_current_items_limit")) === false) {
                $current_items_limit = -1;
            }
        } else {
            $current_items_limit = $items_limit;
        }

        if (empty($this->queue->items)) {
            return parent::showList();
        }

        try {
            $pager = new \Mtlda\Controllers\PagingController(array(
                'delta' => 2,
            ));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load PagingController!');
            return false;
        }

        if (!$pager->setPagingData($this->queue->items)) {
            $this->raiseError(get_class($pager) .'::setPagingData() returned false!');
            return false;
        }

        if (!$pager->setCurrentPage($current_page)) {
            $this->raiseError(get_class($pager) .'::setCurrentPage() returned false!');
            return false;
        }

        if (!$pager->setItemsLimit($current_items_limit)) {
            $this->raiseError(get_class($pager) .'::setItemsLimit() returned false!');
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

        if (!$session->setVariable("{$this->class_name}_current_items_limit", $current_items_limit)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        return parent::showList();
    }

    public function getArchiver(&$data)
    {
        global $mtlda, $tmpl;

        if (!isset($data) || empty($data) || !is_array($data)) {
            $this->raiseError(__METHOD__ .'(), $data parameter is not set!');
            return false;
        }

        if (isset($data['step']) && !empty($data['step']) && is_numeric($data['step'])) {
            $step = $data['step'];
        } else {
            $step = 1;
        }

        if (!isset($data['model']) || empty($data['model']) || $data['model'] != 'queueitem' ||
            !isset($data['id']) || empty($data['id']) || !is_numeric($data['id']) ||
            !isset($data['guid']) || empty($data['guid']) || !$mtlda->isValidGuidSyntax($data['guid'])
        ) {
            $this->raiseError(__METHOD__ .'(), item data is invalid!');
            return false;
        }

        if (($item = $mtlda->loadModel('queueitem', $data['id'], $data['guid'])) === false) {
            $this->raiseError(get_class($mtlda) .'::loadModel() returned false!');
            return false;
        }

        $this->archiveItem = $item;
        $tmpl->assign('item', $item);

        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load KeywordsModel!");
            return false;
        }

        if (($assigned_keywords = $item->getKeywords()) === false) {
            $this->raiseError(get_class($item) .'::getKeywords() returned false!');
            return false;
        }

        $tmpl->assign('keywords', $this->keywords->items);
        $tmpl->assign('assigned_keywords', implode(',', $assigned_keywords));

        switch ($step) {
            case 1:
                $template = "archiver_dialog_step1.tpl";
                break;
            case 2:
                $tmpl->registerPlugin("block", "date_suggestions", array(&$this, "dateSuggestions"), false);
                $template = "archiver_dialog_step2.tpl";
                break;
            case 3:
                $template = "archiver_dialog_step3.tpl";
                break;
            case 4:
                $template = "archiver_dialog_step4.tpl";
                break;
            default:
                $this->raiseError(__METHOD__ .'(), invalid step requested!');
                return false;
                break;
        }

        if ($step < 4) {
            $tmpl->assign('next_step', $step+1);
        }

        if (!isset($template) || empty($template) || !is_string($template)) {
            $this->raiseError(__METHOD__ .'(), no template selected!');
            return false;
        }

        if (($content = $tmpl->fetch($template)) === false) {
            $this->raiseError(get_class($tmpl) ."::fetch({$template}) returned false!");
            return false;
        }

        if (!isset($content) || empty($content) || !is_string($content)) {
            $this->raiseError(get_class($tmpl) ."::fetch({$template}) returned invalid data!");
            return false;
        }

        return $content;
    }

    public function getSplitter(&$data)
    {
        global $mtlda, $tmpl;

        if (!isset($data) || empty($data) || !is_array($data)) {
            $this->raiseError(__METHOD__ .'(), $data parameter is not set!');
            return false;
        }

        if (isset($data['step']) && !empty($data['step']) && is_numeric($data['step'])) {
            $step = $data['step'];
        } else {
            $step = 1;
        }

        if (!isset($data['model']) || empty($data['model']) || $data['model'] != 'queueitem' ||
            !isset($data['id']) || empty($data['id']) || !is_numeric($data['id']) ||
            !isset($data['guid']) || empty($data['guid']) || !$mtlda->isValidGuidSyntax($data['guid'])
        ) {
            $this->raiseError(__METHOD__ .'(), item data is invalid!');
            return false;
        }

        if (($item = $mtlda->loadModel('queueitem', $data['id'], $data['guid'])) === false) {
            $this->raiseError(get_class($mtlda) .'::loadModel() returned false!');
            return false;
        }

        $tmpl->assign('item', $item);

        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load KeywordsModel!");
            return false;
        }

        if (($assigned_keywords = $item->getKeywords()) === false) {
            $this->raiseError(get_class($item) .'::getKeywords() returned false!');
            return false;
        }

        $tmpl->assign('keywords', $this->keywords->items);
        $tmpl->assign('assigned_keywords', implode(',', $assigned_keywords));

        switch ($step) {
            case 1:
                $template = "splitter_dialog_step1.tpl";
                break;
            case 2:
                if (($pages = $this->getPdfPageInfo($item)) === false) {
                    $this->raiseError(__CLASS__ .'::getPdfPageInfo() returned false!');
                    return false;
                }
                $tmpl->assign('page_count', $pages);
                $tmpl->assign("image_safe_link", $item->getId() ."-". $item->getGuid());
                $template = "splitter_dialog_step2.tpl";
                break;
            case 3:
                $template = "splitter_dialog_step3.tpl";
                break;
            case 4:
                $template = "splitter_dialog_step4.tpl";
                break;
            default:
                $this->raiseError(__METHOD__ .'(), invalid step requested!');
                return false;
                break;
        }

        if ($step < 4) {
            $tmpl->assign('next_step', $step+1);
        }

        if (!isset($template) || empty($template) || !is_string($template)) {
            $this->raiseError(__METHOD__ .'(), no template selected!');
            return false;
        }

        if (($content = $tmpl->fetch($template)) === false) {
            $this->raiseError(get_class($tmpl) ."::fetch({$template}) returned false!");
            return false;
        }

        if (!isset($content) || empty($content) || !is_string($content)) {
            $this->raiseError(get_class($tmpl) ."::fetch({$template}) returned invalid data!");
            return false;
        }

        return $content;
    }

    protected function getPdfPageInfo(&$item)
    {
        if (!isset($item) || empty($item)) {
            $this->raiseError(__METHOD__ .'(), $item parameter is invalid!');
            return false;
        }

        if (!is_a($item, 'Mtlda\Models\QueueItemModel')) {
            $this->raiseError(__METHOD__ .'(), can only operate on QueueItemModels!');
            return false;
        }

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load FPDI!');
            return false;
        }

        if (($fqfn = $item->getFilePath()) === false) {
            $this->raiseError(get_class($item) .'::getFilePath() returned false!');
            return false;
        }

        if (!isset($fqfn) || empty($fqfn)) {
            $this->raiseError(get_class($item) .'::getFilePath() returned an invalid file name!');
            return false;
        }

        if (!file_exists($fqfn)) {
            $this->raiseError(__METHOD__ ."(), file {$fqfn} does not exist!");
            return false;
        }

        if (!is_readable($fqfn)) {
            $this->raiseError(__METHOD__ ."(), file {$fqfn} is not readable!");
            return false;
        }

        try {
            $page_count = $pdf->setSourceFile($fqfn);
        } catch (\Exception $e) {
            $this->raiseError(getClass($pdf) .'::setSourceFile() has thrown an exception! '. $e->getMessage());
            return false;
        }

        try {
            @$pdf->cleanUp();
        } catch (\Exception $e) {
            $this->raiseError(get_class($pdf) .'::cleanUp() has thrown an exception! '. $e->getMessage());
            return false;
        }

        if (!isset($page_count)) {
            return false;
        }

        return $page_count;
    }

    public function dateSuggestions($params, $content, &$smarty, &$repeat)
    {
        if (!isset($this->dateSuggestions)) {
            $this->buildDateSuggestions();
        }

        $index = $smarty->getTemplateVars("smarty.IB.suggestions_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->dateSuggestions)) {
            $repeat = false;
            return $content;
        }

        if (isset($this->dateSuggestions[$index])) {
            $smarty->assign("suggest", $this->dateSuggestions[$index]);
        }

        $index++;
        $smarty->assign("smarty.IB.suggestions_list.index", $index);
        $repeat = true;

        return $content;
    }

    protected function buildDateSuggestions()
    {
        if (!isset($this->archiveItem) || empty($this->archiveItem)) {
            $this->raiseError(__METHOD__ .'(), have no item to operate on!');
            return false;
        }

        $sources = array();

        if ($this->archiveItem->hasTitle()) {
            if (($title = $this->archiveItem->getTitle()) === false) {
                $this->raiseError(get_class($this->archiveItem) .'::getTitle() returned false!');
                return false;
            }
            array_push($sources, $title);
        }
        if (($filename = $this->archiveItem->getFileName()) === false) {
            $this->raiseError(get_class($this->archiveItem) .'::getFileName() returned false');
            return false;
        }
        array_push($sources, $filename);
        if (($sources = array_unique($sources)) === false) {
            $this->raiseError(__METHOD__ .'(), failed to filter sources!');
            return false;
        }

        $regexp_map = array(
            '/(\d\d)/' => 'YY',
            '/(\d\d\d\d)/' => 'YYYY',
            '/(\d\d)-(\d\d)/' => 'YY-MM',
            '/(\d\d)-(\d\d)/' => 'YY-MM',
            '/(\d\d)-(\d\d)/' => 'MM-YY',
            '/(\d\d\d\d)-(\d\d)/' => 'YYYY-MM',
            '/(\d\d)-(\d\d\d\d)/' => 'MM-YYYY',
            '/(\d\d\d\d)(\d\d)(\d\d)/' => 'YYYYMMDD',
            '/(\d\d\d\d)\.(\d\d)\.(\d\d)/' => 'YYYYMMDD',
            '/(\d\d)(\d\d)(\d\d\d\d)/' => 'DDMMYYYY',
            '/(\d\d)\.(\d\d)\.(\d\d\d\d)/' => 'DDMMYYYY',
        );

        $suggestions = array();

        foreach ($sources as $source) {
            foreach ($regexp_map as $pattern => $map) {
                if (!preg_match($pattern, $source, $matches)) {
                    continue;
                }
                if ($map == 'YY-MM' && count($matches) == 3) {
                    $suggest = "20{$matches[1]}-{$matches[2]}-01";
                } elseif ($map == 'MM-YY' && count($matches) == 3) {
                    $suggest = "20{$matches[2]}-{$matches[1]}-01";
                } elseif ($map == 'MM-YYYY' && count($matches) == 3) {
                    $suggest = "{$matches[2]}-{$matches[1]}-01";
                } elseif ($map == 'YYYY-MM' && count($matches) == 3) {
                    $suggest = "{$matches[1]}-{$matches[2]}-01";
                } elseif ($map == 'YYYYMMDD' && count($matches) == 4) {
                    $suggest = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
                } elseif ($map == 'DDMMYYYY' && count($matches) == 4) {
                    $suggest = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
                } elseif ($map == 'YY' && count($matches) == 2) {
                    $suggest = sprintf("20%d-01-01", $matches[1]);
                    array_push($suggestions, $suggest);
                    $suggest = sprintf("20%d-12-31", $matches[1]);
                } elseif ($map == 'YYYY' && count($matches) == 2) {
                    $suggest = sprintf("%d-01-01", $matches[1]);
                    array_push($suggestions, $suggest);
                    $suggest = sprintf("%d-12-31", $matches[1]);
                }
                array_push($suggestions, $suggest);
            }
        }

        //
        // remove unusual dates.
        //
        $this->dateSuggestions = array_filter($suggestions, function ($date) {
            if (($parsed = date_parse($date)) === false) {
                return false;
            }
            if ($parsed['year'] < 1900 || $parsed['year'] > 2100) {
                return false;
            }
            if ($parsed['month'] < 1 || $parsed['month'] > 12) {
                return false;
            }
            if ($parsed['day'] < 1 || $parsed['day'] > 31) {
                return false;
            }
            return true;
        });

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
