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
            'show',
            'edit',
            'delete',
            'add',
            'sign',
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

        $this->template_dir = MTLDA_BASE.'/views/templates';
        $this->compile_dir  = MTLDA_BASE.'/cache/templates_c';
        $this->config_dir   = MTLDA_BASE.'/cache/smarty_config';
        $this->cache_dir    = MTLDA_BASE.'/cache/smarty_cache';

        if (!file_exists($this->compile_dir) && !is_writeable(MTLDA_BASE .'/cache')) {
            $mtlda->raiseError(
                "Cache directory ". $MTLDA_BASE .'/cache' ." is not writeable"
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

        if ($page_title = $config->getPageTitle()) {
            $this->assign('page_title', $page_title);
        }
        if (!($base_path = $config->getWebPath())) {
            $base_path = '';
        }

        $this->registerPlugin("function", "get_url", array(&$this, "getUrl"), false);
        $this->registerFilter("pre", array(&$this, "addTemplateName"));

        $this->assign('web_path', $base_path);
        $this->assign('image_arrow_left', $base_path .'/resources/images/arrow-circle-left-4x.png');
        $this->assign('image_arrow_right', $base_path .'/resources/images/arrow-circle-right-4x.png');

        $this->assign('document_top_left', $base_path .'/resources/images/top_left.png');
        $this->assign('document_top_center', $base_path .'/resources/images/top_center.png');
        $this->assign('document_top_right', $base_path .'/resources/images/top_right.png');
        $this->assign('document_middle_left', $base_path .'/resources/images/middle_left.png');
        $this->assign('document_middle_center', $base_path .'/resources/images/middle_center.png');
        $this->assign('document_middle_right', $base_path .'/resources/images/middle_right.png');
        $this->assign('document_bottom_left', $base_path .'/resources/images/bottom_left.png');
        $this->assign('document_bottom_center', $base_path .'/resources/images/bottom_center.png');
        $this->assign('document_bottom_right', $base_path .'/resources/images/bottom_right.png');
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
            return false;
        }

        if (array_key_exists('mode', $params) && !in_array($params['mode'], $this->supported_modes)) {
            $mtlda->raiseError("getUrl: value of parameter 'mode' ({$params['mode']}) isn't supported", E_USER_WARNING);
            $repeat = false;
            return false;
        }

        if (!($url = $config->getWebPath())) {
            $url = "";
        }

        $url.= "/";
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
        global $mtlda, $query, $router;

        if (isset($query->params)) {
            $params = $query->params;
        }

        if ((!isset($params) || empty($params)) && $this->default_mode == "list") {
            $mode = "list";
        } elseif (isset($params) && !empty($params)) {
            if ($params[0] == "list") {
                $mode = "list";
            } elseif ($params[0] == "edit") {
                $mode = "edit";
            } elseif ($params[0] == "show") {
                $mode = "show";
            }
        } elseif ($this->default_mode == "show") {
            $mode = "show";
        }

        if ($mode == "list" && $this->templateExists($this->class_name ."_list.tpl")) {

            return $this->showList();

        } elseif ($mode == "edit" && $this->templateExists($this->class_name ."_edit.tpl")) {

            if (!$item = $router->parseQueryParams()) {
                $mtlda->raiseError("HttpRouterController::parseQueryParams() returned false!");
                return false;
            }
            if (
                empty($item) ||
                !is_array($item) ||
                !isset($item['id']) ||
                empty($item['id']) ||
                !isset($item['hash']) ||
                empty($item['hash']) ||
                !$mtlda->isValidId($item['id']) ||
                !$mtlda->isValidGuidSyntax($item['hash'])
            ) {
                $mtlda->raiseError("HttpRouterController::parseQueryParams() was unable to parse query parameters!");
                return false;
            }
            return $this->showEdit($item['id'], $item['hash']);

        } elseif ($mode == "show" && $this->templateExists($this->class_name ."_show.tpl")) {

            if (!$item = $router->parseQueryParams()) {
                $mtlda->raiseError("HttpRouterController::parseQueryParams() returned false!");
            }
            if (
                empty($item) ||
                !is_array($item) ||
                !isset($item['id']) ||
                empty($item['id']) ||
                !isset($item['hash']) ||
                empty($item['hash']) ||
                !$mtlda->isValidId($item['id']) ||
                !$mtlda->isValidGuidSyntax($item['hash'])
            ) {
                $mtlda->raiseError("HttpRouterController::parseQueryParams() was unable to parse query parameters!");
                return false;
            }
            return $this->showItem($item['id'], $item['hash']);

        } elseif ($this->templateExists($this->class_name .".tpl")) {

            return $this->fetch($this->class_name .".tpl");

        }

        $mtlda->raiseError("All methods utilized but still don't know what to show!");
        return false;
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

    public function showItem($id, $hash)
    {
        return $this->fetch($this->class_name ."_show.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
