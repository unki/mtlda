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
        global $mtlda, $query;

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
        global $mtlda, $query;

        if (!isset($_POST['id'])) {
            $mtlda->raiseError("id is missing!");
            return false;
        }

        if (!$mtlda->isValidId($_POST['id'])) {
            $mtlda->raiseError("id looks invalid!");
            return false;
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

        if (!($obj = $mtlda->loadModel($request_object, $id, $guid))) {
            $mtlda->raiseError("unable to locate model for ${request_object}!");
            return false;
        }

        $storage = new StorageController;

        if (!$storage) {
            $mtlda->raiseError("unable to load StorageController!");
            return false;
        }

        if (!$storage->archive($obj)) {
            $mtlda->raiseError("StorageController::archive() exited with an error!");
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
        global $mtlda, $query;

        $valid_update_ids = array(
            'keyword',
        );

        if (!isset($_POST['object'])) {
            $mtlda->raiseError("object is missing!");
            return false;
        }

        if (!($parts = preg_match("/^(\w)_(\w)$/", $_POST['object']))) {
            $mtlda->raiseError("object looks invalid!");
            return false;
        }

        $object = $_POST['object'];

        if (!in_array($id, $valid_update_ids)) {
            $mtlda->raiseError("id {$id} is not allowed to be updated!");
            return false;
        }

        if (!$mtlda->isValidModel($id)) {
            $mtlda->raiseError("id contains an invalid model!");
            return false;
        }

        if (!($obj = $mtlda->loadModel($id))) {
            $mtlda->raiseError("unable to locate model for {$id}!");
            return false;
        }

        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $mtlda->raiseError("name is missing!");
            return false;
        }

        $name = htmlentities($_POST['name'], ENT_QUOTES);

        if (!isset($_POST['value']) || empty($_POST['value'])) {
            $mtlda->raiseError("value is missing!");
            return false;
        }

        $value = htmlentities($_POST['value'], ENT_QUOTES);

        if (preg_match("/.*_new$/", $_POST['name'])) {
            print_r($_POST);
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

        if ($obj->save()) {
            print "ok";
            return true;
        }

        $mtlda->raiseError("unknown error!");
        return false;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
