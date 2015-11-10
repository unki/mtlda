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
        if (!isset($fqfn) || empty($fqfn)) {
            $this->raiseError(__METHOD__ .'(), \$fqfn parameter is invalid!');
            return false;
        }

        if (!file_exists($fqfn)) {
            $this->raiseError(__METHOD__ ."(), {$fqfn} does not exist!");
            return false;
        }

        if (!is_file($fqfn)) {
            $this->raiseError(__METHOD__ ."(), {$fqfn} is not a file!");
            return false;
        }

        try {
            $this->tesseract = new \TesseractOCR($fqfn);
        } catch (\Exception $e) {
            $this->raiseError(__CLASS__ .', failed to load TesseractOCR!', true, $e);
            return false;
        }

        $this->tesseract->setLanguage('deu');
        try {
            $text = $this->tesseract->recognize();
        } catch (\Exception $e) {
            if (strstr($e->getMessage(), "does not exist! Probably Tesseract was unsuccessful")) {
                return "";
            }
            $this->raiseError(get_class($ocr) .'::recognize() raised an unknown exception!', false, $e);
            return false;
        }
        return $text;
    }

    public function scanDirectory($dir)
    {
        $text_ary = array();

        if (!isset($dir) || empty($dir)) {
            $this->raiseError(__METHOD__ .'(), \$dir parameter must be set!');
            return false;
        }

        if (!file_exists($dir)) {
            $this->raiseError(__METHOD__ ."(), directory {$dir} does not exist!");
            return false;
        }

        if (!is_dir($dir)) {
            $this->raiseError(__METHOD__ ."(), {$dir} is not a directory!");
            return false;
        }

        if (($files = scandir($dir)) === false) {
            $this->raiseError("scandir on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        if (empty($files)) {
            return $text_ary;
        }

        foreach ($files as $file) {
            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                $this->raiseError("realpath() on ". $dir .'/'. $file ." returned false!");
                return false;
            }

            if (($text = $this->scanFile($fqfn)) === false) {
                $this->raiseError(__CLASS__ ."::scanFile() failed on {$fqfn}!");
                return false;
            }

            if (!isset($text) || empty($text) || !is_string($text)) {
                continue;
            }

            array_push($text_ary, $text);
        }

        return $text_ary;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
