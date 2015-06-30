<?php

namespace MTLDA\Controllers;

class HttpRouterController
{
    public function parse($uri)
    {
        global $config;

        $query = new stdClass();
        $query->uri = $uri;

        // just to check if someone may fools us.
        if (substr_count($uri, '/') > 10) {
            print "Error - request looks strange - are you try to fooling us?";
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

        $parts = explode('/', $uri);

        if (!is_array($parts) || empty($parts) || count($parts) < 1) {
            print "Error - unable to parse request URI - nothing to be found.";
            exit(1);
        }

        $query->view = $parts[0];

        // no more information in URI, then we are done
        if (count($parts) == 1) {
            return $query;
        }

        $query->params = array();

        for ($i = 1; $i < count($parts); $i++) {
            array_push($query->params, $parts[$i]);
        }
        
        return $query;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
