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
    public function perform()
    {
        global $mtlda, $router, $query;

        if (!isset($query->action)) {
            $this->raiseError("No action specified!");
        }

        if (!$router->isValidRpcAction($query->action)) {
            $this->raiseError("Invalid RPC action: ". htmlentities($query->action, ENT_QUOTES));
            return false;
        }

        switch ($query->action) {
            case 'delete':
                $this->rpcDelete();
                break;
            case 'archive':
                $this->rpcArchiveObject();
                break;
            case 'add':
            case 'update':
                $this->rpcUpdateObject();
                break;
            case 'find-prev-next':
                $this->rpcFindPrevNextObject();
                break;
            /*case 'toggle':
                $this->rpc_toggle_object_status();
                break;
            case 'clone':
                $this->rpc_clone_object();
                break;
            case 'alter-position':
                $this->rpc_alter_position();
                break;
            case 'get-sub-menu':
                $this->rpc_get_sub_menu();
                break;*/
            case 'get-content':
                $this->rpcGetContent();
                break;
            case 'get-keywords':
                $this->rpcGetKeywords();
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
            case 'idle':
                // just do nothing, for debugging
                print "ok";
                break;
            default:
                $this->raiseError("Unknown RPC action\n");
                return false;
                break;
        }

        return true;
    }

    protected function rpcArchiveObject()
    {
        global $mtlda;

        if (!isset($_POST['id'])) {
            $this->raiseError("id is missing!");
            return false;
        }

        try {
            $queue = new \Mtlda\Controllers\QueueController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load QueueController!");
            return false;
        }

        if (preg_match('/-all$/', $_POST['id'])) {
            if (!$queue->ArchiveAll()) {
                $this->raiseError("QueueController::ArchiveAll() returned false!");
                return false;
            }
            print "ok";
            return true;
        }

        $id = $_POST['id'];

        $parts = array();
        if (!preg_match('/(\w+)-([0-9]+)-([a-z0-9]+)/', $id, $parts)) {
            $this->raiseError("id in incorrect format!");
            return false;
        }

        /* $parts() should now contain
         * [0] = original id
         * [1] = object (queueitem, etc.)
         * [2] = queue_idx
         * [3] = guid
         */
        if (!array($parts) || empty($parts) || count($parts) != 4) {
            $this->raiseError("id does not contain all required information!");
            return false;
        }

        if (!isset($parts[1]) || !$mtlda->isValidModel($parts[1])) {
            $this->raiseError("id contains an invalid model!");
            return false;
        }

        if (!isset($parts[2]) || !is_numeric($parts[2])) {
            $this->raiseError("id contains an invalid idx!");
            return false;
        }

        if (!isset($parts[3]) || !$mtlda->isValidGuidSyntax($parts[3])) {
            $this->raiseError("id contains an invalid guid!");
            return false;
        }

        $request_object = $parts[1];
        $id = $parts[2];
        $guid = $parts[3];

        if ($request_object != "queueitem") {
            $this->raiseError("archive function can only be used for Queue items!");
            return false;
        }

        if (!$queue->archive($id, $guid)) {
            $this->raiseError("QueueController::archive() returned false!");
            return false;
        }

        print "ok";
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

    protected function rpcGetKeywords()
    {
        global $mtlda;

        try {
            $keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load KeywordsModel!");
            return false;
        }

        $result = array();
        foreach ($keywords->avail_items as $keyword) {
            $item = $keywords->items[$keyword];
            array_push($result, array(
                'name' => $item->keyword_name,
                'value' => $item->keyword_idx
            ));
        }

        $output = json_encode(array(
            'success' => 'true',
            'results' => $result
        ));

        if ($output === false) {
            $this->raiseError("json_encode() returned false!");
            return false;
        }

        print $output;
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

    protected function rpcDelete()
    {
        global $mtlda;

        $input_fields = array('id', 'guid', 'model');

        foreach ($input_fields as $field) {
            if (!isset($_POST[$field])) {
                $this->raiseError(__METHOD__ ."'{$field}' isn't set in POST request!");
                return false;
            }
            if (empty($_POST[$field])) {
                $this->raiseError(__METHOD__ ."'{$field}' is empty!");
                return false;
            }
            if (!is_string($_POST[$field]) && !is_numeric($_POST[$field])) {
                $this->raiseError(__METHOD__ ."'{$field}' is not from a valid type!");
                return false;
            }
        }

        $id = $_POST['id'];
        $guid = $_POST['guid'];
        $model = $_POST['model'];

        if (!$mtlda->isValidId($id) && $id != 'flush') {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($guid) && $guid != 'flush') {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        if (($model_name = $mtlda->getModelByNick($model)) === false) {
            $this->raiseError(get_class($mtlda) .'::getModelNameByNick() returned false!');
            return false;
        }

        /* special delete operation 'flush' */
        if ($id == 'flush' && $guid == 'flush') {
            if (($obj = $mtlda->loadModel($model_name)) === false) {
                $this->raiseError(get_class($mtlda) .'::loadModel() returned false!');
                return false;
            }

            if (!method_exists($obj, 'flush')) {
                $this->raiseError(__METHOD__ ."(), model {$model_name} does not provide a flush() method!");
                return false;
            }
            if (!$obj->permitsRpcActions('flush')) {
                $this->raiseError(__METHOD__ ."(), model {$model_name} does not support flush-opertions!");
                return false;
            }
            if (!$obj->flush()) {
                $this->raiseError(get_class($obj) .'::flush() returned false!');
                return false;
            }
            print "ok";
            return true;
        }

        if (($obj = $mtlda->loadModel($model_name, $id, $guid)) === false) {
            $this->raiseError(get_class($mtlda) .'::loadModel() returned false!');
            return false;
        }

        if (!method_exists($obj, 'delete')) {
            $this->raiseError(__METHOD__ ."(), model {$model_name} does not provide a delete() method!");
            return false;
        }

        if (!$obj->permitsRpcActions('delete')) {
            $this->raiseError(get_class($obj) .' does not permit "delete" via a RPC call!');
            return false;
        }

        if (!$obj->delete()) {
            $this->raiseError(get_class($obj) .'::delete() returned false!');
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
