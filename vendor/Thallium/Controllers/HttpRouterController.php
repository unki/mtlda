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

class HttpRouterController extends DefaultController
{
    protected $query;
    protected $query_parts;

    public function __construct()
    {
        global $thallium, $config;

        $this->query = new \stdClass();

        // check HTTP request method
        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            $this->raiseError("Error - \$_SERVER['REQUEST_URI'] is not set!");
            return false;
        }

        if (!isset($_SERVER['REQUEST_METHOD']) || empty($_SERVER['REQUEST_METHOD'])) {
            $this->raiseError("\$_SERVER['REQUEST_METHOD'] is not set!");
            return false;
        }

        if (!$this->isValidRequestMethod($_SERVER['REQUEST_METHOD'])) {
            $this->raiseError("unspported request method {$_SERVER['REQUEST_METHOD']}");
            return false;
        }

        $this->query->method = $_SERVER['REQUEST_METHOD'];

        // check HTTP request URI
        $uri = $_SERVER['REQUEST_URI'];

        $this->query->uri = $uri;

        // just to check if someone may fools us.
        if (substr_count($uri, '/') > 10) {
            $this->raiseError("Request looks strange - are you try to fooling us?");
            exit(1);
        }

        if (!($webpath = $config->getWebPath())) {
            $this->raiseErrro("ConfigController::getWebPath() returned false!", true);
            exit(1);
        }

        // strip off our known base path (e.g. /thallium)
        if ($webpath != '/') {
            $uri = str_replace($webpath, "", $uri);
        }

        // remove leading slashes if any
        $uri = ltrim($uri, '/');

        // explode string into an array
        $this->query_parts = explode('/', $uri);

        if (!is_array($this->query_parts) ||
            empty($this->query_parts) ||
            count($this->query_parts) < 1
        ) {
            $this->raiseError("Unable to parse request URI - nothing to be found.");
            exit(1);
        }

        // remove empty array elements
        $this->query_parts = array_filter($this->query_parts);

        /* for requests to the root page (config item base_web_path), select MainView */
        if (!isset($this->query_parts[0]) &&
            empty($uri) && (
                $_SERVER['REQUEST_URI'] == "/" ||
                rtrim($_SERVER['REQUEST_URI'], '/') == $config->getWebPath()
            )
        ) {
            $this->query->view = "main";
        /* select View according parsed request URI */
        } elseif (isset($this->query_parts[0]) && !empty($this->query_parts[0])) {
            $this->query->view = $this->query_parts[0];
        } else {
            $this->raiseError(
                "Something is wrong here. "
                ."Check if base_web_path is correctly defined in your configuration."
            );
            return false;
        }

        if (isset($this->query_parts[0]) && $this->isValidAction($this->query_parts[0])) {
            $this->query->mode = $this->query_parts[0];
        } elseif (isset($this->query_parts[1]) && $this->isValidAction($this->query_parts[1])) {
            $this->query->mode = $this->query_parts[1];
        }

        $this->query->params = array();

