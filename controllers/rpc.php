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

class RpcController extends DefaultController
{
    public function perform()
    {
        global $mtlda, $router, $query;

        if (!isset($query->action)) {
            $mtlda->raiseError("No action specified!");
        }

        if (!$router->isValidRpcAction($query->action)) {
            $mtlda->raiseError("Invalid RPC action: ". htmlentities($query->action, ENT_QUOTES));
            return false;
        }

        switch($query->action) {
            case 'delete':
                $this->rpcDeleteObject();
                break;
            case 'archive':
                $this->rpcArchiveObject();
                break;
            case 'add':
            case 'update':
                $this->rpcUpdateObject();
                break;
            case 'mailimport':
                $this->rpcMailImport();
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
            case 'idle':
                // just do nothing, for debugging
                print "ok";
                break;
            default:
                $mtlda->raiseError("Unknown RPC action\n");
                return false;
                break;
        }

        return true;
    }

    private function rpcDeleteObject()
    {
        global $mtlda;

        if (!isset($_POST['id'])) {
            $mtlda->raiseError("id is missing!");
            return false;
        }

        if (!$mtlda->isValidId($_POST['id'])) {
            $mtlda->raiseError("id looks invalid!");
            return false;
        }

        $id = $_POST['id'];

        /* for flushing queue */
        if (preg_match('/(\w+)-flush$/', $id, $parts)) {

            if (
                !isset($parts) ||
                !is_array($parts) ||
                !isset($parts[1]) ||
                $parts[1] != "queueitem"
            ) {
                $mtlda->raiseError("flushing only supported for queueitems!");
                return false;
            }

            if (!($queue = $mtlda->loadModel('queue'))) {
                $mtlda->raiseError("unable to locate model for QueueModel!");
                return false;
            }

            if (!$queue->flush()) {
                $mtlda->raiseError("QueueModel::flush() returned false!");
                return false;
            }

            print "ok";
            return true;
        }

        $parts = array();
        if (!preg_match('/(\w+)-([0-9]+)-([a-z0-9]+)/', $id, $parts)) {
            $mtlda->raiseError("id in incorrect format!");
            return false;
        }

        /* $parts() should now contain
         * [0] = original id
         * [1] = object (queueitem, etc.)
         * [2] = queue_idx
         * [3] = guid
         */
        if (!array($parts) || empty($parts) || count($parts) != 4) {
            $mtlda->raiseError("id does not contain all required information!");
            return false;
        }

        if (!isset($parts[1]) || !$mtlda->isValidModel($parts[1])) {
            $mtlda->raiseError("id contains an invalid model!");
            return false;
        }

        if (!isset($parts[2]) || !is_numeric($parts[2])) {
            $mtlda->raiseError("id contains an invalid idx!");
            return false;
        }

        if (!isset($parts[3]) || !$mtlda->isValidGuidSyntax($parts[3])) {
            $mtlda->raiseError("id contains an invalid guid!");
            return false;
        }

        $request_object = $parts[1];
        $id = $parts[2];
        $guid = $parts[3];

        if (!($obj = $mtlda->loadModel($request_object, $id, $guid))) {
            $mtlda->raiseError("unable to locate model for {$request_object}!");
            return false;
        }

        if ($obj->delete()) {
            print "ok";
            return true;
        }

        $mtlda->raiseError("unknown error!");
        return false;

    }

