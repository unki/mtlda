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

class QueueView extends DefaultView
{
    protected static $view_class_name = 'queue';
    protected static $view_item_name = 'QueueItem';

    //protected $queue_avail_items;
    //protected $queue_items;
    protected $archiveItem;
    protected $keywords;

    protected $import;
    protected $sugctrl;

    protected $dateSuggestions;
    protected $keywordSuggestions;
    protected $keywordSuggestionsSimilar;

    /**
     * constructor
     *
     * @params none
     * @return void
     */
    public function __construct()
    {
        try {
            $queue = new \Mtlda\Models\QueueModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load QueueModel!', true);
            return false;
        }

        if (!$this->setViewData($queue)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        try {
            $this->import = new \Mtlda\Controllers\ImportController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ImportController!', true);
            return false;
        }

        if (!$this->addMode('archive')) {
            static::raiseError(__CLASS__ .'::addMode() returned false!', true);
            return false;
        }

        if (!$this->addMode('split')) {
            static::raiseError(__CLASS__ .'::addMode() returned false!', true);
            return false;
        }

        if (!$this->addContent('archiver')) {
            static::raiseError(__CLASS__ .'::addContent() returned false!', true);
            return false;
        }

        if (!$this->addContent('splitter')) {
            static::raiseError(__CLASS__ .'::addContent() returned false!', true);
            return false;
        }

        try {
            $this->sugctrl = new \Mtlda\Controllers\SuggestionsController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load SuggestionsController!', false, $e);
            return false;
        }

        parent::__construct();
    }

    /**
     * show item
     *
     * @params int $id
     * @params string $guid
     * @return bool
     */
    public function showItem($id, $guid)
    {
        global $mtlda;

        if (empty($id) || !$mtlda->isValidId($id)) {
            static::raiseError("Require a valid \$id to show!");
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            static::raiseError("Require a valid \$guid to show!");
            return false;
        }

        try {
            $item = new \Mtlda\Models\QueueItemModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError("Failed to load QueueItemModel({$id}, {$guid})!");
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError("Failed to load StorageController!");
            return false;
        }

        if (!$file = $storage->retrieveFile($item)) {
            static::raiseError("StorageController::retrieveFile() returned false!");
            return false;
        }

        if (!isset($file) ||
            empty($file) ||
            !is_array($file) ||
            !isset($file['hash'], $file['content']) ||
            empty($file['hash']) ||
            empty($file['content'])
        ) {
            static::raiseError("StorageController::retireveFile() returned an invalid file");
            return false;
        }

        if (strlen($file['content']) != $item->getFileSize()) {
            static::raiseError("File size of retrieved file does not match archive record!");
            return false;
        }

        if ($file['hash'] != $item->getFileHash()) {
            static::raiseError("File hash of retrieved file does not match archive record!");
            return false;
        }

        header('Content-Type: application/pdf');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: '. strlen($file['content']));
        print $file['content'];

        return true;
    }

    /**
     * getArchiver()
     *
     * get the archiver dialog steps.
     *
     * @params array $data
     * @return bool|string
     */
    public function getArchiver(&$data)
    {
        global $mtlda, $tmpl;

        if (!isset($data) || empty($data) || !is_array($data)) {
            static::raiseError(__METHOD__ .'(), $data parameter is not set!');
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
            static::raiseError(__METHOD__ .'(), item data is invalid!');
            return false;
        }

        if (($item = $mtlda->loadModel('queueitem', $data['id'], $data['guid'])) === false) {
            static::raiseError(get_class($mtlda) .'::loadModel() returned false!');
            return false;
        }

        $this->archiveItem = $item;
        $tmpl->assign('item', $item);

        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            static::raiseError("Failed to load KeywordsModel!");
            return false;
        }

        $tmpl->assign('keywords', $this->keywords->getItems());
        $tmpl->assign("item_safe_link", $item->getIdx() ."-". $item->getGuid());

        switch ($step) {
            case 1:
                $template = "archiver_dialog_step1.tpl";
                break;
            case 2:
                if (!isset($this->dateSuggestions) && !is_array($this->dateSuggestions)) {
                    if (!$this->buildDateSuggestions($item)) {
                        static::raiseError(__CLASS__ .'::buildDateSuggestions() returned false!');
                        return false;
                    }

                    if (!empty($this->dateSuggestions)) {
                        $tmpl->assign('has_date_suggestions', true);
                    }
                }

                if (!isset($this->keywordSuggestions) && !is_array($this->keywordSuggestions)) {
                    if (!$this->buildKeywordSuggestions($item)) {
                        static::raiseError(__CLASS__ .'::buildKeywordSuggestions() returned false!');
                        return false;
                    }

                    if (!empty($this->keywordSuggestions)) {
                        $tmpl->assign('has_keyword_suggestions', true);
                    }

                    if (!empty($this->keywordSuggestionsSimilar)) {
                        $tmpl->assign('has_keyword_suggestions_similar', true);
                    }
                }

                $tmpl->registerPlugin("block", "date_suggestions", array(&$this, "dateSuggestionsList"), false);
                $tmpl->registerPlugin("block", "keyword_suggestions", array(&$this, "keywordSuggestionsList"), false);
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
                static::raiseError(__METHOD__ .'(), invalid step requested!');
                return false;
                break;
        }

        if ($step < 4) {
            $tmpl->assign('next_step', $step+1);
        }

        if (!isset($template) || empty($template) || !is_string($template)) {
            static::raiseError(__METHOD__ .'(), no template selected!');
            return false;
        }

        if (($content = $tmpl->fetch($template)) === false) {
            static::raiseError(get_class($tmpl) ."::fetch({$template}) returned false!");
            return false;
        }

        if (!isset($content) || empty($content) || !is_string($content)) {
            static::raiseError(get_class($tmpl) ."::fetch({$template}) returned invalid data!");
            return false;
        }

        return $content;
    }

