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

namespace MTLDA\Controllers;

use MTLDA\Models;

class DocumentController
{
    public function open()
    {
        global $mtlda, $query;

        if (!isset($query->view) || empty($query->view)) {
            $mtlda->raiseError("\$query->view is not set!");
            return false;
        }

        if ($query->view == "document") {
            $this->loadDocument();
        }

        return true;
    }

    private function loadDocument()
    {
        global $mtlda, $config, $query;

        if (!isset($query->params) || !isset($query->params[0]) || empty($query->params[0])) {
            $mtlda->raiseError("\$query->params is not set!");
            return false;
        }

        $id = $query->params[1];

        if (!$mtlda->isValidId($id)) {
            $mtlda->raiseError("\$id is invalid!");
            return false;
        }

        if (($id = $mtlda->parseId($id)) === false) {
            $mtlda->raiseError("unable to parse id!");
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            $mtlda->raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($id->model == "archiveitem") {
            $this->loadArchiveDocument($id);
        }
    }

    private function loadArchiveDocument(&$id)
    {
        global $mtlda;

        $document = new Models\ArchiveItemModel($id->id, $id->guid);

        if (!$document) {
            $mtlda->raiseError("Unable to load a ArchiveItemModel!");
            return false;
        }

        if ($document->archive_version != 1 && $document->archive_derivation != 0) {

            $descent = new Models\ArchiveItemModel($document->archive_derivation);
            if (!$descent) {
                $mtlda->raiseError("Unable to load parent ArchiveItemModel!");
                return false;
            }
        }

        $storage = new StorageController;

        if (isset($descent) && !empty($descent)) {
            if (!($content = $storage->retrieveFile($document, $descent->archive_file_hash))) {
                $mtlda->raiseError("StorageController::retrieveFile() returned false");
                return false;
            }
        } else {
            if (!($content = $storage->retrieveFile($document, $document->archive_file_hash))) {
                $mtlda->raiseError("StorageController::retrieveFile() returned false");
                return false;
            }
        }

        header('Content-Type: application/pdf');
        echo $content;
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
