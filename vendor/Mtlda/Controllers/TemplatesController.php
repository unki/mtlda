<?php

/**
 * This file is part of Mtlda.
 *
 * Mtlda, a web-based document archive.
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

namespace Mtlda\Controllers;

class TemplatesController extends \Thallium\Controllers\TemplatesController
{
    public function __construct()
    {
        global $config;

        try {
            parent::__construct();
        } catch (\Exception $e) {
            $this->raiseError(get_class($parent) .'::__construct() failed!', true, $e);
            return false;
        }

        $this->smarty->addPluginsDir(APP_BASE .'/vendor/Mtlda/SmartyPlugins');

        if (!($base_path = $config->getWebPath())) {
            $this->raiseError(get_class($config) .'getWebPath() returned false!', true);
            return false;
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        $this->registerPlugin("function", "get_menu_state", array(&$this, "getMenuState"), false);

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

    public function getMenuState($params, &$smarty)
    {
        global $query;

        if (!array_key_exists('page', $params)) {
            $this->raiseError("getMenuState: missing 'page' parameter", E_USER_WARNING);
            $repeat = false;
            return false;
        }

        if (!isset($query->view)) {
            return null;
        }

        if ($params['page'] == $query->view) {
            return "active";
        }

        return null;
    }

    public function getUrl($params, &$smarty)
    {
        if (($url = parent::getUrl($params, $smarty)) === false) {
            $this->raiseError(get_class($parent) .'::getUrl() returned false!');
            return false;
        }

        if (!array_key_exists('number', $params) &&
            !array_key_exists('items_per_page', $params)) {
            return $url;
        }

        if (array_key_exists('number', $params)) {
            $url.= "list-{$params['number']}.html";
        }

        if (array_key_exists('items_per_page', $params)) {
            $url.= "?items-per-page=". $params['items_per_page'];
        }

        return $url;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