        /* register further _GET parameters */
        if (isset($_GET) && is_array($_GET) && !empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if (is_array($value)) {
                    array_walk($value, function (&$item_value) {
                        return htmlentities($item_value, ENT_QUOTES);
                    });
                    continue;
                }
                $this->query->params[$key] = htmlentities($value, ENT_QUOTES);
            }
        }

        /* register further _POST parameters */
        if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
            foreach ($_POST as $key => $value) {
                if (is_array($value)) {
                    array_walk($value, function (&$item_value) {
                        return htmlentities($item_value, ENT_QUOTES);
                    });
                    continue;
                }
                $this->query->params[$key] = htmlentities($value, ENT_QUOTES);
            }
        }

        for ($i = 1; $i < count($this->query_parts); $i++) {
            array_push($this->query->params, $this->query_parts[$i]);
        }
    }

    public function select()
    {
        //
        // RPC
        //
        if (/* common RPC calls */
            (isset($this->query->mode) && $this->query->mode == 'rpc.html') ||
            /* object update RPC calls */
            ($this->query->method == 'POST' && $this->isValidUpdateObject($this->query->view))
        ) {
            if (!isset($_POST['type']) || !isset($_POST['action'])) {
                $this->raiseError("Incomplete RPC request!");
                return false;
            }
            if (!is_string($_POST['type']) || !is_string($_POST['action'])) {
                $this->raiseError("Invalid RPC request!");
                return false;
            }
            if ($_POST['type'] != "rpc" && $this->isValidRpcAction($_POST['action'])) {
                $this->raiseError("Invalid RPC action!");
                return false;
            }
            $this->query->call_type = "rpc";
            $this->query->action = $_POST['action'];
            return $this->query;
        }

        // no more information in URI, then we are done
        if (count($this->query_parts) <= 1) {
            return $this->query;
        }

        //
        // Previews (.../preview/${id})
        //

        if ($this->query->view == "preview") {
            $this->query->call_type = "preview";
            return $this->query;

        //
        // Documents retrieval (.../show/${id})
        //
        } elseif ($this->query->view == "document") {
            $this->query->call_type = "document";
            return $this->query;
        }

        $this->query->call_type = "common";
        return $this->query;
    }

    /**
     * return true if current request is a RPC call
     *
     * @return bool
     */
    public function isRpcCall()
    {
        if (isset($this->query->call_type) && $this->query->call_type == "rpc") {
            return true;
        }

        return false;
    }

    public function isImageCall()
    {
        if (isset($this->query->call_type) && $this->query->call_type == "preview") {
            return true;
        }

        return false;
    }

    public function isDocumentCall()
    {
        if (isset($this->query->call_type) && $this->query->call_type == "document") {
            return true;
        }

        return false;
    }

    public function isUploadCall()
    {
        if (isset($this->query->method) &&
            $this->query->method == 'POST' &&
            isset($this->query->view) &&
            $this->query->view == 'upload'
        ) {
            return true;
        }

        return false;
    }

    protected function isValidAction($action)
    {
        $valid_actions = array(
                'overview',
                'login',
                'logout',
                'show',
                'list',
                'new',
                'edit',
                'about',
                'rpc.html',
                );

        if (in_array($action, $valid_actions)) {
            return true;
        }

        return false;
    }

    public function isValidRpcAction($action)
    {
        $valid_actions = array(
            'add',
            'update',
            'delete',
            'delete-document',
            'archive',
            'sign',
            'find-prev-next',
            'get-content',
            'get-keywords',
            'save-keywords',
            'save-description',
            'submit-messages',
            'retrieve-messages',
            'process-messages',

            /*'toggle',
            'clone',
            'alter-position',
            'get-sub-menu',
            'set-host-profile',
            'get-host-state',
            'idle',*/
        );

        if (in_array($action, $valid_actions)) {
            return true;
        }

        return false;
    }

    public function parseQueryParams()
    {
        if (!isset($this->query->params) || !isset($this->query->params[1])) {
            return array('id' => null, 'hash' => 'null');
        }

        $matches = array();

        $id = $this->query->params[1];

        if (preg_match("/^([0-9]+)\-([a-z0-9]+)$/", $id, $matches)) {
            $id = $matches[1];
            $hash = $matches[2];
            return array('id' => $id, 'hash' => $hash);

        }

        return array('id' => null, 'hash' => 'null');
    }

    public function redirectTo($page, $mode, $id)
    {
        global $config;

        $url = $config->getWebPath();

        if (isset($page) && !empty($page)) {
            $url.= '/'.$page;
        }

        if (isset($mode) && !empty($mode)) {
            $url.= '/'.$mode;
        }

        if (isset($id) && !empty($id)) {
            $url.= '/'.$id;
        }

        Header("Location: ". $url);
        return true;
    }

    protected function isValidRequestMethod($method)
    {
        $valid_methods = array(
            'GET',
            'POST',
        );

        if (in_array($method, $valid_methods)) {
            return true;
        }

        return false;
    }

    protected function isValidUpdateObject($update_object)
    {
        global $thallium;

        if (($models = $thallium->getRegisteredModels()) === false) {
            $this->raiseError(get_class($thallium) .'::getRegisteredModels() returned false!');
            return false;
        }

        $valid_update_objects = array_keys($models);

        if (in_array($update_object, $valid_update_objects)) {
            return true;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
