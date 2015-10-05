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

use MTLDA\Views;
use MTLDA\Models;
use MTLDA\Controllers;

class MTLDA extends DefaultController
{
    const VERSION = "0.3";

    private $verbosity_level = LOG_WARNING;

    public function __construct($mode = null)
    {
        $GLOBALS['mtlda'] =& $this;

        $this->loadController("Config", "config");
        $this->loadController("Requirements", "requirements");

        global $requirements;

        if (!$requirements->check()) {
            $this->raiseError("Error - not all MTLDA requirements are met. Please check!", true);
        }

        // no longer needed
        unset($requirements);

        $this->loadController("Audit", "audit");
        $this->loadController("Database", "db");

        if (!$this->isCmdline()) {
            $this->loadController("HttpRouter", "router");
            global $router;
            $GLOBALS['query'] = $router->getQuery();
            global $query;
        }

        if (isset($query) && isset($query->view) && $query->view == "install") {
            $mode = "install";
        }

        if ($mode != "install" && $this->checkUpgrade()) {
            return false;
        }

        if (isset($mode) and $mode == "queue_only") {

            $this->loadController("Import", "import");
            global $import;

            if (!$import->handleQueue()) {
                $this->raiseError("ImportController::handleQueue returned false!");
                return false;
            }

            unset($import);

        } elseif (isset($mode) and $mode == "install") {

            $this->loadController("Installer", "installer");
            global $installer;

            if (!$installer->setup()) {
                exit(1);
            }

            unset($installer);
            exit(0);
        }

        $this->loadController("Session", "session");
        $this->loadController("MessageBus", "mbus");

        if (!$this->performActions()) {
            $this->raiseError(__CLASS__ .'::performActions() returned false!', true);
            return false;
        }
        return true;
    }

