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

namespace Mtlda\Views ;

class SearchView extends DefaultView
{
    public $class_name = 'search';
    protected $matches;
    protected $avail_matches;

    public function __construct()
    {
        global $mtlda, $query, $tmpl;

        try {
            $search = new \Mtlda\Controllers\SearchController;
        } catch (\Exception $e) {
            $mtlda->raiseError(__METHOD__ .'(), failed to load SearchController!', true);
            return false;
        }

        if (isset($query->params['search'])) {
            $searchquery = $query->params['search'];
        }

        if (!$search->search($searchquery)) {
            $mtlda->raiseError(get_class($search) .'::search() returned false!', true);
            return false;
        }

        if (($this->matches = $search->getResults()) === false) {
            $mtlda->raiseError(get_class($search) .'::getResults() returned false!', true);
            return false;
        }

        if (!$this->orderMatches()) {
            $mtlda->raiseError(__CLASS__ .'::orderMatches() returned false!', true);
            return false;
        }

        $tmpl->registerPlugin("block", "result_list", array(&$this, "listSearchResults"), false);
        return true;
    }

    public function show()
    {
        global $tmpl;

        if (!isset($this->matches) ||
            empty($this->matches)
        ) {
            $tmpl->assign('no_result', true);
            return $tmpl->fetch('search.tpl');
        }

        return $tmpl->fetch('search.tpl');
    }

    public function listSearchResults($params, $content, &$smarty, &$repeat)
    {
        global $mtlda;

        $index = $smarty->getTemplateVars("smarty.IB.search_list.index");

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->avail_matches)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->avail_matches[$index];
        $item =  $this->matches[$item_idx];

        $smarty->assign("item", $item);

        if (!preg_match('/^(archive|keyword|queue)/', $item_idx, $parts)) {
            $mtlda->raiseError(__METHOD__ .'(), unknown item found!');
            return false;
        }

        $smarty->assign('type', $parts[1]);

        if (!($idx = $item->getId())) {
            $mtlda->raiseError(get_class($item) .'::getId() returned false!');
            return false;
        }

        if (!($guid = $item->getGuid())) {
            $mtlda->raiseError(get_class($item) .'::getGuid() returned false!');
            return false;
        }

        $smarty->assign('item_safe_url', $idx .'-'. $guid);

        $index++;
        $smarty->assign("smarty.IB.search_list.index", $index);
        $repeat = true;

        return $content;
    }

    protected function orderMatches()
    {
        global $mtlda;

        if (!isset($this->matches) ||
            empty($this->matches)
        ) {
            return true;
        }

        $unsorted = $this->matches;
        $documents = array();
        $queue = array();
        $keywords = array();

        foreach ($unsorted as $match) {
            if (is_a($match, 'Mtlda\\Models\\DocumentModel')) {
                array_push($documents, $match);
            } elseif (is_a($match, 'Mtlda\\Models\\QueueItemModel')) {
                array_push($queue, $match);
            } elseif (is_a($match, 'Mtlda\\Models\\KeywordModel')) {
                array_push($keywords, $match);
            } else {
                $mtlda->raiseError(__METHOD__ .'(), unknown Model found!');
                return false;
            }
        }

        if (!$this->filterDocuments($documents)) {
            $mtlda->raiseError(__CLASS__ .'::filterDocuments() returned false!');
            return false;
        }

        if (!$this->filterQueue($queue)) {
            $mtlda->raiseError(__CLASS__ .'::filterQueue() returned false!');
            return false;
        }

        if (!$this->filterKeywords($keywords)) {
            $mtlda->raiseError(__CLASS__ .'::filterKeywords() returned false!');
            return false;
        }

        $sorted = array();
        $matches = array();

        foreach ($documents as $idx => $document) {
            array_push($matches, "archive${idx}");
            $sorted["archive${idx}"] = $document;
        }

        foreach ($queue as $idx => $queueitem) {
            array_push($matches, "queue${idx}");
            $sorted["queue${idx}"] = $queueitem;
        }

        foreach ($keywords as $idx => $keyword) {
            array_push($matches, "keyword${idx}");
            $sorted["keyword${idx}"] = $keyword;
        }

        $this->matches = $sorted;
        $this->avail_matches = $matches;
        return true;
    }

    protected function filterDocuments(&$documents)
    {
        global $mtlda;

        $stack = array();

        foreach ($documents as $document) {
            if ($document->getVersion() == 1) {
                $stack[$document->document_guid] = $document;
                continue;
            }

            if (!$document->hasParent()) {
                $mtlda->raiseError(__METHOD__ .'(), document version not 0 but has no parents!');
                return false;
            }

            if (($parent = $document->getParent()) === false) {
                $mtlda->raiseError(get_class($document) .'::getParent() returned false!');
                return false;
            }

            if (in_array($parent->document_guid, array_keys($stack))) {
                continue;
            }

            $stack[$parent->document_guid] = $parent;
        }

        if (!usort($stack, function ($a, $b) {
            if ($a->document_time < $b->document_time) {
                return -1;
            } elseif ($a->document_time == $b->document_time) {
                return 0;
            } elseif ($a->document_time > $b->document_time) {
                return 1;
            }
        })) {
            $mtlda->raiseError(__METHOD__ .'(), usort() returned false!');
            return false;
        }

        $documents = $stack;
        return true;
    }

    protected function filterQueue(&$queue)
    {
        global $mtlda;

        if (!usort($queue, function ($a, $b) {
            if ($a->queue_time < $b->queue_time) {
                return -1;
            } elseif ($a->queue_time == $b->queue_time) {
                return 0;
            } elseif ($a->queue_time > $b->queue_time) {
                return 1;
            }
        })) {
            $mtlda->raiseError(__METHOD__ .'(), usort() returned false!');
            return false;
        }

        return true;
    }

    protected function filterKeywords(&$queue)
    {
        global $mtlda;

        if (!usort($queue, function ($a, $b) {
            return strcasecmp($a->keyword_name, $b->keyword_name);
        })) {
            $mtlda->raiseError(__METHOD__ .'(), usort() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4: