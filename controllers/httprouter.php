<?php

namespace MTLDA\Controllers;

use stdClass;

class HttpRouterController
{
    private $call_type;
    private $page_name;
    private $action;

    public function parse($uri)
    {
        global $mtlda, $config;

        $query = new stdClass();
        $query->uri = $uri;

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
            $query->view = "main";
        } else {
            $query->view = $parts[0];
        }
        $query->params = array();

        // no more information in URI, then we are done
        if (count($parts) <= 1) {
            return $query;
        }

        for ($i = 1; $i < count($parts); $i++) {
            array_push($query->params, $parts[$i]);
        }

        if ($this->page_name == 'RPC Call') {
            if (!isset($_POST['type']) || !isset($_POST['action'])) {
                return false;
            }
            if (!is_string($_POST['type']) || !isset($_POST['action'])) {
                return false;
            }
            if ($_POST['type'] != "rpc") {
                return false;
            }
            $this->call_type = "rpc";
            $this->action = $_POST['action'];
            return true;
         /* queue-xxx.html ... */
        } elseif (preg_match('/(.*)-([0-9]+)/', $query->view)) {
            preg_match('/.*\/(.*)-([0-9]+)/', $query->view, $parts);

            if (!$this->is_valid_action($parts[1])) {
                $ms->throwError('Invalid action: '. $parts[1]);
            }
            if (!$this->is_valid_id($parts[2])) {
                $ms->throwError('Invalid id: '. $parts[2]);
            }

            $this->action = $parts[1];
            $this->id = $parts[2];
        /* main.html, ... */
        } elseif (preg_match('/.*\/.*\.html$/', $query->view)) {
            preg_match('/.*\/(.*)\.html$/', $query->view, $parts);
            if (!$this->is_valid_action($parts[1])) {
                $ms->throwError('Invalid action: '. $parts[1]);
            }

            $this->action = $parts[1];
        }
        /* register further _GET parameters */
        if (isset($_GET) && is_array($_GET) && !empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $this->$key = htmlentities($value, ENT_QUOTES);
            }
        }

        $this->call_type = "common";
        return $query;
    }

    /**
     * return true if current request is a RPC call
     *
     * @return bool
     */
    public function isRpcCall()
    {
        if ($this->call_type == "rpc") {
            return true;
        }

        return false;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
