<?php

class MTLDA_Config_Controller {

    var $config_path = BASE_PATH ."/config";
    var $config_file = "config.php";
    var $config_fqpn;

    public function __construct() {

        global $config;

        if(!file_exists($this->config_path)) {
            print "Error - configuration directory ". $this->config_path ." does not exist!";
            exit(1);
        }

        if(!is_executable($this->config_path)) {
            print "Error - unable to enter configuration directory ". $this->config_path ." - please check permissions!";
            exit(1);
        }

        $this->config_fqpn = $this->config_path ."/config.php";

        if(!file_exists($this->config_fqpn)) {
            print "Error - configuration file ". $this->config_fqpn ." does not exist!";  
            exit(1);
        }

        if(!is_readable($this->config_fqpn)) {
            print "Error - unable to read configuration file ". $this->config_fqpn ." - please check permissions!";
            exit(1);
        }

        if(($config_ary = parse_ini_file($this->config_fqpn, true)) === FALSE) {
            print "Error - parse_ini_file() function failed on ". $this->config_fqpn ." - please check syntax!";
            exit(1);
        }

        if(!is_array($config_ary) || empty($config_ary)) {
            print "Error - invalid configuration retrieved from ". $this->config_fqpn ." - please check syntax!";
            exit(1);
        }

        $config = $config_ary;
    }

}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
