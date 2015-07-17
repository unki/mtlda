<?php

namespace MTLDA\Controllers;

class ConfigController
{
    public $config_path;
    public $config_file;
    public $config_fqpn;

    public function __construct()
    {
        global $config;

        $this->config_path = BASE_PATH ."/config";
        $this->config_file = "config.ini";

        if (!file_exists($this->config_path)) {
            print "Error - configuration directory ". $this->config_path ." does not exist!";
            exit(1);
        }

        if (!is_executable($this->config_path)) {
            print "Error - unable to enter config directory ". $this->config_path ." - please check permissions!";
            exit(1);
        }

        $this->config_fqpn = $this->config_path ."/". $this->config_file;

        if (!file_exists($this->config_fqpn)) {
            print "Error - configuration file ". $this->config_fqpn ." does not exist!";
            exit(1);
        }

        if (!is_readable($this->config_fqpn)) {
            print "Error - unable to read configuration file ". $this->config_fqpn ." - please check permissions!";
            exit(1);
        }

        if (!function_exists("parse_ini_file")) {
            print "Error - this PHP installation does not provide required parse_ini_file() function!";
            exit(1);
        }

        if (($config_ary = parse_ini_file($this->config_fqpn, true)) === false) {
            print "Error - parse_ini_file() function failed on ". $this->config_fqpn ." - please check syntax!";
            exit(1);
        }

        if (!is_array($config_ary) || empty($config_ary)) {
            print "Error - invalid configuration retrieved from ". $this->config_fqpn ." - please check syntax!";
            exit(1);
        }

        // remove trailing slash from base_web_path if any
        if (
            isset($config_ary['app']) &&
            isset($config_ary['app']['base_web_path']) &&
            !empty($config_ary['app']['base_web_path'])) {
            $config_ary['app']['base_web_path'] = rtrim($config_ary['app']['base_web_path'], '/');
        }

        $config = $config_ary;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
