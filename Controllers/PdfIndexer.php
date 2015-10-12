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

use Mtlda\Models;
use \Smalot\PdfParser;

class PdfIndexerController extends DefaultController
{
    private $parser;

    public function __construct()
    {
        global $mtlda;

        try {
            $this->parser = new \Smalot\PdfParser\Parser();
        } catch (\Exception $e) {
            $mtlda->raiseError('Failed to load PdfParser!', true);
            return false;
        }

        return true;
    }

    public function scan(&$document)
    {
        global $mtlda;

        if (!is_a($document, 'Mtlda\Models\DocumentModel')) {
            $mtlda->raiseError(__METHOD__ .' only supports DocumentModels!');
            return false;
        }

        $this->sendMessage('scan-reply', 'Retrieving document from archive.', '40%');

        if (!$fqpn = $document->getFilePath()) {
            $mtlda->raiseError(get_class($document) .'::getFilePath() returned false!');
            return false;
        }

        if (!file_exists($fqpn)) {
            $mtlda->raiseError("{$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $mtlda->raiseError("{$fqpn} is not readable!");
            return false;
        }

        $this->sendMessage('scan-reply', 'Parsing document.', '50%');

        try {
            $pdf = $this->parser->parseFile($fqpn);
        } catch (\Exception $e) {
            $mtlda->raiseError(get_class($this->parser) .'::parseFile() returned false! '. $e->getMessage());
            return false;
        }

        $this->sendMessage('scan-reply', 'Extracting text parts from document.', '60%');

        try {
            $text = $pdf->getText();
        } catch (\Exception $e) {
            $mtlda->raiseError(get_class($pdf) .'::getText() returned false!');
            return false;
        }

        if (!isset($text) || empty($text)) {
            return true;
        }

        try {
            $index = new Models\DocumentIndexModel;
        } catch (\Exception $e) {
            $mtlda->raiseError(__METHOD__ .'(), failed to load DocumentIndexModel!');
            return false;
        }

        $index->setDocumentIdx($document->getId());
        $index->setDocumentGuid($document->getGuid());
        $index->setDocumentText($text);
        $index->save();
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
