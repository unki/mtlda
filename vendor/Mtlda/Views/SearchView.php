<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
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
    protected static $view_class_name = 'search';

    protected $match_results;
    protected $match_types;

    public function __construct()
    {
        global $router, $tmpl;

        try {
            $search = new \Mtlda\Controllers\SearchController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load SearchController!', true, $e);
            return;
        }

        $tmpl->registerPlugin("block", "result_list", array(&$this, "listSearchResults"), false);

        if (!$router->hasQueryParams()) {
            return;
        }

        if (!$router->hasQueryParam('search')) {
            return;
        }

        if (($search_param = $router->getQueryParam('search')) === false) {
            static::raiseError(get_class($router) .'::getQueryParam() returned false!', true);
            return;
        }

        if (!$search->search($search_param, true)) {
            static::raiseError(get_class($search) .'::search() returned false!', true);
            return;
        }

        if (!$search->hasResults()) {
            return;
        }

        if (($results = $search->getResults()) === false) {
            static::raiseError(get_class($search) .'::getResults() returned false!', true);
            return;
        }

        foreach ($results as $type => $obj) {
            if ($type !== '\Mtlda\Models\QueueModel' &&
                $type !== '\Mtlda\Models\ArchiveModel' &&
                $type !== '\Mtlda\Models\KeywordsModel'
            ) {
                unset($results[$type]);
                continue;
            }
        }

        if (!$this->orderMatches($results)) {
            static::raiseError(__CLASS__ .'::orderMatches() returned false!', true);
            return;
        }

        $this->match_results = $results;
        return;
    }

    public function show()
    {
        global $tmpl;

        if (!isset($this->match_results) ||
            empty($this->match_results)
        ) {
            $tmpl->assign('no_result', true);
            return $tmpl->fetch('search.tpl');
        }

        return $tmpl->fetch('search.tpl');
    }

    public function listSearchResults($params, $content, &$smarty, &$repeat)
    {
        if (empty($this->match_results)) {
            $repeat = false;
            return $content;
        }

        $item = array_shift($this->match_results);

        $smarty->assign("item", $item);

        if (!preg_match('/(Document|QueueItem|Keyword)Model$/', get_class($item), $parts)) {
            static::raiseError(__METHOD__ .'(), unknown item found!'. get_class($item));
            return false;
        }

        $type =  strtolower($parts[1]);
        $smarty->assign('type', $type);

        if ($type == 'document') {
            $smarty->assign('callview', 'archive');
            $smarty->assign('icon', 'archive');
        } elseif ($type == 'queueitem') {
            $smarty->assign('callview', 'queue');
            $smarty->assign('icon', 'wait');
        } else {
            $smarty->assign('callview', 'keywords');
            $smarty->assign('icon', 'star');
        }

        if (($idx = $item->getIdx()) === false) {
            static::raiseError(get_class($item) .'::getIdx() returned false!');
            return false;
        }

        if (($guid = $item->getGuid()) === false) {
            static::raiseError(get_class($item) .'::getGuid() returned false!');
            return false;
        }

        $smarty->assign('item_safe_url', $idx .'-'. $guid);

        $repeat = true;
        return $content;
    }

    protected function orderMatches(&$results)
    {
        if (!isset($results) || empty($results)) {
            return true;
        }

        $unsorted = $results;
        $documents = array();
        $queue = array();
        $keywords = array();

        foreach ($unsorted as $match) {
            if (!is_a($match, 'Mtlda\\Models\\ArchiveModel') &&
                !is_a($match, 'Mtlda\\Models\\QueueModel') &&
                !is_a($match, 'Mtlda\\Models\\KeywordsModel')
            ) {
                continue;
            } elseif (is_a($match, 'Mtlda\\Models\\ArchiveModel')) {
                if (!$match->hasItems()) {
                    continue;
                }
                if (($items = $match->getItems()) === false) {
                    static::raiseError(get_class($match) .'::getItems() returned false!');
                    return false;
                }
                foreach ($items as $item) {
                    $documents[] = $item;
                }
            } elseif (is_a($match, 'Mtlda\\Models\\QueueModel')) {
                if (!$match->hasItems()) {
                    continue;
                }
                if (($items = $match->getItems()) === false) {
                    static::raiseError(get_class($match) .'::getItems() returned false!');
                    return false;
                }
                foreach ($items as $item) {
                    $queue[] = $item;
                }
            } elseif (is_a($match, 'Mtlda\\Models\\KeywordsModel')) {
                if (!$match->hasItems()) {
                    continue;
                }
                if (($items = $match->getItems()) === false) {
                    static::raiseError(get_class($match) .'::getItems() returned false!');
                    return false;
                }
                foreach ($items as $item) {
                    $keywords[] = $item;
                }
            } else {
                static::raiseError(__METHOD__ .'(), unknown Model found!'. get_class($match));
                return false;
            }
        }

        if (!$this->filterDocuments($documents)) {
            static::raiseError(__CLASS__ .'::filterDocuments() returned false!');
            return false;
        }

        if (!$this->filterQueue($queue)) {
            static::raiseError(__CLASS__ .'::filterQueue() returned false!');
            return false;
        }

        if (!$this->filterKeywords($keywords)) {
            static::raiseError(__CLASS__ .'::filterKeywords() returned false!');
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

        $results = $sorted;
        return true;
    }

    protected function filterDocuments(&$documents)
    {
        $stack = array();

        foreach ($documents as $document) {
            if ($document->getVersion() == 1) {
                $stack[$document->getGuid()] = $document;
                continue;
            }

            if (!$document->hasParent()) {
                static::raiseError(__METHOD__ .'(), document is not a original copy but no parent can be found!');
                return false;
            }

            if (($parent = $document->getParent()) === false) {
                static::raiseError(get_class($document) .'::getParent() returned false!');
                return false;
            }

            if (($p_guid = $parent->getGuid()) === false) {
                static::raiseError(get_class($parent) .'::getGuid() returned false!');
                return false;
            }

            if (in_array($p_guid, array_keys($stack))) {
                continue;
            }

            $stack[$parent->getGuid()] = $parent;
        }

        if (!usort($stack, function ($a, $b) {
            if ($a->getTime() < $b->getTime()) {
                return -1;
            } elseif ($a->getTime() == $b->getTime()) {
                return 0;
            } elseif ($a->getTime() > $b->getTime()) {
                return 1;
            }
        })) {
            static::raiseError(__METHOD__ .'(), usort() returned false!');
            return false;
        }

        $documents = $stack;
        return true;
    }

    protected function filterQueue(&$queue)
    {
        if (!usort($queue, function ($a, $b) {
            if ($a->getTime() < $b->getTime()) {
                return -1;
            } elseif ($a->getTime() == $b->getTime()) {
                return 0;
            } elseif ($a->getTime() > $b->getTime()) {
                return 1;
            }
        })) {
            static::raiseError(__METHOD__ .'(), usort() returned false!');
            return false;
        }

        return true;
    }

    protected function filterKeywords(&$queue)
    {
        if (!usort($queue, function ($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        })) {
            static::raiseError(__METHOD__ .'(), usort() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