    /**
     * getSplitter()
     *
     * get the splitter dialog steps.
     *
     * @params array $data
     * @return bool|string
     */
    public function getSplitter(&$data)
    {
        global $mtlda, $tmpl;

        if (!isset($data) || empty($data) || !is_array($data)) {
            static::raiseError(__METHOD__ .'(), $data parameter is not set!');
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
            static::raiseError(__METHOD__ .'(), item data is invalid!');
            return false;
        }

        if (($item = $mtlda->loadModel('queueitem', $data['id'], $data['guid'])) === false) {
            static::raiseError(get_class($mtlda) .'::loadModel() returned false!');
            return false;
        }

        $tmpl->assign('item', $item);

        switch ($step) {
            case 1:
                $template = "splitter_dialog_step1.tpl";
                break;
            case 2:
                if (($pages = $this->getPdfPageInfo($item)) === false) {
                    static::raiseError(__CLASS__ .'::getPdfPageInfo() returned false!');
                    return false;
                }
                $tmpl->assign('page_count', $pages);
                $tmpl->assign("image_safe_link", $item->getIdx() ."-". $item->getGuid());
                $template = "splitter_dialog_step2.tpl";
                break;
            case 3:
                $template = "splitter_dialog_step3.tpl";
                break;
            case 4:
                $template = "splitter_dialog_step4.tpl";
                break;
            default:
                static::raiseError(__METHOD__ .'(), invalid step requested!');
                return false;
                break;
        }

        if ($step < 4) {
            $tmpl->assign('next_step', $step+1);
        }

        if (!isset($template) || empty($template) || !is_string($template)) {
            static::raiseError(__METHOD__ .'(), no template selected!');
            return false;
        }

        if (($content = $tmpl->fetch($template)) === false) {
            static::raiseError(get_class($tmpl) ."::fetch({$template}) returned false!");
            return false;
        }

        if (!isset($content) || empty($content) || !is_string($content)) {
            static::raiseError(get_class($tmpl) ."::fetch({$template}) returned invalid data!");
            return false;
        }

        return $content;
    }

    protected function getPdfPageInfo(&$item)
    {
        if (!isset($item) || empty($item)) {
            static::raiseError(__METHOD__ .'(), $item parameter is invalid!');
            return false;
        }

        if (!is_a($item, 'Mtlda\Models\QueueItemModel')) {
            static::raiseError(__METHOD__ .'(), can only operate on QueueItemModels!');
            return false;
        }

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load FPDI!');
            return false;
        }

        if (($fqfn = $item->getFilePath()) === false) {
            static::raiseError(get_class($item) .'::getFilePath() returned false!');
            return false;
        }

        if (!isset($fqfn) || empty($fqfn)) {
            static::raiseError(get_class($item) .'::getFilePath() returned an invalid file name!');
            return false;
        }

        if (!file_exists($fqfn)) {
            static::raiseError(__METHOD__ ."(), file {$fqfn} does not exist!");
            return false;
        }

        if (!is_readable($fqfn)) {
            static::raiseError(__METHOD__ ."(), file {$fqfn} is not readable!");
            return false;
        }

        try {
            $page_count = $pdf->setSourceFile($fqfn);
        } catch (\Exception $e) {
            static::raiseError(getClass($pdf) .'::setSourceFile() has thrown an exception! '. $e->getMessage());
            return false;
        }

        try {
            @$pdf->cleanUp();
        } catch (\Exception $e) {
            static::raiseError(get_class($pdf) .'::cleanUp() has thrown an exception! '. $e->getMessage());
            return false;
        }

        if (!isset($page_count)) {
            return false;
        }

        return $page_count;
    }

    public function dateSuggestionsList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars("smarty.IB.date_suggestions_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!isset($this->dateSuggestions) ||
            empty($this->dateSuggestions) ||
            !is_array($this->dateSuggestions) ||
            $index >= count($this->dateSuggestions)
        ) {
            $repeat = false;
            return $content;
        }

        $smarty->assign("suggest", $this->dateSuggestions[$index]);

        $index++;
        $smarty->assign("smarty.IB.date_suggestions_list.index", $index);
        $repeat = true;

        return $content;
    }

    public function keywordSuggestionsList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars("smarty.IB.keyword_suggestions_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!isset($this->keywordSuggestions) ||
            empty($this->keywordSuggestions) ||
            !is_array($this->keywordSuggestions) ||
            $index >= count($this->keywordSuggestions)
        ) {
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
        $index = $smarty->getTemplateVars("smarty.IB.keyword_suggestions_similar_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!isset($this->keywordSuggestionsSimilar) ||
            empty($this->keywordSuggestionsSimilar) ||
            !is_array($this->keywordSuggestionsSimilar) ||
            $index >= count($this->keywordSuggestionsSimilar)
        ) {
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

    protected function buildDateSuggestions($item)
    {
        if (($this->dateSuggestions = $this->sugctrl->getDateSuggestions($item)) === false) {
            static::raiseError(get_class($this->sugctrl) .'::getDateSuggestions() returned false!');
            return false;
        }

        return true;
    }

    protected function buildKeywordSuggestions($item)
    {
        if (($keywords = $this->sugctrl->getKeywordSuggestions($item)) === false) {
            static::raiseError(get_class($this->sugctrl) .'::getKeywordSuggestions() returned false!');
            return false;
        }

        if (!isset($keywords) || empty($keywords)) {
            return true;
        }

        if (array_key_exists('match', $keywords)) {
            $this->keywordSuggestions = $keywords['match'];
        }

        if (!array_key_exists('similar', $keywords)) {
            return true;
        }

        $this->keywordSuggestionsSimilar = $keywords['similar'];
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
