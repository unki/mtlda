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
    protected $keywordSuggestions;
    protected $keywordSuggestionsSimilar;
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

        try {
            $this->archive = new \Mtlda\Models\ArchiveModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load ArchiveModel!', false, $e);
            return false;
        }

        $tmpl->assign('keywords', $this->keywords->items);
        $tmpl->assign("item_safe_link", $item->getId() ."-". $item->getGuid());

        switch ($step) {
            case 1:
                $template = "archiver_dialog_step1.tpl";
                break;
            case 2:
                if (!isset($this->dateSuggestions)) {
                    $this->buildDateSuggestions();
                }
                if (!isset($this->keywordSuggestions)) {
                    $this->buildKeywordSuggestions();
                }
                $tmpl->registerPlugin("block", "date_suggestions", array(&$this, "dateSuggestions"), false);
                $tmpl->registerPlugin("block", "keyword_suggestions", array(&$this, "keywordSuggestions"), false);
                $tmpl->registerPlugin(
                    "block",
                    "keyword_suggestions_similar",
                    array(&$this, "keywordSuggestionsSimilar"),
                    false
                );
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

        $index = $smarty->getTemplateVars("smarty.IB.date_suggestions_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->dateSuggestions)) {
            $repeat = false;
            return $content;
        }

        $smarty->assign("suggest", $this->dateSuggestions[$index]);

        $index++;
        $smarty->assign("smarty.IB.date_suggestions_list.index", $index);
        $repeat = true;

        return $content;
    }

    protected function buildDateSuggestions()
    {
        global $tmpl;

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

        if ($this->archiveItem->hasIndices()) {
            if (($indices = $this->archiveItem->getIndices()) === false) {
                $this->raiseError(get_class($this->archiveItem) .'::getIndices() returned false!');
                return false;
            }
            if (!isset($indices) || empty($indices) || !is_array($indices)) {
                $this->raiseError(get_class($this->archiveItem) .'::getIndices() returned invalid data!');
                return false;
            }
            foreach ($indices as $index) {
                if (($text = $index->getDocumentText()) === false) {
                    $this->raiseError(get_class($index) .'::getDocumentText() returned false!');
                    return false;
                }
                array_push($sources, $text);
            }
        }

        if (is_a($this->archiveItem, 'Mtlda\Models\DocumentModel') &&
            $this->archiveItem->hasProperties()
        ) {
            if (($properties = $this->archiveItem->getProperties()) === false) {
                $this->raiseError(get_class($this->archiveItem) .'::getProperties() returned false!');
                return false;
            }
            if (!isset($properties) || empty($properties) || !is_array($properties)) {
                $this->raiseError(get_class($this->archiveItem) .'::getProperties() returned invalid data!');
                return false;
            }
            foreach ($properties as $property) {
                if (($value = $property->getDocumentValue()) === false) {
                    $this->raiseError(get_class($property) .'::getDocumentValue() returned false!');
                    return false;
                }
                array_push($sources, $value);
            }
        }

        if (($sources = array_unique($sources)) === false) {
            $this->raiseError(__METHOD__ .'(), failed to filter sources!');
            return false;
        }

        $regexp_map = array(
            '/(?<year>\d\d)/' => 'YY',
            '/(?<year>\d\d\d\d)/' => 'YYYY',
            '/(?<year>\d\d)-(?<month>\d\d)/' => 'YYMM',
            '/(?<year>\d\d)\.(?<month>\d\d)/' => 'YYMM',
            '/(?<month>\d\d)-(?<year>\d\d)/' => 'MMYY',
            '/(?<month>\d\d)\.(?<year>\d\d)/' => 'MMYY',
            '/(?<year>\d\d\d\d)-(?<month>\d\d)/' => 'YYYYMM',
            '/(?<year>\d\d\d\d).(?<month>\d\d)/' => 'YYYYMM',
            '/(?<month>\d\d)-(?<year>\d\d\d\d)/' => 'MMYYYY',
            '/(?<month>\d\d).(?<year>\d\d\d\d)/' => 'MMYYYY',
            '/(?<year>\d\d\d\d)(?<month>\d\d)(?<day>\d\d)/' => 'YYYYMMDD',
            '/(?<year>\d\d\d\d)\.(?<month>\d\d)\.(?<day>\d\d)/' => 'YYYYMMDD',
            '/(?<day>\d\d)(?<month>\d\d)(?<year>\d\d\d\d)/' => 'DDMMYYYY',
            '/(?<day>\d\d)\.(?<month>\d\d)\.(?<year>\d\d\d\d)/' => 'DDMMYYYY',
        );

        $suggestions = array();

        foreach ($sources as $source) {
            foreach ($regexp_map as $pattern => $map) {
                $year = null;
                $month = null;
                $date = null;
                if (($result = preg_match_all($pattern, $source, $matches, PREG_SET_ORDER)) === 0) {
                    continue;
                }

                if ($result === false) {
                    $this->raiseError(__METHOD__ .'(), an error in preg_match_all() occured! '. preg_last_error());
                    return false;
                }

                if (!isset($matches) || empty($matches) || !is_array($matches)) {
                    continue;
                }

                foreach ($matches as $match) {
                    if ($map == 'YYMM' && $this->requireArrayKeys($match, array('year', 'month'))) {
                        $year = sprintf("20%02d", $match['year']);
                        $month = $match['month'];
                    } elseif ($map == 'MMYY' && $this->requireArrayKeys($match, array('year', 'month'))) {
                        $month = $match['month'];
                        $year = sprintf("20%02d", $match['year']);
                    } elseif ($map == 'MMYYYY' && $this->requireArrayKeys($match, array('month', 'year'))) {
                        $month = $match['month'];
                        $year = $match['year'];
                    } elseif ($map == 'YYYYMM' && $this->requireArrayKeys($match, array('year', 'month'))) {
                        $year = $match['year'];
                        $month = $match['month'];
                    } elseif ($map == 'YYYYMMDD' && $this->requireArrayKeys($match, array('year', 'month'))) {
                        $year = $match['year'];
                        $month = $match['month'];
                        $day = $match['day'];
                    } elseif ($map == 'DDMMYYYY' && $this->requireArrayKeys($match, array('day', 'month', 'year'))) {
                        $day = $match['day'];
                        $month = $match['month'];
                        $year = $match['year'];
                    } elseif ($map == 'YY' && $this->requireArrayKeys($match, array('year'))) {
                        $year = sprintf("20%02d", $match['year']);
                    } elseif ($map == 'YYYY' && $this->requireArrayKeys($match, array('year'))) {
                        $year = $match['year'];
                    }

                    if (isset($day) && isset($month) && isset($year)) {
                        array_push($suggestions, sprintf("%04d-%02d-%02d", $year, $month, $day));
                    }
                    if (isset($month) && isset($year)) {
                        $first = sprintf("%04d-%02d-01", $year, $month);
                        array_push($suggestions, $first);
                        $last = date("t", strtotime($first));
                        $last = sprintf("%04d-%02d-%02d", $year, $month, $last);
                        array_push($suggestions, $last);
                    }
                    if (isset($year)) {
                        $first = sprintf("%04d-01-01", $year);
                        array_push($suggestions, $first);
                        $last = sprintf("%04d-12-31", $year);
                        array_push($suggestions, $last);
                    }
                }
            }
        }

        //
        // remove unusual dates.
        //
        $this->dateSuggestions = array_filter($suggestions, function ($date) {
            if (($parsed = date_parse($date)) === false) {
                return false;
            }
            if (isset($parsed['errors']) &&
                is_array($parsed['errors']) &&
                !empty($parsed['errors'])
            ) {
                return false;
            }
            if ($parsed['year'] < 1900 || $parsed['year'] > date('Y')) {
                return false;
            }
            if ($parsed['month'] < 1 || $parsed['month'] > 12) {
                return false;
            }
            if ($parsed['day'] < 1 || $parsed['day'] > 31 ||
                $parsed['day'] > date('t', strtotime("{$parsed['year']}-{$parsed['month']}-01"))
            ) {
                return false;
            }
            return true;
        });

        if (($this->dateSuggestions = array_unique($this->dateSuggestions)) === false) {
            $this->raiseError(__METHOD__ .'(), array_unique() returned false!');
            return false;
        }

        if (!sort($this->dateSuggestions)) {
            $this->raiseError(__METHOD__ .'(), sort() returned false!');
            return false;
        }

        if (isset($this->dateSuggestions) && !empty($this->dateSuggestions)) {
            $tmpl->assign('has_date_suggestions', true);
        }

        return true;
    }

    protected function buildKeywordSuggestions()
    {
        global $tmpl;

        $archive_item_keywords = array();

        if (!isset($this->archiveItem) || empty($this->archiveItem)) {
            $this->raiseError(__METHOD__ .'(), have no item to operate on!');
            return false;
        }

        if (!isset($this->keywords->items) || empty($this->keywords->items) ||
            count($this->keywords->items) < 1) {
            return true;
        }

        if ($this->archiveItem->hasKeywords()) {
            if (($assigned_keywords = $this->archiveItem->getKeywords()) === false) {
                $this->raiseError(get_class($this->archiveItem) .'::getKeywords() returned false!');
                return false;
            }
            foreach ($assigned_keywords as $idx) {
                try {
                    $keyword = new \Mtlda\Models\KeywordModel($idx);
                } catch (\Exception $e) {
                    $this->raiseError(__METHOD__ .'(), failed to load KeywordModel!', false, $e);
                    return false;
                }
                if (($name = $keyword->getName()) === false) {
                    $this->raiseError(get_class($keyword) .'::getName() returned false!');
                    return false;
                }
                array_push($archive_item_keywords, $name);
            }
        }

        $sources = array();

        if ($this->archiveItem->hasTitle()) {
            if (($title = $this->archiveItem->getTitle()) === false) {
                $this->raiseError(get_class($this->archiveItem) .'::getTitle() returned false!');
                return false;
            }
            array_push($sources, $title);
        }

        if (($filename = $this->archiveItem->getFileNameBase()) === false) {
            $this->raiseError(get_class($this->archiveItem) .'::getFileName() returned false');
            return false;
        }
        array_push($sources, $filename);

        if ($this->archiveItem->hasIndices()) {
            if (($indices = $this->archiveItem->getIndices()) === false) {
                $this->raiseError(get_class($this->archiveItem) .'::getIndices() returned false!');
                return false;
            }
            if (!isset($indices) || empty($indices) || !is_array($indices)) {
                $this->raiseError(get_class($this->archiveItem) .'::getIndices() returned invalid data!');
                return false;
            }
            foreach ($indices as $index) {
                if (($text = $index->getDocumentText()) === false) {
                    $this->raiseError(get_class($index) .'::getDocumentText() returned false!');
                    return false;
                }
                array_push($sources, $text);
            }
        }

        if (is_a($this->archiveItem, 'Mtlda\Models\DocumentModel') &&
            $this->archiveItem->hasProperties()
        ) {
            if (($properties = $this->archiveItem->getProperties()) === false) {
                $this->raiseError(get_class($this->archiveItem) .'::getProperties() returned false!');
                return false;
            }
            if (!isset($properties) || empty($properties) || !is_array($properties)) {
                $this->raiseError(get_class($this->archiveItem) .'::getProperties() returned invalid data!');
                return false;
            }
            foreach ($properties as $property) {
                if (($value = $property->getDocumentValue()) === false) {
                    $this->raiseError(get_class($property) .'::getDocumentValue() returned false!');
                    return false;
                }
                array_push($sources, $value);
            }
        }

        if (($sources = array_unique($sources)) === false) {
            $this->raiseError(__METHOD__ .'(), failed to filter sources!');
            return false;
        }

        $existing_keywords = array();

        foreach ($this->keywords->items as $keyword) {
            if (($name = $keyword->getName()) === false) {
                $this->raiseError(get_class($keyword) .'::getName() returned false!');
                return false;
            }
            array_push($existing_keywords, $name);
        }

        $suggestions = array();
        $words = array();

        foreach ($sources as $source) {
            $source = str_replace('_', ' ', $source);
            $source = preg_replace('/[^[:alnum:][:space:]]/u', '', $source);
            if (count(($found_words = str_word_count($source, 1))) < 1) {
                continue;
            }
            array_walk($found_words, function (&$word, $key) {
                $word = trim($word);
                return true;
            });
            $found_words = array_filter($found_words, function ($word) {
                if (strlen($word) < 1) {
                    return false;
                }
                return true;
            });
            if (count($found_words) < 1) {
                continue;
            }
            foreach ($found_words as $word) {
                array_push($words, $word);
            }
        }

        foreach ($words as $key => $word) {
            if (isset($existing_keywords) &&
                !empty($existing_keywords) &&
                ($matching_keyword = preg_grep("/{$word}/", $existing_keywords)) &&
                isset($matching_keyword) &&
                !empty($matching_keyword) &&
                is_array($matching_keyword) &&
                count($matching_keyword) > 0 &&
                ($matching_keyword = array_shift($matching_keyword))
            ) {
                $words[$key] = $matching_keyword;
                continue;
            }
            unset($words[$key]);
        }

        if (($words = array_count_values($words)) === false) {
            $this->raiseError(__METHOD__ .'(), array_count_values() returned false!');
            return false;
        }

        if (!arsort($words, SORT_NUMERIC)) {
            $this->raiseError(__METHOD__ .'(), arsort() returned false!');
            return false;
        }

        if (($words = array_slice($words, 0, 10)) === false) {
            $this->raiseError(__METHOD__ .'(), array_slice() returned false!');
            return false;
        }

        foreach ($words as $word => $occur) {
            if (in_array($word, $archive_item_keywords)) {
                continue;
            }
            $this->keywordSuggestions[$word] = $occur;
        }

        if (isset($this->keywordSuggestions) && !empty($this->keywordSuggestions)) {
            $tmpl->assign('has_keyword_suggestions', true);
        }

        if (!isset($this->archive->items) ||
            empty($this->archive->items)
        ) {
            return true;
        }

        $items = array();
        $filenames = array();
        $titles = array();
        $sources = array();

        foreach ($this->archive->items as $document) {
            if (($idx = $document->getId()) === false) {
                $this->raiseError(get_class($document) .'::getId() returned false!');
                return false;
            }
            $items[$idx] = $document;
            if (($filename = $document->getFileName()) === false) {
                $this->raiseError(get_class($document) .'::getFileName() returned false!');
                return false;
            }
            if ($document->hasTitle() && ($title = $document->getTitle()) === false) {
                $this->raiseError(get_class($document) .'::getTitle() returned false!');
                return false;
            }
            $filenames[$idx] = $filename;
            $titles[$idx] = $title;
        }

        if ($this->archiveItem->hasTitle()) {
            $archive_item_title = $this->archiveItem->getTitle();
        }
        $archive_item_filename = $this->archiveItem->getFileName();

        foreach ($filenames as $idx => $filename) {
            if (($diff = levenshtein($archive_item_filename, $filename)) === -1) {
                continue;
            }
            if ($diff > 10) {
                continue;
            }
            $document = $items[$idx];
            if (!$document->hasKeywords()) {
                continue;
            }
            if (($keywords = $document->getKeywords()) === false) {
                $this->raiseError(get_class($document) .'::getKeywords() returned false!');
                return false;
            }
            if (!isset($keywords) || empty($keywords)) {
                continue;
            }
            foreach ($keywords as $idx) {
                try {
                    $keyword = new \Mtlda\Models\KeywordModel($idx);
                } catch (\Exception $e) {
                    $this->raiseError(__METHOD__ .'(), failed to load KeywordModel!', false, $e);
                    return false;
                }
                if (($name = $keyword->getName()) === false) {
                    $this->raiseErrror(get_class($keyword) .'::getName() returned false!');
                    return false;
                }
                array_push($sources, $name);
            }
        }

        if (isset($archive_item_title)) {
            foreach ($titles as $idx => $title) {
                if (($diff = levenshtein($archive_item_title, $title)) === -1) {
                    continue;
                }
                if ($diff > 10) {
                    continue;
                }
                $document = $items[$idx];
                if (!$document->hasKeywords()) {
                    continue;
                }
                if (($keywords = $document->getKeywords()) === false) {
                    $this->raiseError(get_class($document) .'::getKeywords() returned false!');
                    return false;
                }
                if (!isset($keywords) || empty($keywords)) {
                    continue;
                }
                foreach ($keywords as $idx) {
                    try {
                        $keyword = new \Mtlda\Models\KeywordModel($idx);
                    } catch (\Exception $e) {
                        $this->raiseError(__METHOD__ .'(), failed to load KeywordModel!', false, $e);
                        return false;
                    }
                    if (($name = $keyword->getName()) === false) {
                        $this->raiseErrror(get_class($keyword) .'::getName() returned false!');
                        return false;
                    }
                    array_push($sources, $name);
                }
            }
        }

        if (($words = array_count_values($sources)) === false) {
            $this->raiseError(__METHOD__ .'(), array_count_values() returned false!');
            return false;
        }

        if (!arsort($words, SORT_NUMERIC)) {
            $this->raiseError(__METHOD__ .'(), arsort() returned false!');
            return false;
        }

        if (($words = array_slice($words, 0, 10)) === false) {
            $this->raiseError(__METHOD__ .'(), array_slice() returned false!');
            return false;
        }

        foreach ($words as $word => $occur) {
            if (in_array($word, $archive_item_keywords)) {
                continue;
            }
            $this->keywordSuggestionsSimilar[$word] = $occur;
        }

        if (isset($this->keywordSuggestionsSimilar) && !empty($this->keywordSuggestionsSimilar)) {
            $tmpl->assign('has_keyword_suggestions_similar', true);
        }

        return true;
    }

    public function keywordSuggestions($params, $content, &$smarty, &$repeat)
    {
        if (!isset($this->keywordSuggestions)) {
            $this->buildKeywordSuggestions();
        }

        $index = $smarty->getTemplateVars("smarty.IB.keyword_suggestions_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count(array_keys($this->keywordSuggestions))) {
            $repeat = false;
            return $content;
        }

        $key = array_keys($this->keywordSuggestions)[$index];
        $value = $this->keywordSuggestions[$key];

        $smarty->assign("keyword", $key);
        $smarty->assign("occurrences", $value);

        $index++;
        $smarty->assign("smarty.IB.keyword_suggestions_list.index", $index);
        $repeat = true;

        return $content;
    }

    public function keywordSuggestionsSimilar($params, $content, &$smarty, &$repeat)
    {
        if (!isset($this->keywordSuggestionsSimilar)) {
            $this->buildKeywordSuggestions();
        }

        $index = $smarty->getTemplateVars("smarty.IB.keyword_suggestions_similar_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count(array_keys($this->keywordSuggestionsSimilar))) {
            $repeat = false;
            return $content;
        }

        $key = array_keys($this->keywordSuggestionsSimilar)[$index];
        $value = $this->keywordSuggestionsSimilar[$key];

        $smarty->assign("keyword", $key);
        $smarty->assign("occurrences", $value);

        $index++;
        $smarty->assign("smarty.IB.keyword_suggestions_similar_list.index", $index);
        $repeat = true;

        return $content;
    }

    public function requireArrayKeys($haystack, $needles)
    {
        if (!isset($haystack) || empty($haystack) || (!is_string($haystack) && !is_array($haystack))) {
            $this->raiseError(__METHOD__ .'(), $haystack parameter is invalid!');
            return false;
        }

        if (!isset($needles) || empty($needles) || (!is_string($needles) && !is_array($needles))) {
            $this->raiseError(__METHOD__ .'(), $needles parameter is invalid!');
            return false;
        }

        if (is_string($haystack)) {
            $haystack = array($haystack => null);
        }

        if (is_string($needles)) {
            $needles = array($needles);
        }

        $result = true;

        foreach ($needles as $needle) {
            if (!array_key_exists($needle, $haystack)) {
                $result = false;
            }
        }

        return $result;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
