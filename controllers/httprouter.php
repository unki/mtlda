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

class HttpRouterController
{
    private $query;

    public function __construct()
    {
        global $mtlda, $config;

        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            $mtlda->raiseError("Error - \$_SERVER['REQUEST_URI'] is not set!");
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'];

        $this->query = new \stdClass();
        $this->query->uri = $uri;

        // just to check if someone may fools us.
        if (substr_count($uri, '/') > 10) {
            $mtlda->raiseError("Request looks strange - are you try to fooling us?");
            exit(1);
        }

        if ($webpath = $config->getWebPath()) {
            $uri = str_replace($webpath, "", $uri);
        }

        // remove leading slashes if any
        $uri = ltrim($uri, '/');

        // explode string into an array
        $parts = explode('/', $uri);

        if (!is_array($parts) || empty($parts) || count($parts) < 1) {
            $mtlda->raiseError("Unable to parse request URI - nothing to be found.");
            exit(1);
        }

        // remove empty array elements
        $parts = array_filter($parts);

        /* for requests to the root page (config item base_web_path), load MainView */
        if (
            !isset($parts[0]) &&
            empty($uri) &&
            rtrim($_SERVER['REQUEST_URI'], '/') == $config->getWebPath()
        ) {
            $this->query->view = "main";
        } else {
            $this->query->view = $parts[0];
        }

        if (isset($parts[1]) && $this->isValidAction($parts[1])) {
            $this->query->mode = $parts[1];
        }

        $this->query->params = array();

        /* register further _GET parameters */
        if (isset($_GET) && is_array($_GET) && !empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $this->query->params[$key] = htmlentities($value, ENT_QUOTES);
            }
        }

        /* register further _POST parameters */
        if (isset($_POST) && is_array($_POST) && !empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $this->query->params[$key] = htmlentities($value, ENT_QUOTES);
            }
        }

        // no more information in URI, then we are done
        if (count($parts) <= 1) {
            return $this->query;
        }

        for ($i = 1; $i < count($parts); $i++) {
            array_push($this->query->params, $parts[$i]);
        }

        if (isset($this->query->mode) && $this->query->mode == 'rpc.html') {
            if (!isset($_POST['type']) || !isset($_POST['action'])) {
                return false;
            }
            if (!is_string($_POST['type']) || !is_string($_POST['action'])) {
                return false;
            }
            if ($_POST['type'] != "rpc" && $this->isValidRpcAction($_POST['action'])) {
                return false;
            }
            $this->query->call_type = "rpc";
            $this->query->action = $_POST['action'];
            return $this->query;
        /* /preview/{id} */
        } elseif ($this->query->view == "preview") {
            $this->query->call_type = "preview";
            return $this->query;
        } elseif ($this->query->view == "document") {
            $this->query->call_type = "document";
            return $this->query;
        /* queue-xxx.html ... */
        } elseif (preg_match('/(.*)-([0-9]+)/', $this->query->view)) {
            preg_match('/.*\/(.*)-([0-9]+)/', $this->query->view, $parts);

            if (!$this->isValidAction($parts[1])) {
                $mtlda->raiseError('Invalid action: '. $parts[1]);
            }
            if (!$mtlda->isValidId($parts[2])) {
                $mtlda->raiseError('Invalid id: '. $parts[2]);
            }

            $this->action = $parts[1];
            $this->id = $parts[2];
        /* main.html, ... */
        } elseif (preg_match('/.*\/.*\.html$/', $this->query->view)) {
            preg_match('/.*\/(.*)\.html$/', $this->query->view, $parts);
            if (!$this->isValidAction($parts[1])) {
                $mtlda->raiseError('Invalid action: '. $parts[1]);
            }

            $this->action = $parts[1];
        }

        $this->query->call_type = "common";

        return true;
    }

    public function getQuery()
    {
        if (!isset($this->query) || empty($this->query) || !is_object($this->query)) {
            return false;
        }

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

    private function isValidAction($action)
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
                'delete',
                'archive',
                'find-prev-next',
                'get-content',

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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
