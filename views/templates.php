<?php

namespace MTLDA\Views ;

use Smarty;

class Templates extends Smarty
{
    public function __construct()
    {
        global $config;

        parent::__construct();

        $this->template_dir = BASE_PATH .'/views/templates';
        $this->compile_dir  = BASE_PATH .'/cache/templates_c';
        $this->config_dir   = BASE_PATH .'/cache/smarty_config';
        $this->cache_dir    = BASE_PATH .'/cache/smarty_cache';

        if (!is_writeable($this->compile_dir)) {
            print "Error - Smarty compile directory ". $this->compile_dir ." is not writeable
                for the current user (". $this->getuid() .").<br />\n";
            print "Please check that permissions are set correctly to this directory.<br />\n";
            exit(1);
        }

        if (isset($config['app']) && isset($config['app']['page_title'])) {
            $this->assign('page_title', $config['app']['page_title']);
        }
    }

    public function getuid()
    {
        if ($uid = posix_getuid()) {
            if ($user = posix_getpwuid($uid)) {
                return $user['name'];
            }
        }

        return 'n/a';

    } // getuid()
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