    public function startup()
    {
        global $config, $db, $router, $query;

        if (!isset($query->view)) {
            $this->raiseError("Error - parsing request URI hasn't unveiled what to view!");
            return false;
        }

        $this->loadController("Views", "views");
        global $views;


        if ($router->isRpcCall()) {

            if (!$this->rpcHandler()) {
                $this->raiseError("MTLDA::rpcHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($router->isImageCall()) {

            if (!$this->imageHandler()) {
                $this->raiseError("MTLDA::imageHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($router->isDocumentCall()) {

            if (!$this->documentHandler()) {
                $this->raiseError("MTLDA::documentHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($router->isUploadCall()) {

            if (!$this->uploadHandler()) {
                $this->raiseError("MTLDA::uploadHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($page_name = $views->getViewName($query->view)) {

            if (!$page = $views->load($page_name)) {
                $this->raiseError("ViewController:load() returned false!");
                return false;
            }

            print $page;
            return true;
        }

        $this->raiseError("Unable to find a view for ". $query->view);
        return false;
    }

    public function raiseError($string, $stop = false)
    {
        if (defined('DB_NOERROR')) {
            $this->last_error = $string;
            return;
        }

        print "<br /><br />". $string ."<br /><br />\n";

        try {
            throw new ExceptionController;
        } catch (ExceptionController $e) {
            print "<br /><br />\n";
            $this->write($e, LOG_WARNING);
        }

        if ($stop) {
            die;
        }

        $this->last_error = $string;

    } // raiseError()

    public function write($logtext, $loglevel = LOG_INFO, $override_output = null, $no_newline = null)
    {
        if (isset($this->config->logging)) {
            $logtype = $this->config->logging;
        } else {
            $logtype = 'display';
        }

        if (isset($override_output) || !empty($override_output)) {
            $logtype = $override_output;
        }

        if ($loglevel > $this->getVerbosity()) {
            return true;
        }

        switch($logtype) {
            default:
            case 'display':
                print $logtext;
                if (!$this->isCmdline()) {
                    print "<br />";
                } elseif (!isset($no_newline)) {
                    print "\n";
                }
                break;
            case 'errorlog':
                error_log($logtext);
                break;
            case 'logfile':
                error_log($logtext, 3, $this->config->log_file);
                break;
        }

        return true;

    } // write()

    public function isCmdline()
    {
        if (php_sapi_name() == 'cli') {
            return true;
        }

        return false;

    } // isCmdline()

    public function setVerbosity($level)
    {
        /*if (!in_array($level, array(0 => LOG_INFO, 1 => LOG_WARNING, 2 => LOG_DEBUG))) {
            $this->raiseError("Unknown verbosity level ". $level);
        }

        $this->verbosity_level = $level;*/

    } // setVerbosity()

    public function getVerbosity()
    {
        return $this->verbosity_level;

    } // getVerbosity()

    private function rpcHandler()
    {
        $this->loadController("Rpc", "rpc");
        global $rpc;

        if (!$rpc->perform()) {
            $this->raiseError("RpcController::perform() returned false!");
            return false;
        }
        unset($rpc);

        ob_start();
        // invoke the MessageBus processor so pending tasks can
        // be handled. but suppress any output.
        if (!$this->performActions()) {
            $this->raiseError('performActions() returned false!');
            return false;
        }
        ob_end_clean();

        return true;
    }

    private function imageHandler()
    {
        $this->loadController("Image", "image");
        global $image;

        if (!$image->perform()) {
            $this->raiseError("ImageController::perform() returned false!");
            return false;
        }

        unset($image);
        return true;
    }

    private function documentHandler()
    {
        $this->loadController("Document", "document");
        global $document;

        if (!$document->perform()) {
            $this->raiseError("DocumentController::perform() returned false!");
            return false;
        }

        unset($document);
        return true;
    }

    private function uploadHandler()
    {
        $this->loadController("Upload", "upload");
        global $upload;

        if (!$upload->perform()) {
            $this->raiseError("UploadController::perform() returned false!");
            return false;
        }

        unset($upload);
        return true;
    }

    public function isValidId($id)
    {
        // disable for now, 20150809
        /*$id = (int) $id;

        if (is_numeric($id)) {
            return true;
        }

        return false;*/
        return true;
    }

    public function isValidModel($model)
    {
        $valid_models = array(
            'queue',
            'queueitem',
            'document',
            'keyword',
        );

        if (in_array($model, $valid_models)) {
            return true;
        }

        return false;
    }

    public function isValidGuidSyntax($guid)
    {
        if (strlen($guid) == 64) {
            return true;
        }

        return false;
    }

    public function parseId($id)
    {
        if (!isset($id) || empty($id)) {
            return false;
        }

        $parts = array();

        if (preg_match('/(\w+)-([0-9]+)-([a-z0-9]+)/', $id, $parts) === false) {
            return false;
        }

        if (!isset($parts) || empty($parts) || count($parts) != 4) {
            return false;
        }

        $id_obj = new \stdClass();
        $id_obj->original_id = $parts[0];
        $id_obj->model = $parts[1];
        $id_obj->id = $parts[2];
        $id_obj->guid = $parts[3];

        return $id_obj;
    }

    public function createGuid()
    {
        if (!function_exists("openssl_random_pseudo_bytes")) {
            $guid = uniqid(rand(0, 32766), true);
            return $guid;
        }

        if (($guid = openssl_random_pseudo_bytes("32")) === false) {
            $this->raiseError("openssl_random_pseudo_bytes() returned false!");
            return false;
        }

        $guid = bin2hex($guid);
        return $guid;
    }

    public function loadModel($object_name, $id = null, $guid = null)
    {
        switch($object_name) {
            case 'queue':
                $obj = new Models\QueueModel;
                break;
            case 'queueitem':
                $obj = new Models\QueueItemModel($id, $guid);
                break;
            case 'document':
                $obj = new Models\DocumentModel($id, $guid);
                break;
            case 'keyword':
                $obj = new Models\KeywordModel($id, $guid);
                break;
        }

        if (isset($obj)) {
            return $obj;
        }

        return false;
    }

    public function checkUpgrade()
    {
        global $db, $config;

        if (!($base_path = $config->getWebPath())) {
            $this->raiseError("ConfigController::getWebPath() returned false!");
            return false;
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        if (!$db->checkTableExists("TABLEPREFIXmeta")) {
            $this->raiseError(
                "You are missing meta table in database! "
                ."You may run <a href=\"{$base_path}/install\">"
                ."Installer</a> to fix this.",
                true
            );
            return true;
        }

        if ($db->getDatabaseSchemaVersion() < $db::SCHEMA_VERSION) {
            $this->raiseError(
                "The local schema version ({$db->getDatabaseSchemaVersion()}) is lower "
                ."than the programs schema version (". $db::SCHEMA_VERSION ."). "
                ."You may run <a href=\"{$base_path}/install\">Installer</a> "
                ."again to upgrade.",
                true
            );
            return true;
        }

        return false;
    }

    public function loadController($controller, $global_name)
    {
        if (empty($controller)) {
            $this->raiseError("\$controller must not be empty!", true);
            return false;
        }

        if (isset($GLOBALS[$global_name]) && !empty($GLOBALS[$global_name])) {
            return true;
        }

        $controller = 'MTLDA\\Controllers\\'.$controller.'Controller';

        if (!class_exists($controller, true)) {
            $this->raiseError("{$controller} class is not available!", true);
            return false;
        }

        try {
            $GLOBALS[$global_name] =& new $controller;
        } catch (Exception $e) {
            $this->raiseError("Failed to load {$controller_name}", true);
            return false;
        }

        return true;
    }

    public function getProcessUserId()
    {
        if ($uid = posix_getuid()) {
            return $uid;
        }

        return false;
    }

    public function getProcessGroupId()
    {
        if ($gid = posix_getgid()) {
            return $gid;
        }

        return false;
    }

    public function getProcessUserName()
    {
        if (!$uid = $this->getProcessUserId()) {
            return false;
        }

        if ($user = posix_getpwuid($uid)) {
            return $user['name'];
        }

        return false;

    }

    public function getProcessGroupName()
    {
        if (!$uid = $this->getProcessGroupId()) {
            return false;
        }

        if ($group = posix_getgrgid($uid)) {
            return $group['name'];
        }

        return false;
    }

    private function performActions()
    {
        global $mbus;

        if (!($messages = $mbus->getRequestMessages()) || empty($messages)) {
            return true;
        }

        if (!is_array($messages)) {
            $this->raiseError(get_class($mbus) .'::getRequestMessages() has not returned an array!');
            return false;
        }

        foreach ($messages as $message) {

            $message->setProcessingFlag();

            if (!$message->save()) {
                $mtlda->raiseError(get_class($message) .'::save() returned false!');
                return false;
            }

            if (!$this->handleMessage($message)) {
                $this->raiseError('handleMessage() returned false!');
                return false;
            }

            if (!$message->delete()) {
                $this->raiseError(get_class($message) .'::delete() returned false!');
                return false;
            }
        }

        return true;
    }

    private function handleMessage(&$message)
    {
        if (
            empty($message) ||
            get_class($message) != 'MTLDA\Models\MessageModel'
        ) {
            $this->raiseError(__METHOD__ .' requires a MessageModel reference as parameter!');
            return false;
        }

        if (!$message->isClientMessage()) {
            $this->raiseError(__METHOD__ .' can only handle client requests!');
            return false;
        }

        if (!($command = $message->getCommand())) {
            $this->raiseError(get_class($message) .'::getCommand() returned false!');
            return false;
        }

        if (!is_string($command)) {
            $this->raiseError(get_class($message) .'::getCommand() has not returned a string!');
            return false;
        }

        switch ($command) {
            default:
                $this->raiseError(__METHOD__ .', unknown command \"'. $command .'\" found!');
                return false;
                break;

            case 'sign-request':
                if (!$this->handleSignRequest($message)) {
                    $this->raiseError('handleSignRequest() returned false!');
                    return false;
                }
                break;
        }

        return true;
    }

    private function handleSignRequest(&$message)
    {
        global $mbus;

        if (
            empty($message) ||
            get_class($message) != 'MTLDA\Models\MessageModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a MessageModel reference as parameter!');
            return false;
        }

        if (!($body = $message->getBody())) {
            $this->raiseError(get_class($message) .'::getBody() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($message) .'::getBody() has not returned a string!');
            return false;
        }

        if (!($sessionid = $message->getSessionId())) {
            $this->raiseError(get_class($message) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($message) .'::getSessionId() has not returned a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-request', 'Preparing', '0%', $sessionid)) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!($sign_request = unserialize($body))) {
            $this->raiseError(__METHOD__ .', unable to unserialize message body!');
            return false;
        }

        if (!is_object($sign_request)) {
            $this->raiseError(__METHOD__ .', unserialize() has not returned an object!');
            return false;
        }

        if (
            !isset($sign_request->id) || empty($sign_request->id) ||
            !isset($sign_request->guid) || empty($sign_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', sign-request is incomplete!');
            return false;
        }

        if (!$this->isValidId($sign_request->id)) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if (!$this->isValidGuidSyntax($sign_request->guid)) {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        try {
            $document = new Models\DocumentModel(
                $sign_request->id,
                $sign_request->guid
            );
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .", unable to load DocumentModel!");
            return false;
        }

        if (!$this->signDocument($document)) {
            $this->raiseError(__CLASS__ .'::signDocument() returned false!');
            return false;
        }

        return true;
    }

    private function signDocument(&$document)
    {
        if (get_class($document) != "MTLDA\Models\DocumentModel") {
            $this->raiseError(__METHOD__ .', can only work with DocumentModels!');
            return false;
        }

        if ($document->document_signed_copy == 'Y') {
            $this->raiseError(__METHOD__ .", will not resign an already signed document!");
            return false;
        }

        try {
            $archive = new Controllers\ArchiveController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load ArchiveController!");
            return false;
        }

        if (!$archive) {
            $this->raiseError("Unable to load ArchiveController!");
            return false;
        }

        if (!$archive->sign($document)) {
            $this->raiseError("ArchiveController::sign() returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
