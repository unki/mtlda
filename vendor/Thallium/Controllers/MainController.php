<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015> <Andreas Unterkircher>
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

namespace Thallium\Controllers;

class MainController extends DefaultController
{
    const VERSION = "1.0";

    protected $verbosity_level = LOG_WARNING;
    protected $override_namespace_prefix;
    protected $registeredModels = array(
        'auditentry' => 'AuditEntryModel',
        'auditlog' => 'AuditLogModel',
        'jobmodel' => 'JobModel',
        'jobsmodel' => 'JobsModel',
        'messagebusmodel' => 'MessageBusModel',
        'messagemodel' => 'MessageModel',
    );

    public function __construct($mode = null)
    {
        $GLOBALS['thallium'] =& $this;

        $this->loadController("Config", "config");
        $this->loadController("Requirements", "requirements");

        global $requirements;

        if (!$requirements->check()) {
            $this->raiseError("Error - not all requirements are met. Please check!", true);
        }

        // no longer needed
        unset($requirements);

        $this->loadController("Audit", "audit");
        $this->loadController("Database", "db");

        if (!$this->isCmdline()) {
            $this->loadController("HttpRouter", "router");
            global $router;
            if (($GLOBALS['query'] = $router->select()) === false) {
                $this->raiseError(__METHOD__ .'(), HttpRouterController::select() returned false!');
                return false;
            }
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
        $this->loadController("Jobs", "jobs");
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
                $this->raiseError(__CLASS__ .'::rpcHandler() returned false!');
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

    public function setVerbosity($level)
    {
        /*if (!in_array($level, array(0 => LOG_INFO, 1 => LOG_WARNING, 2 => LOG_DEBUG))) {
            $this->raiseError("Unknown verbosity level ". $level);
        }

        $this->verbosity_level = $level;*/

    } // setVerbosity()

    protected function rpcHandler()
    {
        $this->loadController("Rpc", "rpc");
        global $rpc;

        ob_start();
        if (!$rpc->perform()) {
            $this->raiseError("RpcController::perform() returned false!");
            return false;
        }
        unset($rpc);

        $size = ob_get_length();
        header("Content-Length: $size");
        header('Connection: close');
        ob_end_flush();
        ob_flush();
        session_write_close();

        // invoke the MessageBus processor so pending tasks can
        // be handled. but suppress any output.
        if (!$this->performActions()) {
            $this->raiseError('performActions() returned false!');
            return false;
        }

        return true;
    }

    protected function uploadHandler()
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

    public function isValidModel($model_name)
    {
        if (!isset($model_name) ||
            empty($model_name) ||
            !is_string($model_name)
        ) {
            $this->raiseError(__METHOD__ .'(), \$model_name parameter is invalid!');
            return false;
        }

        if (!preg_match('/model$/i', $model_name)) {
            $nick = $model_name;
            $model = null;
        } else {
            $nick = null;
            $model = $model_name;
        }

        if ($this->isRegisteredModel($nick, $model)) {
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

    public function loadModel($model_name, $id = null, $guid = null)
    {
        if (!($prefix = $this->getNamespacePrefix())) {
            $this->raiseError(__METHOD__ .'(), failed to fetch namespace prefix!');
            return false;
        }

        if (!($known_models =  $this->getRegisteredModels())) {
            $this->raiseError(__METHOD__ .'(), getRegisteredModels returned false!');
            return false;
        }

        $nick = null;
        $name = null;

        if (in_array(strtolower($model_name), array_keys($known_models))) {
            $model = $known_models[$model_name];
        } elseif (in_array($model_name, $known_models)) {
            $model = $model_name;
        }

        $model = $prefix .'\\Models\\'. $model;

        try {
            $obj = new $model($id, $guid);
        } catch (\Exception $e) {
            $this->raiseError("Failed to load model {$object_name}! ". $e->getMessage());
            return false;
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

        if ($db->getApplicationDatabaseSchemaVersion() < $db->getApplicationSoftwareSchemaVersion() ||
            $db->getFrameworkDatabaseSchemaVersion() < $db->getFrameworkSoftwareSchemaVersion()
        ) {
            $this->raiseError(
                "A database schema version upgrade is pending.&nbsp;"
                ."You have to run <a href=\"{$base_path}/install\">Installer</a> "
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

        if (!($prefix = $this->getNamespacePrefix())) {
            $this->raiseError(__METHOD__ .'(), failed to fetch namespace prefix!');
            return false;
        }

        $controller = '\\'. $prefix .'\\Controllers\\'.$controller.'Controller';

        if (!class_exists($controller, true)) {
            $this->raiseError("{$controller} class is not available!", true);
            return false;
        }

        try {
            $GLOBALS[$global_name] =& new $controller;
        } catch (Exception $e) {
            $this->raiseError("Failed to load {$controller_name}! ". $e->getMessage(), true);
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

    protected function performActions()
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
                $this->raiseError(get_class($message) .'::save() returned false!');
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

    protected function handleMessage(&$message)
    {
        global $jobs;

        if (get_class($message) != 'Thallium\\Models\\MessageModel') {
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

        if (!($sessionid = $message->getSessionId())) {
            $this->raiseError(get_class($message) .'::getSessionId() returned false!');
            return false;
        }

        if (!($msg_guid = $message->getGuid()) || !$this->isValidGuidSyntax($msg_guid)) {
            $this->raiseError(get_class($message) .'::getGuid() has not returned a valid GUID!');
            return false;
        }

        if (!($job = $jobs->createJob($sessionid, $msg_guid))) {
            $this->raiseError(get_class($jobs) .'::createJob() returned false!');
            return false;
        }

        if (isset($job) && (empty($job) || !$this->isValidGuidSyntax($job))) {
            $this->raiseError(get_class($jobs) .'::createJob() has not returned a valid GUID!');
            return false;
        }

        if (!$jobs->setCurrentJob($job)) {
            $this->raiseError(get_class($jobs) .'::setCurrentJob() returned false!');
            return false;
        }

        if (!$jobs->setJobInProcessing($job)) {
            $this->raiseError(get_class($jobs) .'::setJobInProcessing() returned false!');
            return false;
        }

        return array(
            'command' => $command,
            'job' => $job
        );

        if (!$jobs->deleteJob($job)) {
            $this->raiseError(get_class($jobs) .'::deleteJob() returned false!');
            return false;
        }

        return true;
    }

    final public function getNamespacePrefix()
    {
        if (isset($this->override_namespace_prefix) &&
            !empty($this->override_namespace_prefix)
        ) {
            return $this->override_namespace_prefix;
        }

        $namespace = __NAMESPACE__;

        if (!strstr($namespace, '\\')) {
            return $namespace;
        }

        $namespace_parts = explode('\\', $namespace);

        if (!isset($namespace_parts) ||
            empty($namespace_parts) ||
            !is_array($namespace_parts) ||
            !isset($namespace_parts[0]) ||
            empty($namespace_parts[0]) ||
            !is_string($namespace_parts[0])
        ) {
            $this->raiseError('Failed to extract prefix from NAMESPACE constant!');
            return false;
        }

        return $namespace_parts[0];
    }

    final public function setNamespacePrefix($prefix)
    {
        if (!isset($prefix) || empty($prefix) || !is_string($prefix)) {
            $this->raiseError(__METHOD__ .', \$prefix parameter is invalid!');
            return false;
        }

        $this->override_namespace_prefix = $prefix;
        return true;
    }

    final public function getRegisteredModels()
    {
        if (!isset($this->registeredModels) ||
            empty($this->registeredModels) ||
            !is_array($this->registeredModels)
        ) {
            $this->raiseError(__METHOD__ .'(), registeredModels property is invalid!');
            return false;
        }

        return $this->registeredModels;
    }

    final public function registerModel($nick, $model)
    {
        if (!isset($this->registeredModels) ||
            empty($this->registeredModels) ||
            !is_array($this->registeredModels)
        ) {
            $this->raiseError(__METHOD__ .'(), registeredModels property is invalid!');
            return false;
        }

        if (!isset($nick) || empty($nick) || !is_string($nick)) {
            $this->raiseError(__METHOD__ .'(), \$nick parameter is invalid!');
            return false;
        }

        if (!isset($model) || empty($model) || !is_string($model)) {
            $this->raiseError(__METHOD__ .'(), \$model parameter is invalid!');
            return false;
        }

        if ($this->isRegisteredModel($nick, $model)) {
            return true;
        }

        $this->registeredModels[$nick] = $model;
        return true;
    }

    final public function isRegisteredModel($nick = null, $model = null)
    {
        if ((!isset($nick) || empty($nick) || !is_string($nick)) &&
            (!isset($model) || empty($model) || !is_string($nick))
        ) {
            $this->raiseError(__METHOD__ .'(), can not look for nothing!');
            return false;
        }

        if (($known_models = $this->getRegisteredModels()) === false) {
            $this->raiseError(__METHOD__ .'(), getRegisteredModels() returned false!');
            return false;
        }

        $result = false;

        if (isset($nick) && !empty($nick)) {
            if (in_array($nick, array_keys($known_models))) {
                $result = true;
            }
        }

        // not looking for $model? then we are done.
        if (!isset($model) || empty($model)) {
            return $result;
        }

        // looking for nick was ok, but does it also match $model?
        if ($result) {
            if ($known_models[$nick] == $model) {
                return true;
            } else {
                return false;
            }
        }

        if (!in_array($model, $known_models)) {
            return false;
        }

        return true;
    }

    public function getModelByNick($nick)
    {
        if (!isset($nick) || empty($nick) || !is_string($nick)) {
            $this->raiseError(__METHOD__ .'(), $nick parameter is invalid!');
            return false;
        }

        if (($known_models = $this->getRegisteredModels()) === false) {
            $this->raiseError(__METHOD__ .'(), getRegisteredModels() returned false!');
            return false;
        }

        if (!isset($known_models[$nick])) {
            return false;
        }

        return $known_models[$nick];
    }

    public function isBelowDirectory($dir, $topmost = null)
    {
        if (empty($dir)) {
            $this->raiseError("\$dir can not be empty!");
            return false;
        }

        if (empty($topmost)) {
            $topmost = APP_BASE;
        }

        $dir = strtolower(realpath($dir));
        $dir_top = strtolower(realpath($topmost));

        $dir_top_reg = preg_quote($dir_top, '/');

        // check if $dir is within $dir_top
        if (!preg_match('/^'. preg_quote($dir_top, '/') .'/', $dir)) {
            return false;
        }

        if ($dir == $dir_top) {
            return false;
        }

        $cnt_dir = count(explode('/', $dir));
        $cnt_dir_top = count(explode('/', $dir_top));

        if ($cnt_dir > $cnt_dir_top) {
            return true;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