    private function rpcArchiveObject()
    {
        global $mtlda;

        if (!isset($_POST['id'])) {
            $mtlda->raiseError("id is missing!");
            return false;
        }

        try {
            $queue = new Controllers\QueueController;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load QueueController!");
            return false;
        }

        if (preg_match('/-all$/', $_POST['id'])) {
            if (!$queue->ArchiveAll()) {
                $mtlda->raiseError("QueueController::ArchiveAll() returned false!");
                return false;
            }
            print "ok";
            return true;
        }

        $id = $_POST['id'];

        $parts = array();
        if (!preg_match('/(\w+)-([0-9]+)-([a-z0-9]+)/', $id, $parts)) {
            $mtlda->raiseError("id in incorrect format!");
            return false;
        }

        /* $parts() should now contain
         * [0] = original id
         * [1] = object (queueitem, etc.)
         * [2] = queue_idx
         * [3] = guid
         */
        if (!array($parts) || empty($parts) || count($parts) != 4) {
            $mtlda->raiseError("id does not contain all required information!");
            return false;
        }

        if (!isset($parts[1]) || !$mtlda->isValidModel($parts[1])) {
            $mtlda->raiseError("id contains an invalid model!");
            return false;
        }

        if (!isset($parts[2]) || !is_numeric($parts[2])) {
            $mtlda->raiseError("id contains an invalid idx!");
            return false;
        }

        if (!isset($parts[3]) || !$mtlda->isValidGuidSyntax($parts[3])) {
            $mtlda->raiseError("id contains an invalid guid!");
            return false;
        }

        $request_object = $parts[1];
        $id = $parts[2];
        $guid = $parts[3];

        if ($request_object != "queueitem") {
            $mtlda->raiseError("archive function can only be used for Queue items!");
            return false;
        }

        if (!$queue->archive($id, $guid)) {
            $mtlda->raiseError("QueueController::archive() returned false!");
            return false;
        }

        print "ok";
        return true;
    }

    private function rpcGetContent()
    {
        global $mtlda, $views;

        $valid_content = array(
                'preview',
        );

        if (!isset($_POST['content'])) {
            $mtlda->raiseError('No content requested!');
            return false;
        }

        if (!in_array($_POST['content'], $valid_content)) {
            $mtlda->raiseError('unknown content requested: '. htmlentities($_POST['content'], ENT_QUOTES));
            return false;
        }

        switch ($_POST['content']) {
            case 'preview':
                $content = $views->load('PreviewView', false);
                break;
        }

        if (isset($content) && !empty($content)) {
            print $content;
            return true;
        }

        $mtlda->raiseError("No content found!");
        return false;
    }

