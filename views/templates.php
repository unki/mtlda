<?php

namespace MTLDA\Views ;

use Smarty;

class Templates extends Smarty
{
    private $template_dir;
    private $compile_dir;
    private $config_dir;
    private $cache_dir;

    public function __construct()
    {
        global $config;

        parent::__construct();

        // disable template caching during development
        $this->setCaching(Smarty::CACHING_OFF);
        $this->force_compile = true;
        $this->caching = false;

        $this->template_dir = BASE_PATH.'/views/templates';
        $this->compile_dir  = BASE_PATH.'/cache/templates_c';
        $this->config_dir   = BASE_PATH.'/cache/smarty_config';
        $this->cache_dir    = BASE_PATH.'/cache/smarty_cache';

        if (!file_exists($this->compile_dir) && !is_writeable(BASE_PATH .'/cache')) {
            print "Error - cache directory ". $BASE_PATH .'/cache' ." is not writeable
                for the current user (". $this->getuid() .").<br />\n";
            print "Please check that permissions are set correctly to this directory.<br />\n";
            exit(1);
        }

        if (!file_exists($this->compile_dir) && !mkdir($this->compile_dir, 0700)) {
            print "Failed to create directory ". $this->compile_dir;
            exit(1);
        }

        if (!is_writeable($this->compile_dir)) {
            print "Error - Smarty compile directory ". $this->compile_dir ." is not writeable
                for the current user (". $this->getuid() .").<br />\n";
            print "Please check that permissions are set correctly to this directory.<br />\n";
            exit(1);
        }

        if (isset($config['app']) && isset($config['app']['page_title'])) {
            $this->assign('page_title', $config['app']['page_title']);
        }
        if (isset($config['app']) && isset($config['app']['base_web_path'])) {
            $this->assign('web_path', $config['app']['base_web_path']);
        }

        $this->registerPlugin("function", "get_url", array(&$this, "getUrl"), false);
        $this->registerFilter("pre", array(&$this, "addTemplateName"));
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

    public function getUrl($params, &$smarty)
    {
        global $config;

        if (!array_key_exists('page', $params)) {
            trigger_error("getUrl: missing 'page' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        if (isset($config['app']) && isset($config['app']['base_web_path'])) {
            return $config['app']['base_web_path'] ."/". $params['page'];
        }

        return '/'.$params['page'];

    } // get_url()

    public function addTemplateName($tpl_source, $template)
    {
        return "<!-- BEGIN ".
            $template->template_resource
            ." -->\n".
            $tpl_source
            ."<!-- END ".
            $template->template_resource
            ." -->";

    }  // addTemplateName()

    public function fetch($tpl_name)
    {
        global $mtlda;

        if (!file_exists($this->template_dir ."/". $tpl_name)) {
            $mtlda->raiseError("Unable to find ". $tpl_name ." in ". $this->template_dir);
        }

        parent::fetch($tpl_name);

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
