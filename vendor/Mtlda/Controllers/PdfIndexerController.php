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

class PdfIndexerController extends DefaultController
{
    protected $parser;
    protected $pdf;
    protected $ocr;
    protected $indexing_cfg;

    public function __construct()
    {
        global $config;

        if (!($this->indexing_cfg = $config->getPdfIndexingConfiguration())) {
            static::raiseError(get_class($config) .'::getPdfIndexingConfiguration() returned false!', true);
            return false;
        }

        if ($config->isPlainTextIndexingEnabled() &&
            !$this->loadPdfParser()
        ) {
            static::raiseError(__CLASS__ .'::loadPdfParser() returned false!', true);
            return false;
        }

        if ($config->isOcrIndexingEnabled() &&
            !$this->loadOcrParser()
        ) {
            static::raiseError(__CLASS__ .'::loadOcrParser() returned false!', true);
            return false;
        }

        return true;
    }

    protected function loadPdfParser()
    {
        try {
            $this->parser = new \Smalot\PdfParser\Parser();
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PdfParser!', false, $e);
            return false;
        }

        return true;
    }

    protected function loadOcrParser()
    {
        try {
            $this->ocr = new \Mtlda\Controllers\OcrController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load OcrController!', false, $e);
            return false;
        }

        return true;
    }

