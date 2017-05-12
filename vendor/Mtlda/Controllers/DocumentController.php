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

class DocumentController extends DefaultController
{
    public function perform()
    {
        global $mtlda, $query, $router;

        if (!isset($query->view) || empty($query->view)) {
            static::raiseError("\$query->view is not set!");
            return false;
        }

        if ($query->view != "document") {
            static::raiseError("\$query->view should be document but isn't so!");
            return false;
        }

        if (!$params = $router->parseQueryParams()) {
            static::raiseError("HttpRouterController::parseQueryParams() returned false!");
            return false;
        }

        if (empty($params) || !is_array($params)) {
            static::raiseError("HttpRouterController::parseQueryParams() return an invalid format!");
            return false;
        }

        if (!isset($query->params[0]) || empty($query->params[0])) {
            static::raiseError("Action is not set!");
            return false;
        }

        if (!in_array($query->params[0], array('show'))) {
            static::raiseError("Invalid action!");
            return false;
        }

        if (!isset($query->params[1]) || empty($query->params[1])) {
            static::raiseError("Object id is not set!");
            return false;
        }

        if (($id = $mtlda->parseId($query->params[1])) === false) {
            static::raiseError("Object id can not be parsed!");
            return false;
        }

        if ($query->params[0] == "show") {
            $this->loadDocument($id);
        } else {
            static::raiseError("Unknown action found!");
            return false;
        }

        return true;
    }

    private function loadDocument($id)
    {
        global $mtlda;

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            static::raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($id->model == "document") {
            $content = $this->getArchiveDocumentContent($id);
            if (!isset($content) || empty($content)) {
                static::raiseError("No valid document content returned!");
                return false;
            }
            header('Content-Type: application/pdf');
            header('Content-Length: '. strlen($content));
            print $content;
            return true;
        }

        static::raiseError("Unsupported model requested");
        return false;
    }

    private function getArchiveDocumentContent(&$id)
    {
        global $mtlda;

        $document = new \Mtlda\Models\DocumentModel(array(
            'idx' => $id->id,
            'guid' => $id->guid
        ));

        if (!$document) {
            static::raiseError("Unable to load a DocumentModel!");
            return false;
        }

        $storage = new StorageController;

        if (!($file = $storage->retrieveFile($document))) {
            static::raiseError("StorageController::retrieveFile() returned false");
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

        if (strlen($file['content']) != $document->getFileSize()) {
            static::raiseError("File size of retrieved file does not match archive record!");
            return false;
        }

        if ($file['hash'] != $document->getFileHash()) {
            static::raiseError("File hash of retrieved file does not match archive record!");
            return false;
        }

        return $file['content'];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
