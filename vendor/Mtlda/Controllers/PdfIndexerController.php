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

    public function __construct()
    {
        global $mtlda;

        try {
            $this->parser = new \Smalot\PdfParser\Parser();
        } catch (\Exception $e) {
            $this->raiseError('Failed to load PdfParser!', true);
            return false;
        }

        return true;
    }

    public function scan(&$document)
    {
        global $mtlda;

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

        $this->sendMessage('scan-reply', 'Retrieving document from archive.', '40%');

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
            $indices = new \Mtlda\Models\DocumentIndicesModel($idx, $guid);
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

        $this->sendMessage('scan-reply', 'Parsing document.', '50%');

        try {
            $pdf = $this->parser->parseFile($fqpn);
        } catch (\Exception $e) {
            $this->raiseError(get_class($this->parser) .'::parseFile() returned false! '. $e->getMessage());
            return false;
        }

        $this->sendMessage('scan-reply', 'Extracting text parts from document.', '60%');

        try {
            $text = $pdf->getText();
        } catch (\Exception $e) {
            $this->raiseError(get_class($pdf) .'::getText() returned false!');
            return false;
        }

        if (isset($text) && !empty($text)) {
            try {
                $index = new \Mtlda\Models\DocumentIndexModel;
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ .'(), failed to load DocumentIndexModel!');
                return false;
            }

            if (!$index->setDocumentIdx($document->getId())) {
                $this->raiseError(get_class($index) .'::setDocumentIdx() returned false!');
                return false;
            }
            if (!$index->setDocumentGuid($document->getGuid())) {
                $this->raiseError(get_class($index) .'::setDocumentGuid() returned false!');
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
        }

        try {
            $details  = $pdf->getDetails();
        } catch (\Exception $e) {
            $this->raiseError(get_class($pdf) .'::getDetails() returned false!');
            return false;
        }

        if (isset($details) && !empty($details)) {
            foreach ($details as $property => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                if (empty($property) || empty($value)) {
                    continue;
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
            }
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