    public function scan(&$document)
    {
        global $config;

        if (!isset($document) || empty($document) ||
            (!is_a($document, 'Mtlda\Models\DocumentModel') &&
            !is_a($document, 'Mtlda\Models\QueueItemModel'))
        ) {
            static::raiseError(__METHOD__ .'(), provided model is not supported!');
            return false;
        }

        if (!($idx = $document->getIdx())) {
            static::raiseError(get_class($document) .'::getIdx() returned false!');
            return false;
        }

        if (!($guid = $document->getGuid())) {
            static::raiseError(get_class($document) .'::getGuid() returned false!');
            return false;
        }

        if (!($hash = $document->getFileHash())) {
            static::raiseError(get_class($document) .'::getFileHash() returned false!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Retrieving document from archive.', '10%');

        if (!$fqpn = $document->getFilePath()) {
            static::raiseError(get_class($document) .'::getFilePath() returned false!');
            return false;
        }

        if (!file_exists($fqpn)) {
            static::raiseError("{$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            static::raiseError("{$fqpn} is not readable!");
            return false;
        }

        //
        // cleanup existing indices & properties
        //
        try {
            $indices = new \Mtlda\Models\DocumentIndicesModel(array(
                'file_hash' => $hash
            ));
        } catch (\Exception $e) {
            static::raiseError(__CLASS__ .', failed to load DocumentIndicesModel!');
            return false;
        }

        if (!$indices->delete()) {
            static::raiseError(get_class($indices) .'::delete() returned false!');
            return false;
        }

        if (is_a($document, 'Mtlda\Models\DocumentModel')) {
            try {
                $properties = new \Mtlda\Models\DocumentPropertiesModel(array(
                    'idx' => $idx,
                    'guid' => $guid
                ));
            } catch (\Exception $e) {
                static::raiseError(__CLASS__ .', failed to load DocumentProperties!');
                return false;
            }

            if (!$properties->delete()) {
                static::raiseError(get_class($properties) .'::delete() returned false!');
                return false;
            }
        }

        $this->sendMessage('scan-reply', 'Parsing document.', '30%');

        if (($config->isPlainTextIndexingEnabled() ||
            $config->isOcrIndexingEnabled()) &&
            ($info = $this->parsePdf($fqpn)) === false
        ) {
            static::raiseError(__CLASS__ .'::parsePdf() returned false!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Extracting text parts.', '50%');

        if ($config->isPlainTextIndexingEnabled() &&
            ($info = $this->extractPdfText()) === false
        ) {
            static::raiseError(__CLASS__ .'::extractPdfText() returned false!');
            return false;
        }

        if (isset($info['text']) && !empty($info['text'])) {
            if (!$this->saveDocumentIndex($document, $info['text'])) {
                static::raiseError(__METHOD__ .'(), saveDocumentIndex() returned false!');
                return false;
            }
        }

        if (is_a($document, 'Mtlda\Models\DocumentModel') &&
            (isset($info['details']) && !empty($info['details']))
        ) {
            foreach ($info['details'] as $property => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                if (empty($property) || empty($value)) {
                    continue;
                }

                if (!$this->saveDocumentProperty($document, $property, $value)) {
                    static::raiseError(__METHOD__ .'(), saveDocumentProperty() returned false!');
                    return false;
                }
            }
        }

        $this->sendMessage('scan-reply', 'Starting OCR recogination.', '50%');

        if ($config->isOcrIndexingEnabled() &&
            ($text_ary = $this->runOcr()) === false
        ) {
            static::raiseError(__CLASS__ .'::runOcr() returned false!');
            return false;
        }

        if (!isset($text_ary) || empty($text_ary) || !is_array($text_ary)) {
            return true;
        }

        foreach ($text_ary as $text) {
            if (!$this->saveDocumentIndex($document, $text)) {
                static::raiseError(__METHOD__ .'(), saveDocumentIndex() returned false!');
                return false;
            }
        }

        return true;
    }

    protected function parsePdf($fqpn)
    {
        if (!isset($fqpn) || empty($fqpn) || !is_string($fqpn)) {
            static::raiseError(__METHOD__ .'(), \$fqpn parameter is invalid!');
            return false;
        }

        try {
            $this->pdf = $this->parser->parseFile($fqpn);
        } catch (\Exception $e) {
            static::raiseError(get_class($this->parser) .'::parseFile() returned false!', false, $e);
            return false;
        }

        return true;
    }

    protected function extractPdfText()
    {
        if (!isset($this->pdf) || empty($this->pdf) || !is_object($this->pdf)) {
            static::raiseError(__METHOD__ .'(), document has not been parsed yet!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Extracting text parts from document.', '60%');

        try {
            $text = $this->pdf->getText();
        } catch (\Exception $e) {
            static::raiseError(get_class($this->pdf) .'::getText() returned false!', false, $e);
            return false;
        }

        try {
            $details  = $this->pdf->getDetails();
        } catch (\Exception $e) {
            static::raiseError(get_class($this->pdf) .'::getDetails() returned false!', false, $e);
            return false;
        }

        $info = array();
        $info['text'] = $text;
        $info['details'] = $details;
        return $info;
    }

    protected function runOcr()
    {
        try {
            $pages  = $this->pdf->getPages();
        } catch (\Exception $e) {
            static::raiseError(get_class($this->pdf) .'::getPages() returned false!', false, $e);
            return false;
        }

        if (empty($pages)) {
            return true;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load StorageController!', false, $e);
            return false;
        }

        if (!($tempDir = $storage->createTempDir('ocr_'))) {
            static::raiseError(get_class($storage) .'::createTempDir() returned false!');
            return false;
        }

        if (!file_exists($tempDir) || !is_dir($tempDir)) {
            static::raiseError(
                get_class($storage) .'::createTempDir() has not returned a valid directory!'
            );
            return false;
        }

        foreach ($pages as $id => $page) {
            try {
                $objects = $page->getXObjects();
            } catch (\Exception $e) {
                $this->unlinkDirectory($tempDir);
                static::raiseError(get_class($page) .'::getXObjects() returned false!', false, $e);
                return false;
            }

            foreach ($objects as $id => $object) {
                if (!is_a($object, 'Smalot\PdfParser\XObject\Image')) {
                    continue;
                }

                try {
                    $content = $object->getContent();
                } catch (\Exception $e) {
                    $this->unlinkDirectory($tempDir);
                    static::raiseError(get_class($object) .'::getContent() returned false!', false, $e);
                    return false;
                }

                if (!($tempFile = tempnam($tempDir, 'pdfcontent_'))) {
                    $this->unlinkDirectory($tempDir);
                    static::raiseError(__METHOD__ .'(), tempnam() returned false!');
                    return false;
                }

                if (!(file_put_contents($tempFile, $content))) {
                    $this->unlinkDirectory($tempDir);
                    static::raiseError(__METHOD__ .'(), file_put_contents() returned false!');
                    return false;
                }
            }
        }

        if (($text = $this->ocr->scanDirectory($tempDir)) === false) {
            static::raiseError(get_class($this->ocr) .'::scanDirectory() returned false!');
            return false;
        }

        if (!$this->unlinkDirectory($tempDir)) {
            static::raiseError(__CLASS__ .'::unlinkDirectory() returned false!');
            return false;
        }

        if (!isset($text) || empty($text) || !is_array($text)) {
            return array();
        }

        return $text;
    }

    protected function saveDocumentProperty($document, $property, $value)
    {
        if (!isset($document) ||
            empty($document) ||
            !is_a($document, 'Mtlda\Models\DocumentModel')
        ) {
            static::raiseError(__METHOD__ .'(), \$document parameter is invalid!');
            return false;
        }

        if (!isset($property) ||
            empty($property) ||
            !is_string($property)
        ) {
            static::raiseError(__METHOD__ .'(), \$property parameter is invalid!');
            return false;
        }

        if (!isset($value) ||
            empty($value) ||
            (!is_string($value) && !is_numeric($value))
        ) {
            static::raiseError(__METHOD__ .'(), \$value parameter is invalid!'. $value);
            return false;
        }

        if (($hash = $document->getFileHash()) === false) {
            static::raiseError(get_class($document).'::getFileHash() returned false!');
            return false;
        }

        try {
            $pmodel = new \Mtlda\Models\DocumentPropertyModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load DocumentPropertyModel!', false, $e);
            return false;
        }

        if (!$pmodel->setFileHash($hash)) {
            static::raiseError(get_class($pmodel) .'::setFileHash() returned false!');
            return false;
        }

        if (!$pmodel->setDocumentProperty($property)) {
            static::raiseError(get_class($pmodel) .'::setDocumentProperty() returned false!');
            return false;
        }

        if (!$pmodel->setDocumentValue($value)) {
            static::raiseError(get_class($pmodel) .'::setDocumentValue() returned false!');
            return false;
        }

        if (!$pmodel->save()) {
            static::raiseError(get_class($pmodel) .'::save() returned false!');
            return false;
        }

        return true;
    }

    protected function saveDocumentIndex($document, $text)
    {
        if (!isset($document) || empty($document) ||
            (!is_a($document, 'Mtlda\Models\DocumentModel') &&
            !is_a($document, 'Mtlda\Models\QueueItemModel'))
        ) {
            static::raiseError(__METHOD__ .'(), \$document parameter is invalid!');
            return false;
        }

        if (!isset($text) || empty($text) ||
            (!is_string($text) && !is_numeric($text))
        ) {
            static::raiseError(__METHOD__ .'(), \$property parameter is invalid!');
            return false;
        }

        if (($hash = $document->getFileHash()) === false) {
            static::raiseError(get_class($document).'::getFileHash() returned false!');
            return false;
        }

        try {
            $index = new \Mtlda\Models\DocumentIndexModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load DocumentIndexModel!', false, $e);
            return false;
        }

        if (!$index->setFileHash($hash)) {
            static::raiseError(get_class($index) .'::setFileHash() returned false!');
            return false;
        }

        if (!$index->setDocumentText($text)) {
            static::raiseError(get_class($index) .'::setDocumentText() returned false!');
            return false;
        }

        if (!$index->save()) {
            static::raiseError(get_class($index) .'::save() returned false!');
            return false;
        }

        return true;
    }

    protected function unlinkDirectory($dir)
    {
        if (!isset($dir) || empty($dir) || !is_string($dir)) {
            static::raiseError(__METHOD__ .'(), $dir parameter is invalid!');
            return false;
        }

        global $mtlda;

        if (($files = scandir($dir)) === false) {
            static::raiseError(__METHOD__ ."(), scandir({$dir}) returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {
            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                static::raiseError(__METHOD__ ."(), realpath(${dir}/${file}) returned false!");
                return false;
            }

            if (!$mtlda->isBelowDirectory(dirname($fqfn), self::CACHE_DIRECTORY)) {
                static::raiseError("will only handle requested within ". $this::CACHE_DIRECTORY ."!");
                return false;
            }

            if (is_dir($fqfn)) {
                if (!$this->unlinkDirectory($fqfn)) {
                    return false;
                }
            } else {
                if (!unlink($fqfn)) {
                    static::raiseError("unlink() on {$fqfn} returned false!");
                    return false;
                }
            }
        }

        if (!rmdir($dir)) {
            static::raiseError("rmdir() on {$dir} returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
