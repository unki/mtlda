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

class RpcController extends \Thallium\Controllers\RpcController
{
    public function performApplicationSpecifc()
    {
        global $mtlda, $router, $query;

        switch ($query->action) {
            case 'find-prev-next':
                $this->rpcFindPrevNextObject();
                break;
            case 'get-content':
                $this->rpcGetContent();
                break;
            case 'save-keywords':
                $this->rpcSaveKeywords();
                break;
            case 'save-description':
                $this->rpcSaveDescription();
                break;
            case 'submit-messages':
                $this->rpcSubmitToMessageBus();
                break;
            case 'retrieve-messages':
                $this->rpcRetrieveFromMessageBus();
                break;
            case 'delete-expired-documents':
                $this->rpcDeleteExpiredDocuments();
                break;
            default:
                return false;
                break;
        }

        return true;
    }

    protected function rpcFindPrevNextObject()
    {
        global $mtlda, $views;

        $valid_models = array(
            'queueitem',
        );

        $valid_directions = array(
            'next',
            'prev',
        );

        if (!isset($_POST['model'])) {
            $this->raiseError('No model requested!');
            return false;
        }

        if (!in_array($_POST['model'], $valid_models)) {
            $this->raiseError('unknown model requested: '. htmlentities($_POST['model'], ENT_QUOTES));
            return false;
        }

        if (!isset($_POST['id'])) {
            $this->raiseError('id is not set!');
            return false;
        }

        $id = $_POST['id'];

        if (!$mtlda->isValidId($id)) {
            $this->raiseError('\$id is invalid');
            return false;
        }

        if (!isset($_POST['direction'])) {
            $this->raiseError('direction is not set!');
            return false;
        }

        if (!in_array($_POST['direction'], $valid_directions)) {
            $this->raiseError('invalid direction requested: '. htmlentities($_POST['direction'], ENT_QUOTES));
            return false;
        }

        if (($id = $mtlda->parseId($id)) === false) {
            $this->raiseError('Unable to parse \$id');
            return false;
        }

        switch ($id->model) {
            case 'queueitem':
                $model = new \Mtlda\Models\QueueItemModel($id->id, $id->guid);
                break;
        }

        if (!isset($model) || empty($model)) {
            $this->raiseError("Model not found: ". htmlentities($id->modek, ENT_QUOTES));
            return false;
        }

        switch ($_POST['direction']) {
            case 'prev':
                $prev = $model->prev();
                if ($prev) {
                    print "queueitem-". $prev;
                }
                break;
            case 'next':
                $next = $model->next();
                if ($next) {
                    print "queueitem-". $next;
                }
                break;
        }

        return true;
    }

    protected function rpcSaveKeywords()
    {
        global $mtlda;

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $this->raiseError("No id provided!");
            return false;
        }

        if (!isset($_POST['guid']) || empty($_POST['guid'])) {
            $this->raiseError("No guid provided!");
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($_POST['guid'])) {
            $this->raiseError("guid is invalid!");
            return false;
        }

        /* if no values are provided this usually means
           all keywords have been removed from this document.
        */
        if (!isset($_POST['values']) ||
            empty($_POST['values'])
        ) {
            $_POST['values'] = array();
        }

        if (!is_array($_POST['values']) && preg_match('/^([0-9]+)$/', $_POST['values'])) {
            $_POST['values'] = array($_POST['values']);
        } elseif (!is_array($_POST['values']) && preg_match('/^([0-9]+),([0-9]+)/', $_POST['values'])) {
            $_POST['values'] = explode(',', $_POST['values']);
        }

        $id = $_POST['id'];
        $guid = $_POST['guid'];
        $values = $_POST['values'];

        try {
            $document = new \Mtlda\Models\DocumentModel($id, $guid);
        } catch (\Exception $e) {
            $this->raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!$document->setKeywords($values)) {
            $this->raiseError("DocumentModel::setKeywords() returned false!");
            return false;
        }

        print "ok";
        return true;
    }

    protected function rpcSaveDescription()
    {
        global $mtlda;

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $this->raiseError("No id provided!");
            return false;
        }

        if (!isset($_POST['guid']) || empty($_POST['guid'])) {
            $this->raiseError("No guid provided!");
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($_POST['guid'])) {
            $this->raiseError("guid is invalid!");
            return false;
        }

        /* if no values are provided this usually means
           all keywords have been removed from this document.
        */
        if (!isset($_POST['description']) ||
            empty($_POST['description']) ||
            !is_string($_POST['description'])
        ) {
            $_POST['description'] = '';
        }

        $id = $_POST['id'];
        $guid = $_POST['guid'];
        $description = $_POST['description'];

        try {
            $document = new \Mtlda\Models\DocumentModel($id, $guid);
        } catch (\Exception $e) {
            $this->raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!$document->setDescription($description)) {
            $this->raiseError("DocumentModel::setDescription() returned false!");
            return false;
        }

        print "ok";
        return true;
    }

    protected function rpcDeleteExpiredDocuments()
    {
        try {
            $archive = new \Mtlda\Controllers\ArchiveController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .', failed to load ArchiveController!');
            return false;
        }

        if (!($archive->deleteExpiredDocuments())) {
            $this->raiseError(get_class($archive) .'::deleteExpiredDocuments() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
