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

class OcrController extends \Thallium\Controllers\DefaultController
{
    protected $tesseract;

    public function __construct()
    {
        require_once APP_BASE .'/vendor/TesseractOCR/TesseractOCR.php';
        return true;
    }

    public function scanFile($fqfn)
    {
        global $config;

        if (!isset($fqfn) || empty($fqfn)) {
            static::raiseError(__METHOD__ .'(), \$fqfn parameter is invalid!');
            return false;
        }

        if (!file_exists($fqfn)) {
            static::raiseError(__METHOD__ ."(), {$fqfn} does not exist!");
            return false;
        }

        if (!is_file($fqfn)) {
            static::raiseError(__METHOD__ ."(), {$fqfn} is not a file!");
            return false;
        }

        try {
            $this->tesseract = new \TesseractOCR($fqfn);
        } catch (\Exception $e) {
            static::raiseError(__CLASS__ .', failed to load TesseractOCR!', true, $e);
            return false;
        }

        if (($language = $config->getDefaultOcrLanguage()) === false) {
            static::raiseError(get_class($config) .'::getDefaultOcrLanguage() returned false!');
            return false;
        }

        if (!$this->isSupportedLanguage($language)) {
            static::raiseError(__CLASS__ .'::isSupportedLanguage() returned false!');
            return false;
        }

        $this->tesseract->lang($language);

        try {
            $text = $this->tesseract->run();
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), "does not exist! Probably Tesseract was unsuccessful")) {
                return "";
            }
            static::raiseError(get_class($ocr) .'::run() raised an unknown exception!', false, $e);
            return false;
        }
        return $text;
    }

    public function scanDirectory($dir)
    {
        $text_ary = array();

        if (!isset($dir) || empty($dir)) {
            static::raiseError(__METHOD__ .'(), \$dir parameter must be set!');
            return false;
        }

        if (!file_exists($dir)) {
            static::raiseError(__METHOD__ ."(), directory {$dir} does not exist!");
            return false;
        }

        if (!is_dir($dir)) {
            static::raiseError(__METHOD__ ."(), {$dir} is not a directory!");
            return false;
        }

        if (($files = scandir($dir)) === false) {
            static::raiseError("scandir on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        if (empty($files)) {
            return $text_ary;
        }

        foreach ($files as $file) {
            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                static::raiseError("realpath() on ". $dir .'/'. $file ." returned false!");
                return false;
            }

            if (($text = $this->scanFile($fqfn)) === false) {
                static::raiseError(__CLASS__ ."::scanFile() failed on {$fqfn}!");
                return false;
            }

            if (!isset($text) || empty($text) || !is_string($text)) {
                continue;
            }

            array_push($text_ary, $text);
        }

        return $text_ary;
    }

    protected function isSupportedLanguage($language)
    {
        if (!isset($language) || empty($language)) {
            static::raiseError(__METHOD__ .'(), $language parameter is invalid!');
            return false;
        }

        $languages = array(
            'afr', 'ara', 'aze', 'bel', 'ben', 'bul', 'cat', 'ces', 'chi-sim',
            'chi-tra', 'chr', 'dan', 'deu', 'deu-frak', 'ell', 'eng', 'enm',
            'epo', 'equ', 'est', 'eus', 'fin', 'fra', 'frk', 'frm', 'glg',
            'grc', 'heb', 'hin', 'hrv', 'hun', 'ind', 'isl', 'ita', 'ita-old',
            'jpn', 'kan', 'kor', 'lav', 'lit', 'mal', 'mkd', 'mlt', 'msa', 'nld',
            'nor', 'osd', 'pol', 'por', 'ron', 'rus', 'slk', 'slk-frak', 'slv',
            'spa', 'spa-old', 'sqi', 'srp', 'swa', 'swe', 'tam', 'tel', 'tgl',
            'tha', 'tur', 'ukr', 'vie',
        );

        if (!in_array($language, $languages)) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
