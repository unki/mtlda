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

namespace Mtlda\Views ;

use Smarty;

abstract class Templates extends Smarty
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
            'upload',
            'truncate',
            );
    public $default_mode = "list";

    public function __construct()
    {
        global $mtlda, $config;

        parent::__construct();

        // disable template caching during development
        $this->setCaching(Smarty::CACHING_OFF);
        $this->force_compile = true;
        $this->caching = false;

        $this->template_dir = MTLDA_BASE.'/vendor/Mtlda/Views/templates';
        $this->compile_dir  = MTLDA_BASE.'/cache/templates_c';
        $this->config_dir   = MTLDA_BASE.'/cache/smarty_config';
        $this->cache_dir    = MTLDA_BASE.'/cache/smarty_cache';

        if (!file_exists($this->compile_dir) && !is_writeable(MTLDA_BASE .'/cache')) {
            $mtlda->raiseError(
                "Cache directory ". MTLDA_BASE .'/cache' ." is not writeable"
                ."for user (". $this->getuid() .").<br />\n"
                ."Please check that permissions are set correctly to this directory.<br />\n"
            );
        }

        if (!file_exists($this->compile_dir) && !mkdir($this->compile_dir, 0700)) {
            $mtlda->raiseError("Failed to create directory ". $this->compile_dir);
            return false;
        }

        if (!is_writeable($this->compile_dir)) {
            $mtlda->raiseError(
                "Error - Smarty compile directory ". $this->compile_dir ." is not writeable
                for the current user (". $this->getuid() .").<br />\n
                Please check that permissions are set correctly to this directory.<br />\n"
            );
            return false;
        }

        $this->setTemplateDir($this->template_dir);
        $this->setCompileDir($this->compile_dir);
        $this->setConfigDir($this->config_dir);
        $this->setCacheDir($this->cache_dir);

        if ($page_title = $config->getPageTitle()) {
            $this->assign('page_title', $page_title);
        }

        if (!($base_path = $config->getWebPath())) {
            $mtlda->raiseError("Web path is missing!");
            return false;
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        $this->registerPlugin("function", "get_url", array(&$this, "getUrl"), false);
        $this->registerPlugin("function", "get_menu_state", array(&$this, "getMenuState"), false);
        $this->registerPlugin(
            "function",
            "get_humanreadable_filesize",
            array(&$this, "getHumanReadableFilesize"),
            false
        );

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
            $mtlda->raiseError("Web path is missing!");
            return false;
        }

        if ($url == '/') {
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

        if (array_key_exists('file', $params) && !empty($params['file'])) {
            $url.= '/'. $params['file'];
        }

        return $url;

    } // getUrl()

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
        try {
            $result =  parent::fetch(
                $template,
                $cache_id,
                $compile_id,
                $parent,
                $display,
                $merge_tpl_vars,
                $no_output_filter
            );
        } catch (\SmartyException $e) {
            $mtlda->raiseError("Smarty throwed an exception! ". $e->getMessage());
            return false;
        }

        return $result;

    } // fetch()

    public function getMenuState($params, &$smarty)
    {
        global $mtlda, $query;

        if (!array_key_exists('page', $params)) {
            $mtlda->raiseError("getMenuState: missing 'page' parameter", E_USER_WARNING);
            $repeat = false;
            return false;
        }

        if ($params['page'] == $query->view) {
            return "active";
        }

        return null;
    }

    public function getHumanReadableFilesize($params, &$smarty)
    {
        global $mtlda, $query;

        if (!array_key_exists('size', $params)) {
            $mtlda->raiseError("getMenuState: missing 'size' parameter", E_USER_WARNING);
            $repeat = false;
            return false;
        }

        if ($params['size'] < 1048576) {
            return round($params['size']/1024, 2) ."KB";
        }

        return round($params['size']/1048576, 2) ."MB";
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
