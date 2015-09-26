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
use MTLDA\Controllers;

class DocumentController extends DefaultController
{
    public function perform()
    {
        global $mtlda, $query, $router;

        if (!isset($query->view) || empty($query->view)) {
            $mtlda->raiseError("\$query->view is not set!");
            return false;
        }

        if ($query->view != "document") {
            $mtlda->raiseError("\$query->view should be document but isn't so!");
            return false;
        }

        if (!$params = $router->parseQueryParams()) {
            $mtlda->raiseError("HttpRouterController::parseQueryParams() returned false!");
            return false;
        }

        if (empty($params) || !is_array($params)) {
            $mtlda->raiseError("HttpRouterController::parseQueryParams() return an invalid format!");
            return false;
        }

        if (!isset($query->params[0]) || empty($query->params[0])) {
            $mtlda->raiseError("Action is not set!");
            return false;
        }

        if (!in_array($query->params[0], array('show','sign', 'delete'))) {
            $mtlda->raiseError("Invalid action!");
            return false;
        }

        if (!isset($query->params[1]) || empty($query->params[1])) {
            $mtlda->raiseError("Object id is not set!");
            return false;
        }

        if (!$mtlda->isValidId($query->params[1])) {
            $mtlda->raiseError("Object id is invalid!");
            return false;
        }

        if (!($id = $mtlda->parseId($query->params[1]))) {
            $mtlda->raiseError("Object id can not be parsed!");
            return false;
        }

        if ($query->params[0] == "show") {
            $this->loadDocument($id);
        } elseif ($query->params[0] == "sign") {
            $this->signDocument($id);
        } elseif ($query->params[0] == "delete") {
            $this->deleteDocument($id);
        } else {
            $mtlda->raiseError("Unknown action found!");
            return false;
        }

        return true;
    }

    private function loadDocument($id)
    {
        global $mtlda;

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            $mtlda->raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($id->model == "document") {
            $content = $this->getArchiveDocumentContent($id);
            if (!isset($content) || empty($content)) {
                $mtlda->raiseError("No valid document content returned!");
                return false;
            }
            header('Content-Type: application/pdf');
            header('Content-Length: '. strlen($content));
            print $content;
            return true;
        }

        $mtlda->raiseError("Unsupported model requested");
        return false;
    }

    private function signDocument($id)
    {
        global $mtlda, $router;

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            $mtlda->raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($id->model != "document") {
            $mtlda->raiseError("Can only handle Documents!");
            return false;
        }

        $document = new Models\DocumentModel($id->id, $id->guid);
        if (!$document) {
            $mtlda->raiseError("Unable to load a DocumentModel!");
            return false;
        }

        if ($document->document_signed_copy == 'Y') {
            $mtlda->raiseError(__TRAIT__ ." will not resign an already signed document!");
            return false;
        }

        try {
            $archive = new Controllers\ArchiveController;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load ArchiveController!");
            return false;
        }

        if (!$archive) {
            $mtlda->raiseError("Unable to load ArchiveController!");
            return false;
        }

        if (!$archive->sign($document)) {
            $mtlda->raiseError("ArchiveController::sign() returned false!");
            return false;
        }

        $router->redirectTo(
            'archive',
            'show',
            $document->document_idx ."-". $document->document_guid
        );
    }

    private function deleteDocument($id)
    {
        global $mtlda, $router;

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            $mtlda->raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($id->model != "document") {
            $mtlda->raiseError("Can only handle Documents!");
            return false;
        }

        $document = new Models\DocumentModel($id->id, $id->guid);
        if (!$document) {
            $mtlda->raiseError("Unable to load a DocumentModel!");
            return false;
        }

        if ($document->document_version == 1) {
            $mtlda->raiseError(__TRAIT__ ." cannot delete the original imported document!");
            return false;
        }

        $parent_idx = $document->document_derivation;
        $parent_guid = $document->document_derivation_guid;

        if (!$document->delete()) {
            $mtlda->raiseError("DocumentModel::delete() returned false!");
            return false;
        }

        $router->redirectTo(
            'archive',
            'show',
            $parent_idx ."-". $parent_guid
        );

        return true;
    }

    private function getArchiveDocumentContent(&$id)
    {
        global $mtlda;

        $document = new Models\DocumentModel($id->id, $id->guid);

        if (!$document) {
            $mtlda->raiseError("Unable to load a DocumentModel!");
            return false;
        }

        // don't rembmer the purpose of this code
        /*if ($document->document_version != 1 && $document->document_derivation != 0) {

            $descent = new Models\DocumentModel($document->document_derivation);
            if (!$descent) {
                $mtlda->raiseError("Unable to load parent DocumentModel!");
                return false;
            }
        }*/

        $storage = new StorageController;

        if (!($file = $storage->retrieveFile($document))) {
            $mtlda->raiseError("StorageController::retrieveFile() returned false");
            return false;
        }

        if (
            !isset($file) ||
            empty ($file) ||
            !is_array($file) ||
            !isset($file['hash'], $file['content']) ||
            empty($file['hash']) ||
            empty($file['content'])
        ) {
            $mtlda->raiseError("StorageController::retireveFile() returned an invalid file");
            return false;
        }

        if (strlen($file['content']) != $document->document_file_size) {
            $mtlda->raiseError("File size of retrieved file does not match archive record!");
            return false;
        }

        if ($file['hash'] != $document->document_file_hash) {
            $mtlda->raiseError("File hash of retrieved file does not match archive record!");
            return false;
        }

        return $file['content'];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