    private function rpcFindPrevNextObject()
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
            $mtlda->raiseError('No model requested!');
            return false;
        }

        if (!in_array($_POST['model'], $valid_models)) {
            $mtlda->raiseError('unknown model requested: '. htmlentities($_POST['model'], ENT_QUOTES));
            return false;
        }

        if (!isset($_POST['id'])) {
            $mtlda->raiseError('id is not set!');
            return false;
        }

        $id = $_POST['id'];

        if (!$mtlda->isValidId($id)) {
            $mtlda->raiseError('\$id is invalid');
            return false;
        }

        if (!isset($_POST['direction'])) {
            $mtlda->raiseError('direction is not set!');
            return false;
        }

        if (!in_array($_POST['direction'], $valid_directions)) {
            $mtlda->raiseError('invalid direction requested: '. htmlentities($_POST['direction'], ENT_QUOTES));
            return false;
        }

        if (($id = $mtlda->parseId($id)) === false) {
            $mtlda->raiseError('Unable to parse \$id');
            return false;
        }

        switch ($id->model) {
            case 'queueitem':
                $model = new Models\QueueItemModel($id->id, $id->guid);
                break;
        }

        if (!isset($model) || empty($model)) {
            $mtlda->raiseError("Model not found: ". htmlentities($id->modek, ENT_QUOTES));
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

    private function rpcUpdateObject()
    {
        global $mtlda;

        $input_fields = array('key', 'id', 'value', 'model');

        foreach ($input_fields as $field) {

            if (!isset($_POST[$field])) {
                $mtlda->raiseError("'{$field}' isn't set in POST request!");
                return false;
            }
            if (empty($_POST[$field])) {
                $mtlda->raiseError("'{$field}' is empty!");
                return false;
            }
            if (!is_string($_POST[$field]) && !is_numeric($_POST[$field])) {
                $mtlda->raiseError("'{$field}' is not from a valid type!");
                return false;
            }
        }

        $key = strtolower($_POST['key']);
        $id = $_POST['id'];
        $value = $_POST['value'];
        $model = $_POST['model'];

        if (!(preg_match("/^([a-z]+)_([a-z_]+)$/", $key, $parts))) {
            $mtlda->raiseError("key looks invalid!");
            return false;
        }

        if ($id != 'add' && !is_numeric($id)) {
            $mtlda->raiseError("id is invalid!");
            return false;
        }

        if (
            !isset($parts) ||
            empty($parts) ||
            !is_array($parts) ||
            count($parts) != 3
        ) {
            $mtlda->raiseError("key looks wrong!");
            return false;
        }

        if (!$mtlda->isValidModel($model)) {
            $mtlda->raiseError("scope contains an invalid model ({$model})!");
            return false;
        }

        if ($id == 'add') {
            $id = null;
        }

        if (!($obj = $mtlda->loadModel($model, $id))) {
            $mtlda->raiseError("Failed to load {$model}!");
            return false;
        }

        // check if model permits RPC updates
        if (!$obj->permitsRpcUpdates()) {
            $mtlda->raiseError("Model {$model} denys RPC updates!");
            return false;
        }

        if (!$obj->permitsRpcUpdateToField($key)) {
            $mtlda->raiseError("Model {$model} denys RPC updates to field {$key}!");
            return false;
        }

        // sanitize input value
        $value = htmlentities($value, ENT_QUOTES);
        $obj->$key = stripslashes($value);

        if (!$obj->save()) {
            $mtlda->raiseError(get_class($obj) ."::save() returned false!");
            return false;
        }

        print "ok";
        return true;
    }

    private function rpcGetKeywords()
    {
        global $mtlda;

        try {
            $keywords = new Models\KeywordsModel;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load KeywordsModel!");
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
            $mtlda->raiseError("json_encode() returned false!");
            return false;
        }

        print $output;
        return true;
    }

    private function rpcSaveKeywords()
    {
        global $mtlda;

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $mtlda->raiseError("No id provided!");
            return false;
        }

        if (!isset($_POST['guid']) || empty($_POST['guid'])) {
            $mtlda->raiseError("No guid provided!");
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($_POST['guid'])) {
            $mtlda->raiseError("guid is invalid!");
            return false;
        }

        /* if no values are provided this usually means
           all keywords have been removed from this document.
        */
        if (
            !isset($_POST['values']) ||
            empty($_POST['values']) ||
            !is_array($_POST['values'])
        ) {
            $_POST['values'] = array();
        }

        $id = $_POST['id'];
        $guid = $_POST['guid'];
        $values = $_POST['values'];

        try {
            $document = new Models\DocumentModel($id, $guid);
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!$document->setKeywords($values)) {
            $mtlda->raiseError("DocumentModel::setKeywords() returned false!");
            return false;
        }

        print "ok";
        return true;
    }

    private function rpcSaveDescription()
    {
        global $mtlda;

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $mtlda->raiseError("No id provided!");
            return false;
        }

        if (!isset($_POST['guid']) || empty($_POST['guid'])) {
            $mtlda->raiseError("No guid provided!");
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($_POST['guid'])) {
            $mtlda->raiseError("guid is invalid!");
            return false;
        }

        /* if no values are provided this usually means
           all keywords have been removed from this document.
        */
        if (
            !isset($_POST['description']) ||
            empty($_POST['description']) ||
            !is_string($_POST['description'])
        ) {
            $_POST['description'] = '';
        }

        $id = $_POST['id'];
        $guid = $_POST['guid'];
        $description = $_POST['description'];

        try {
            $document = new Models\DocumentModel($id, $guid);
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!$document->setDescription($description)) {
            $mtlda->raiseError("DocumentModel::setDescription() returned false!");
            return false;
        }

        print "ok";
        return true;
    }

    protected function rpcMailImport()
    {
        global $mtlda;

        try {
            $importer = new MailImportController;
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load MailImportController!");
            return false;
        }

        if (!$importer->fetch()) {
            $mtlda->raiseError("MailImportController::fetch() returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
