<?php

namespace MTLDA\Controllers;

use MTLDA\Models;

class RpcController
{
    public function perform()
    {
        global $router, $query;

        if (!isset($query->action)) {
            print "No action specified!";
        }

        if (!$router->isValidRpcAction($query->action)) {
            print "Invalid RPC action";
            return false;
        }

        switch($query->action) {
            case 'delete':
                $this->rpcDeleteObject();
                break;
            case 'archive':
                $this->rpcArchiveObject();
                break;
            case 'toggle':
                $this->rpc_toggle_object_status();
                break;
            case 'clone':
                $this->rpc_clone_object();
                break;
            case 'alter-position':
                $this->rpc_alter_position();
                break;
            case 'get-content':
                $this->rpc_get_content();
                break;
            case 'get-sub-menu':
                $this->rpc_get_sub_menu();
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
        if (preg_match('/(\w+)-([0-9]+)\+([a-z0-9]+)/', $id, $parts) === false) {
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

        if (!($obj = $this->loadModel($request_object, $id, $guid))) {
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
        if (preg_match('/(\w+)-([0-9]+)\+([a-z0-9]+)/', $id, $parts) === false) {
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

        if (!($obj = $this->loadModel($request_object, $id, $guid))) {
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

    public function loadModel($object_name, $id = null, $guid = null)
    {
        switch($object_name) {
            case 'queueitem':
                $obj = new Models\QueueItemModel($id, $guid);
                break;
        }

        if (isset($obj)) {
            return $obj;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
