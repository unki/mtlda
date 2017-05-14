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

namespace Mtlda\Controllers;

class SuggestionsController extends DefaultController
{
    protected $keywords;
    protected $archive;

    /**
     * initialize controller
     *
     * @params none
     * @return void
     */
    public function __construct()
    {
        try {
            $this->keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load KeywordsModel!', false, $e);
            return false;
        }

        try {
            $this->archive = new \Mtlda\Models\ArchiveModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ArchiveModel!', false, $e);
            return false;
        }

        parent::__construct();
        return;
    }

    /*
     * getDateSuggestions()
     *
     * return an array of dates that might be suiteable for the provided item.
     *
     * @param Mtlda\Models\QueueItemModel|Mtlda\Models\DocumentModel $item
     * @return bool|array
     */
    public function getDateSuggestions($item)
    {
        if (!isset($item) ||
            empty($item) ||
            (!is_a($item, 'Mtlda\Models\QueueItemModel') &&
            !is_a($item, 'Mtlda\Models\DocumentModel'))
        ) {
            static::raiseError(__METHOD__ .'(), invalid item provided as parameter!');
            return false;
        }

        $sources = array();

        if ($item->hasTitle()) {
            if (($title = $item->getTitle()) === false) {
                static::raiseError(get_class($item) .'::getTitle() returned false!');
                return false;
            }

            array_push($sources, $title);
        }

        if ($item->hasFileName()) {
            if (($filename = $item->getFileName()) === false) {
                static::raiseError(get_class($item) .'::getFileName() returned false');
                return false;
            }

            array_push($sources, $filename);
        }

        if ($item->hasIndices()) {
            if (($indices = $item->getIndices(true)) === false) {
                static::raiseError(get_class($item) .'::getIndices() returned false!');
                return false;
            }

            if (!isset($indices) || empty($indices) || !is_array($indices)) {
                static::raiseError(get_class($item) .'::getIndices() returned invalid data!');
                return false;
            }

            foreach ($indices as $index) {
                if (($text = $index->getDocumentText()) === false) {
                    static::raiseError(get_class($index) .'::getDocumentText() returned false!');
                    return false;
                }

                array_push($sources, $text);
            }
        }

        if (is_a($item, 'Mtlda\Models\DocumentModel') && $item->hasProperties()) {
            if (($properties = $item->getProperties()) === false) {
                static::raiseError(get_class($item) .'::getProperties() returned false!');
                return false;
            }

            if (!isset($properties) || empty($properties) || !is_array($properties)) {
                static::raiseError(get_class($item) .'::getProperties() returned invalid data!');
                return false;
            }

            foreach ($properties as $property) {
                if (($value = $property->getDocumentValue()) === false) {
                    static::raiseError(get_class($property) .'::getDocumentValue() returned false!');
                    return false;
                }

                array_push($sources, $value);
            }
        }

        if (($sources = array_unique($sources)) === false) {
            static::raiseError(__METHOD__ .'(), failed to filter sources!');
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
                    static::raiseError(__METHOD__ .'(), an error in preg_match_all() occured! '. preg_last_error());
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
        $dateSuggestions = array_filter($suggestions, function ($date) {
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

        if (($dateSuggestions = array_unique($dateSuggestions)) === false) {
            static::raiseError(__METHOD__ .'(), array_unique() returned false!');
            return false;
        }

        if (!sort($dateSuggestions)) {
            static::raiseError(__METHOD__ .'(), sort() returned false!');
            return false;
        }

        return $dateSuggestions;
    }

    /*
     * getKeywordSuggestions()
     *
     * return an array of keyword that might be suiteable for the provided item.
     *
     * @param Mtlda\Models\QueueItemModel|Mtlda\Models\DocumentModel $item
     * @return bool|array
     */
    public function getKeywordSuggestions($item)
    {
        $archive_item_keywords = array();

        if (!isset($item) ||
            empty($item) ||
            (!is_a($item, 'Mtlda\Models\QueueItemModel') &&
            !is_a($item, 'Mtlda\Models\DocumentModel'))
        ) {
            static::raiseError(__METHOD__ .'(), invalid item provided as parameter!');
            return false;
        }

        if (!$this->keywords->hasItems()) {
            return array();
        }

        if ($item->hasKeywords()) {
            if (($assigned_keywords = $item->getKeywords()) === false) {
                static::raiseError(get_class($item) .'::getKeywords() returned false!');
                return false;
            }

            foreach ($assigned_keywords as $idx) {
                try {
                    $keyword = new \Mtlda\Models\KeywordModel(array(
                        'idx' => $idx
                    ));
                } catch (\Exception $e) {
                    static::raiseError(__METHOD__ .'(), failed to load KeywordModel!', false, $e);
                    return false;
                }

                if (($name = $keyword->getName()) === false) {
                    static::raiseError(get_class($keyword) .'::getName() returned false!');
                    return false;
                }

                array_push($archive_item_keywords, $name);
            }
        }

        $sources = array();

        if ($item->hasTitle()) {
            if (($title = $item->getTitle()) === false) {
                static::raiseError(get_class($item) .'::getTitle() returned false!');
                return false;
            }

            array_push($sources, $title);
        }

        if ($item->hasFileName()) {
            if (($filename = $item->getFileNameBase()) === false) {
                static::raiseError(get_class($item) .'::getFileName() returned false');
                return false;
            }

            array_push($sources, $filename);
        }

        if ($item->hasIndices()) {
            if (($indices = $item->getIndices(true)) === false) {
                static::raiseError(get_class($item) .'::getIndices() returned false!');
                return false;
            }

            if (!isset($indices) || empty($indices) || !is_array($indices)) {
                static::raiseError(get_class($item) .'::getIndices() returned invalid data!');
                return false;
            }

            foreach ($indices as $index) {
                if (($text = $index->getDocumentText()) === false) {
                    static::raiseError(get_class($index) .'::getDocumentText() returned false!');
                    return false;
                }

                array_push($sources, $text);
            }
        }

        if (is_a($item, 'Mtlda\Models\DocumentModel') && $item->hasProperties()) {
            if (($properties = $item->getProperties()) === false) {
                static::raiseError(get_class($item) .'::getProperties() returned false!');
                return false;
            }

            if (!isset($properties) || empty($properties) || !is_array($properties)) {
                static::raiseError(get_class($item) .'::getProperties() returned invalid data!');
                return false;
            }

            foreach ($properties as $property) {
                if (($value = $property->getDocumentValue()) === false) {
                    static::raiseError(get_class($property) .'::getDocumentValue() returned false!');
                    return false;
                }

                array_push($sources, $value);
            }
        }

        if (($sources = array_unique($sources)) === false) {
            static::raiseError(__METHOD__ .'(), failed to filter sources!');
            return false;
        }

        $existing_keywords = array();

        foreach ($this->keywords->getItems() as $keyword) {
            if (($name = $keyword->getName()) === false) {
                static::raiseError(get_class($keyword) .'::getName() returned false!');
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
            static::raiseError(__METHOD__ .'(), array_count_values() returned false!');
            return false;
        }

        if (!arsort($words, SORT_NUMERIC)) {
            static::raiseError(__METHOD__ .'(), arsort() returned false!');
            return false;
        }

        if (($words = array_slice($words, 0, 10)) === false) {
            static::raiseError(__METHOD__ .'(), array_slice() returned false!');
            return false;
        }

        $keywordSuggestions = array();

        foreach ($words as $word => $occur) {
            if (in_array($word, $archive_item_keywords)) {
                continue;
            }

            $keywordSuggestions[$word] = $occur;
        }

        if (!$this->archive->hasItems()) {
            return array('match' => $keywordSuggestions);
        }

        $items = array();
        $filenames = array();
        $titles = array();
        $sources = array();

        foreach ($this->archive->getItems() as $document) {
            if (($idx = $document->getIdx()) === false) {
                static::raiseError(get_class($document) .'::getIdx() returned false!');
                return false;
            }
            $items[$idx] = $document;
            if (($filename = $document->getFileName()) === false) {
                static::raiseError(get_class($document) .'::getFileName() returned false!');
                return false;
            }
            if ($document->hasTitle() && ($title = $document->getTitle()) === false) {
                static::raiseError(get_class($document) .'::getTitle() returned false!');
                return false;
            }
            $filenames[$idx] = $filename;
            $titles[$idx] = $title;
        }

        if ($item->hasTitle()) {
            $archive_item_title = $item->getTitle();
        }
        $archive_item_filename = $item->getFileName();

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
                static::raiseError(get_class($document) .'::getKeywords() returned false!');
                return false;
            }
            if (!isset($keywords) || empty($keywords)) {
                continue;
            }
            foreach ($keywords as $idx) {
                try {
                    $keyword = new \Mtlda\Models\KeywordModel(array(
                        'idx' => $idx
                    ));
                } catch (\Exception $e) {
                    static::raiseError(__METHOD__ .'(), failed to load KeywordModel!', false, $e);
                    return false;
                }
                if (($name = $keyword->getName()) === false) {
                    static::raiseErrror(get_class($keyword) .'::getName() returned false!');
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
                    static::raiseError(get_class($document) .'::getKeywords() returned false!');
                    return false;
                }
                if (!isset($keywords) || empty($keywords)) {
                    continue;
                }
                foreach ($keywords as $idx) {
                    try {
                        $keyword = new \Mtlda\Models\KeywordModel(array(
                            'idx' => $idx
                        ));
                    } catch (\Exception $e) {
                        static::raiseError(__METHOD__ .'(), failed to load KeywordModel!', false, $e);
                        return false;
                    }
                    if (($name = $keyword->getName()) === false) {
                        static::raiseErrror(get_class($keyword) .'::getName() returned false!');
                        return false;
                    }
                    array_push($sources, $name);
                }
            }
        }

        if (($words = array_count_values($sources)) === false) {
            static::raiseError(__METHOD__ .'(), array_count_values() returned false!');
            return false;
        }
        if (!arsort($words, SORT_NUMERIC)) {
            static::raiseError(__METHOD__ .'(), arsort() returned false!');
            return false;
        }

        if (($words = array_slice($words, 0, 10)) === false) {
            static::raiseError(__METHOD__ .'(), array_slice() returned false!');
            return false;
        }

        $keywordSuggestionsSimilar[$word] = array();

        foreach ($words as $word => $occur) {
            if (in_array($word, $archive_item_keywords)) {
                continue;
            }
            $keywordSuggestionsSimilar[$word] = $occur;
        }

        return array(
            'match' => $keywordSuggestions,
            'similar' => $keywordSuggestionsSimilar,
        );
    }

    /**
     * requireArrayKeys()
     *
     * returns true, if all the provided $needles can be found as keys in the array $haystack.
     *
     * @param string|array $haystack
     * @param string|array $needles
     * @return bool
     */
    protected function requireArrayKeys($haystack, $needles)
    {
        if (!isset($haystack) || empty($haystack) || (!is_string($haystack) && !is_array($haystack))) {
            static::raiseError(__METHOD__ .'(), $haystack parameter is invalid!');
            return false;
        }

        if (!isset($needles) || empty($needles) || (!is_string($needles) && !is_array($needles))) {
            static::raiseError(__METHOD__ .'(), $needles parameter is invalid!');
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
