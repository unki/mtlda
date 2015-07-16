<?php

namespace MTLDA\Controllers;

use stdClass;

class HttpRouterController
{
    private $query;

    public function parse($uri)
    {
        global $mtlda, $config;

        $this->query = new stdClass();
        $this->query->uri = $uri;

        // just to check if someone may fools us.
        if (substr_count($uri, '/') > 10) {
            $mtlda->raiseError("Request looks strange - are you try to fooling us?");
            exit(1);
        }

        if (
                isset($config['app']) &&
                isset($config['app']['base_web_path']) &&
                !empty($config['app']['base_web_path'])) {
            $uri = str_replace($config['app']['base_web_path'], "", $uri);
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

        /* for requests to the root page ($config['app']['base_web_path']), load MainView */
        if (
            !isset($parts[0]) &&
            empty($uri) &&
            rtrim($_SERVER['REQUEST_URI'], '/') == $config['app']['base_web_path']
        ) {
            $this->query->view = "main";
        } else {
            $this->query->view = $parts[0];
        }

        if (isset($parts[1]) && $this->isValidAction($parts[1])) {
            $this->query->mode = $parts[1];
        }

        $this->query->params = array();

        // no more information in URI, then we are done
        if (count($parts) <= 1) {
            return $this->query;
        }

        /* register further _GET parameters */
        if (isset($_GET) && is_array($_GET) && !empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $this->query->params[$key] = htmlentities($value, ENT_QUOTES);
            }
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
                'toggle',
                'clone',
                'alter-position',
                'get-content',
                'get-sub-menu',
                'set-host-profile',
                'get-host-state',
                'idle',
                );

        if (in_array($action, $valid_actions)) {
            return true;
        }

        return false;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
