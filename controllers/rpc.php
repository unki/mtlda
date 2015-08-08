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
        global $router, $query;

        if (!isset($query->action)) {
            print "No action specified!";
        }

        if (!$router->isValidRpcAction($query->action)) {
            print "Invalid RPC action: ". htmlentities($query->action, ENT_QUOTES);
            return false;
        }

        switch($query->action) {
            case 'delete':
                $this->rpcDeleteObject();
                break;
            case 'archive':
                $this->rpcArchiveObject();
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
                print "Unknown RPC action\n";
                return false;
                break;
        }

        return true;

    }

    private function rpcDeleteObject()
    {
        global $mtlda, $query;

        if (!isset($_POST['id'])) {
            print "id is missing!";
            return false;
        }

        if (!$mtlda->isValidId($_POST['id'])) {
            print "id looks invalid!";
            return false;
        }

        $id = $_POST['id'];

        $parts = array();
        if (preg_match('/(\w+)-([0-9]+)-([a-z0-9]+)/', $id, $parts) === false) {
            print "id in incorrect format!";
            return false;
        }

        /* $parts() should now contain
         * [0] = original id
         * [1] = object (queueitem, etc.)
         * [2] = queue_idx
         * [3] = guid
         */
        if (!array($parts) || empty($parts) || count($parts) != 4) {
            print "id does not contain all required information!";
            return false;
        }

        if (!isset($parts[1]) || !$mtlda->isValidModel($parts[1])) {
            print "id contains an invalid model!";
            return false;
        }

        if (!isset($parts[2]) || !is_numeric($parts[2])) {
            print "id contains an invalid idx!";
            return false;
        }

        if (!isset($parts[3]) || !$mtlda->isValidGuidSyntax($parts[3])) {
            print "id contains an invalid guid!";
            return false;
        }

        $request_object = $parts[1];
        $id = $parts[2];
        $guid = $parts[3];

        if (!($obj = $mtlda->loadModel($request_object, $id, $guid))) {
            print "unable to locate model for ". $request_object;
            return false;
        }

        if ($obj->delete()) {
            print "ok";
            return true;
        }

        print "unknown error";
        return false;

    }

    private function rpcArchiveObject()
    {
        global $mtlda, $query;

        if (!isset($_POST['id'])) {
            print "id is missing!";
            return false;
        }

        if (!$mtlda->isValidId($_POST['id'])) {
            print "id looks invalid!";
            return false;
        }

        $id = $_POST['id'];

        $parts = array();
        if (preg_match('/(\w+)-([0-9]+)-([a-z0-9]+)/', $id, $parts) === false) {
            print "id in incorrect format!";
            return false;
        }

        /* $parts() should now contain
         * [0] = original id
         * [1] = object (queueitem, etc.)
         * [2] = queue_idx
         * [3] = guid
         */
        if (!array($parts) || empty($parts) || count($parts) != 4) {
            print "id does not contain all required information!";
            return false;
        }

        if (!isset($parts[1]) || !$mtlda->isValidModel($parts[1])) {
            print "id contains an invalid model!";
            return false;
        }

        if (!isset($parts[2]) || !is_numeric($parts[2])) {
            print "id contains an invalid idx!";
            return false;
        }

        if (!isset($parts[3]) || !$mtlda->isValidGuidSyntax($parts[3])) {
            print "id contains an invalid guid!";
            return false;
        }

        $request_object = $parts[1];
        $id = $parts[2];
        $guid = $parts[3];

        if ($request_object != "queueitem") {
            print "archive function can only be used for Queue items!";
            return false;
        }

        if (!($obj = $mtlda->loadModel($request_object, $id, $guid))) {
            print "unable to locate model for ${request_object}!";
            return false;
        }

        $storage = new StorageController;

        if (!$storage) {
            print "unable to load StorageController!";
            return false;
        }

        if (!$storage->archive($obj)) {
            print "StorageController::archive() exited with an error!";
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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
