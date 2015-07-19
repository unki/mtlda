<?php

namespace MTLDA\Views ;

use Smarty;

class Templates extends Smarty
{
    public $template_dir;
    public $compile_dir;
    public $config_dir;
    public $cache_dir;
    public $supported_modes = array (
            'list',
            'edit',
            'delete',
            'add',
            );
    public $default_mode = "list";

    public function __construct()
    {
        global $mtlda, $config;

        parent::__construct();

        if (!isset($this->class_name)) {
            $mtlda->raiseError("Class has not defined property 'class_name'. Something is wrong with it");
        }

        // disable template caching during development
        $this->setCaching(Smarty::CACHING_OFF);
        $this->force_compile = true;
        $this->caching = false;

        $this->template_dir = BASE_PATH.'/views/templates';
        $this->compile_dir  = BASE_PATH.'/cache/templates_c';
        $this->config_dir   = BASE_PATH.'/cache/smarty_config';
        $this->cache_dir    = BASE_PATH.'/cache/smarty_cache';

        if (!file_exists($this->compile_dir) && !is_writeable(BASE_PATH .'/cache')) {
            $mtlda->raiseError(
                "Cache directory ". $BASE_PATH .'/cache' ." is not writeable"
                ."for user (". $this->getuid() .").<br />\n"
                ."Please check that permissions are set correctly to this directory.<br />\n"
            );
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
        $this->setTemplateDir($this->template_dir);
        $this->setCompileDir($this->compile_dir);
        $this->setConfigDir($this->config_dir);
        $this->setCacheDir($this->cache_dir);

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
        global $mtlda, $config;

        if (!array_key_exists('page', $params)) {
            $mtlda->raiseError("getUrl: missing 'page' parameter", E_USER_WARNING);
            $repeat = false;
            return;
        }

        if (array_key_exists('mode', $params) && !in_array($params['mode'], $this->supported_modes)) {
            $mtlda->raiseError("getUrl: value of parameter 'mode' ({$params['mode']}) isn't supported", E_USER_WARNING);
            $repeat = false;
            return;
        }

        if (
                isset($config['app']) &&
                isset($config['app']['base_web_path']) &&
                !empty($config['app']['base_web_path'])
           ) {
            $url = $config['app']['base_web_path'] ."/";
        } else {
            $url = "/";
        }

        $url.= $params['page'] ."/";

        if (isset($params['mode']) && !empty($params['mode'])) {
            $url.= $params['mode'] ."/";
        }

        if (array_key_exists('id', $params) && !empty($params['id'])) {
            $url.= $params['id'];
        }

        return $url;

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

    public function fetch(
        $template = null,
        $cache_id = null,
        $compile_id = null,
        $parent = null,
        $display = false,
        $merge_tpl_vars = true,
        $no_output_filter = false
    ) {
        global $mtlda;

        if (!file_exists($this->template_dir."/". $template)) {
            $mtlda->raiseError("Unable to locate ". $template ." in directory ". $this->template_dir);
        }

        // Now call parent method
        return parent::fetch(
            $template,
            $cache_id,
            $compile_id,
            $parent,
            $display,
            $merge_tpl_vars,
            $no_output_filter
        );

    } // fetch()

    public function show()
    {
        global $mtlda, $query;

        if (((
                        !isset($query->params) ||
                        empty($query->params)) &&
                    $this->default_mode == "list") ||
                (isset($query) &&
                 !empty($query->params) &&
                 $query->params[0] == "list")) {

            return $this->showList();

        } elseif (isset($query->params) && !empty($query->params) && $query->params[0] == "edit") {

            if (isset($query->params[1])) {

                $id = $query->params[1];
                if (preg_match("/^([0-9])\-([a-z0-9]+)$/", $id, $matches)) {

                    $id = $matches[1];
                    $hash = $matches[2];

                } else {

                    $hash = null;

                }
            } else {

                $id = null;
                $hash = null;

            }
            return $this->showEdit($id, $hash);

        } elseif ($this->default_mode == "show" && $this->templateExists($this->class_name .".tpl")) {

            return $this->fetch($this->class_name .".tpl");

        } else {

            $mtlda->raiseError("All methods utilized but still don't know what to show!");
        }
    }

    public function showList()
    {
        $this->registerPlugin("block", $this->class_name ."_list", array(&$this, $this->class_name ."List"));
        return $this->fetch($this->class_name ."_list.tpl");
    }

    public function showEdit($id)
    {
        $this->assign('item', $id);
        return $this->fetch($this->class_name ."_edit.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
