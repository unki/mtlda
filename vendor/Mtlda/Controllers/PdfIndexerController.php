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

class PdfIndexerController extends DefaultController
{
    private $parser;
    private $pdf;
    private $indexing_cfg;

    public function __construct()
    {
        global $config;

        if (!($this->indexing_cfg = $config->getPdfIndexingConfiguration())) {
            $this->raiseError(get_class($config) .'::getPdfIndexingConfiguration() returned false!', true);
            return false;
        }

        if ($this->isPlainTextIndexingEnabled() &&
            !$this->loadPdfParser()
        ) {
            $this->raiseError(__CLASS__ .'::loadPdfParser() returned false!', true);
            return false;
        }

        if ($this->isOcrIndexingEnabled() &&
            !$this->loadOcrParser()
        ) {
            $this->raiseError(__CLASS__ .'::loadOcrParser() returned false!', true);
            return false;
        }

        return true;
    }

    private function loadPdfParser()
    {
        try {
            $this->parser = new \Smalot\PdfParser\Parser();
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load PdfParser!');
            return false;
        }

        return true;
    }

    private function loadOcrParser()
    {
        try {
            $this->ocr = new OcrController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load OcrController!');
            return false;
        }

        return true;
    }

    public function scan(&$document)
    {
        if (!is_a($document, 'Mtlda\Models\DocumentModel')) {
            $this->raiseError(__METHOD__ .' only supports DocumentModels!');
            return false;
        }

        if (!($idx = $document->getId())) {
            $this->raiseError(get_class($document) .'::getId() returned false!');
            return false;
        }

        if (!($guid = $document->getGuid())) {
            $this->raiseError(get_class($document) .'::getGuid() returned false!');
            return false;
        }

        if (!($hash = $document->getFileHash())) {
            $this->raiseError(get_class($document) .'::getFileHash() returned false!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Retrieving document from archive.', '10%');

        if (!$fqpn = $document->getFilePath()) {
            $this->raiseError(get_class($document) .'::getFilePath() returned false!');
            return false;
        }

        if (!file_exists($fqpn)) {
            $this->raiseError("{$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $this->raiseError("{$fqpn} is not readable!");
            return false;
        }

        //
        // cleanup existing indices & properties
        //
        try {
            $indices = new \Mtlda\Models\DocumentIndicesModel($hash);
        } catch (\Exception $e) {
            $this->raiseError(__CLASS__ .', failed to load DocumentIndicesModel!');
            return false;
        }

        if (!$indices->delete()) {
            $this->raiseError(get_class($indices) .'::delete() returned false!');
            return false;
        }

        try {
            $properties = new \Mtlda\Models\DocumentPropertiesModel($idx, $guid);
        } catch (\Exception $e) {
            $this->raiseError(__CLASS__ .', failed to load DocumentProperties!');
            return false;
        }

        if (!$properties->delete()) {
            $this->raiseError(get_class($properties) .'::delete() returned false!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Parsing document.', '30%');

        if (($this->isPlainTextIndexingEnabled() ||
            $this->isOcrIndexingEnabled()) &&
            ($info = $this->parsePdf($fqpn)) === false
        ) {
            $this->raiseError(__CLASS__ .'::parsePdf() returned false!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Extracting text parts.', '50%');

        if ($this->isPlainTextIndexingEnabled() &&
            ($info = $this->extractPdfText()) === false
        ) {
            $this->raiseError(__CLASS__ .'::extractPdfText() returned false!');
            return false;
        }

        if (isset($info['text']) && !empty($info['text'])) {
            if (!$this->saveDocumentIndex($document, $info['text'])) {
                $this->raiseError(__METHOD__ .'(), saveDocumentIndex() returned false!');
                return false;
            }
        }

        if (isset($info['details']) && !empty($info['details'])) {
            foreach ($info['details'] as $property => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                if (empty($property) || empty($value)) {
                    continue;
                }

                if (!$this->saveDocumentProperty($document, $property, $value)) {
                    $this->raiseError(__METHOD__ .'(), saveDocumentProperty() returned false!');
                    return false;
                }
            }
        }

        $this->sendMessage('scan-reply', 'Starting OCR recogination.', '50%');

        if ($this->isOcrIndexingEnabled() &&
            ($text_ary = $this->runOcr()) === false
        ) {
            $this->raiseError(__CLASS__ .'::runOcr() returned false!');
            return false;
        }

        if (!isset($text_ary) || empty($text_ary) || !is_array($text_ary)) {
            return true;
        }

        foreach ($text_ary as $text) {
            if (!$this->saveDocumentIndex($document, $text)) {
                $this->raiseError(__METHOD__ .'(), saveDocumentIndex() returned false!');
                return false;
            }
        }

        return true;
    }

    private function parsePdf($fqpn)
    {
        if (!isset($fqpn) || empty($fqpn) || !is_string($fqpn)) {
            $this->raiseError(__METHOD__ .'(), \$fqpn parameter is invalid!');
            return false;
        }

        try {
            $this->pdf = $this->parser->parseFile($fqpn);
        } catch (\Exception $e) {
            $this->raiseError(get_class($this->parser) .'::parseFile() returned false! '. $e->getMessage());
            return false;
        }

        return true;
    }

    private function extractPdfText()
    {
        if (!isset($this->pdf) || empty($this->pdf) || !is_object($this->pdf)) {
            $this->raiseError(__METHOD__ .'(), document has not been parsed yet!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Extracting text parts from document.', '60%');

        try {
            $text = $this->pdf->getText();
        } catch (\Exception $e) {
            $this->raiseError(get_class($this->pdf) .'::getText() returned false!');
            return false;
        }

        try {
            $details  = $this->pdf->getDetails();
        } catch (\Exception $e) {
            $this->raiseError(get_class($this->pdf) .'::getDetails() returned false!');
            return false;
        }

        $info = array();
        $info['text'] = $text;
        $info['details'] = $details;
        return $info;
    }

    private function isPlainTextIndexingEnabled()
    {
        global $config;

        if (!isset($this->indexing_cfg) || empty($this->indexing_cfg)) {
            $this->raiseError(__METHOD__ .'(), invalid configuration found!');
            return false;
        }

        if (isset($this->indexing_cfg['extract_text_from_document']) &&
            !empty($this->indexing_cfg['extract_text_from_document']) &&
            $config->isEnabled($this->indexing_cfg['extract_text_from_document'])
        ) {
            return true;
        }

        return false;
    }

    private function isOcrIndexingEnabled()
    {
        global $config;

        if (!isset($this->indexing_cfg) || empty($this->indexing_cfg)) {
            $this->raiseError(__METHOD__ .'(), invalid configuration found!');
            return false;
        }

        if (isset($this->indexing_cfg['use_ocr_for_embedded_images']) &&
            !empty($this->indexing_cfg['use_ocr_for_embedded_images']) &&
            $config->isEnabled($this->indexing_cfg['use_ocr_for_embedded_images'])
        ) {
            return true;
        }

        return false;
    }

    private function runOcr()
    {
        try {
            $pages  = $this->pdf->getPages();
        } catch (\Exception $e) {
            $this->raiseError(get_class($this->pdf) .'::getPages() returned false!');
            return false;
        }

        if (empty($pages)) {
            return true;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController();
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load StorageController!', false, $e);
            return false;
        }

        if (!($tempDir = $storage->createTempDir('ocr_'))) {
            $this->raiseError(get_class($storage) .'::createTempDir() returned false!');
            return false;
        }

        if (!file_exists($tempDir) || !is_dir($tempDir)) {
            $this->raiseError(
                get_class($storage) .'::createTempDir() has not returned a valid directory!'
            );
            return false;
        }

        foreach ($pages as $id => $page) {
            try {
                $objects = $page->getXObjects();
            } catch (\Exception $e) {
                $this->unlinkDirectory($tempDir);
                $this->raiseError(get_class($page) .'::getXObjects() returned false!');
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
                    $this->raiseError(get_class($object) .'::getContent() returned false!');
                    return false;
                }

                if (!($tempFile = tempnam($tempDir, 'pdfcontent_'))) {
                    $this->unlinkDirectory($tempDir);
                    $this->raiseError(__METHOD__ .'(), tempnam() returned false!');
                    return false;
                }

                if (!(file_put_contents($tempFile, $content))) {
                    $this->unlinkDirectory($tempDir);
                    $this->raiseError(__METHOD__ .'(), file_put_contents() returned false!');
                    return false;
                }
            }
        }

        if (($text = $this->ocr->scanDirectory($tempDir)) === false) {
            $this->raiseError(get_class($this->ocr) .'::scanDirectory() returned false!');
            return false;
        }

        if (!$this->unlinkDirectory($tempDir)) {
            $this->raiseError(__CLASS__ .'::unlinkDirectory() returned false!');
            return false;
        }

        if (!isset($text) || empty($text) || !is_array($text)) {
            return array();
        }

        return $text;
    }

    private function saveDocumentProperty($document, $property, $value)
    {
        if (!isset($document) ||
            empty($document) ||
            !is_a($document, 'Mtlda\Models\DocumentModel')
        ) {
            $this->raiseError(__METHOD__ .'(), \$document parameter is invalid!');
            return false;
        }

        if (!isset($property) ||
            empty($property) ||
            !is_string($property)
        ) {
            $this->raiseError(__METHOD__ .'(), \$property parameter is invalid!');
            return false;
        }

        if (!isset($value) ||
            empty($value) ||
            (!is_string($value) && !is_numeric($value))
        ) {
            $this->raiseError(__METHOD__ .'(), \$value parameter is invalid!'. $value);
            return false;
        }

        try {
            $pmodel = new \Mtlda\Models\DocumentPropertyModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load DocumentPropertyModel!');
            return false;
        }

        if (!$pmodel->setDocumentIdx($document->getId())) {
            $this->raiseError(get_class($pmodel) .'::setDocumentIdx() returned false!');
            return false;
        }

        if (!$pmodel->setDocumentGuid($document->getGuid())) {
            $this->raiseError(get_class($pmodel) .'::setDocumentGuid() returned false!');
            return false;
        }

        if (!$pmodel->setDocumentProperty($property)) {
            $this->raiseError(get_class($pmodel) .'::setDocumentProperty() returned false!');
            return false;
        }

        if (!$pmodel->setDocumentValue($value)) {
            $this->raiseError(get_class($pmodel) .'::setDocumentValue() returned false!');
            return false;
        }

        if (!$pmodel->save()) {
            $this->raiseError(get_class($pmodel) .'::save() returned false!');
            return false;
        }

        return true;
    }

    private function saveDocumentIndex($document, $text)
    {
        if (!isset($document) ||
            empty($document) ||
            !is_a($document, 'Mtlda\Models\DocumentModel')
        ) {
            $this->raiseError(__METHOD__ .'(), \$document parameter is invalid!');
            return false;
        }

        if (!isset($text) ||
            empty($text) ||
            (!is_string($text) && !is_numeric($text))
        ) {
            $this->raiseError(__METHOD__ .'(), \$property parameter is invalid!');
            return false;
        }

        try {
            $index = new \Mtlda\Models\DocumentIndexModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load DocumentIndexModel!');
            return false;
        }

        if (!$index->setFileHash($document->getFileHash())) {
            $this->raiseError(get_class($index) .'::setFileHash() returned false!');
            return false;
        }

        if (!$index->setDocumentText($text)) {
            $this->raiseError(get_class($index) .'::setDocumentText() returned false!');
            return false;
        }

        if (!$index->save()) {
            $this->raiseError(get_class($index) .'::save() returned false!');
            return false;
        }

        return true;
    }

    private function unlinkDirectory($dir)
    {
        global $mtlda;

        if (($files = scandir($dir)) === false) {
            $this->raiseError("scandir on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {
            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                $this->raiseError("realpath() on ". $dir .'/'. $file ." returned false!");
                return false;
            }

            if (!$mtlda->isBelowDirectory(dirname($fqfn), self::CACHE_DIRECTORY)) {
                $this->raiseError("will only handle requested within ". $this::CACHE_DIRECTORY ."!");
                return false;
            }

            if (is_dir($fqfn)) {
                if (!$this->unlinkDirectory($fqfn)) {
                    return false;
                }
            } else {
                if (!unlink($fqfn)) {
                    $this->raiseError("unlink() on {$fqfn} returned false!");
                    return false;
                }
            }
        }

        if (!rmdir($dir)) {
            $this->raiseError("rmdir() on {$dir} returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
