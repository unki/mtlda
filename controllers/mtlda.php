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

class MTLDA
{
    private $verbosity_level = LOG_WARNING;

    public function __construct($mode = null)
    {
        $GLOBALS['mtlda'] =& $this;

        $GLOBALS['config'] =& new ConfigController;
        $req = new RequirementsController;
        $GLOBALS['db'] =& new DatabaseController;

        if (!$req->check()) {
            $this->raiseError("Error - not all MTLDA requirements are met. Please check!");
            exit(1);
        }

        if (isset($mode) and $mode == "queue_only") {
            $incoming =& new IncomingController;
            $incoming->handleQueue();
            exit(0);
        }

    }

    public function startup()
    {
        $GLOBALS['router'] =& new HttpRouterController;

        global $config, $db, $router;

        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            $this->raiseError("Error - \$_SERVER['REQUEST_URI'] is not set!");
            exit(1);
        }

        $GLOBALS['query'] = $router->parse($_SERVER['REQUEST_URI']);
        global $query;

        if (!isset($query->view)) {
            $this->raiseError("Error - parsing request URI hasn't unveiled what to view!");
            exit(1);
        }

        $GLOBALS['views'] =& new ViewsController;
        global $views;

        if ($router->isRpcCall()) {
            $this->rpcHandler();
            return;
        }

        if ($router->isImageCall()) {
            $this->imageHandler();
            return;
        }

        if ($router->isDocumentCall()) {
            $this->documentHandler();
            return;
        }

        if (!$page_name = $views->getViewName($query->view)) {
            $this->raiseError("Unable to find a view for ". $query->view);
        }

        $page = $views->load($page_name);
        print $page;
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

    public function write($text, $loglevel = LOG_INFO, $override_output = null, $no_newline = null)
    {
        if (isset($this->config->logging)) {
            $logtype = $this->config->logging;
        }

        if (!isset($this->config->logging)) {
            $logtype = 'display';
        }

        if (isset($override_output)) {
            $logtype = $override_output;
        }

        if ($this->getVerbosity() < $loglevel) {
            return true;
        }

        switch($logtype) {
            default:
            case 'display':
                print $text;
                if (!$this->isCmdline()) {
                    print "<br />";
                } elseif (!isset($no_newline)) {
                    print "\n";
                }
                break;
            case 'errorlog':
                error_log($text);
                break;
            case 'logfile':
                error_log($text, 3, $this->config->log_file);
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
        if (!in_array($level, array(0 => LOG_INFO, 1 => LOG_WARNING, 2 => LOG_DEBUG))) {
            $this->raiseError("Unknown verbosity level ". $level);
        }

        $this->verbosity_level = $level;

    } // setVerbosity()

    public function getVerbosity()
    {
        return $this->verbosity_level;

    } // getVerbosity()

    private function rpcHandler()
    {
        $rpc = new RpcController;
        $rpc->perform();
    }

    private function imageHandler()
    {
        $image = new ImageController;
        $image->perform();
    }

    private function documentHandler()
    {
        $document = new DocumentController;
        $document->perform();
    }

    public function isValidId($id)
    {
        $id = (int) $id;

        if (is_numeric($id)) {
            return true;
        }

        return false;
    }

    public function isValidModel($model)
    {
        $valid_models = array(
            'queueitem',
            'archiveitem',
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
        if (function_exists("openssl_random_pseudo_bytes")) {

            if (($guid = openssl_random_pseudo_bytes("32")) === false) {
                $this->raiseError("openssl_random_pseudo_bytes() returned false!");
                return false;
            }

            $guid = bin2hex($guid);

        } else {

            $guid = uniqid(rand(0, 32766), true);

        }

        return $guid;
    }

    public function loadModel($object_name, $id = null, $guid = null)
    {
        switch($object_name) {
            case 'queueitem':
                $obj = new Models\QueueItemModel($id, $guid);
                break;
            case 'archiveitem':
                $obj = new Models\ArchiveItemModel($id, $guid);
                break;
        }

        if (isset($obj)) {
            return $obj;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
