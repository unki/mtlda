<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017> <Andreas Unterkircher>
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

abstract class DefaultView extends \Thallium\Views\DefaultView
{
    protected $known_contents = array();

    public function __construct()
    {
        global $config, $tmpl;

        if ($config->isHttpUploadEnabled()) {
            $tmpl->assign('http_upload_is_enabled', true);
        }

        if ($config->isMailImportEnabled()) {
            $tmpl->assign('mail_import_is_enabled', true);
        }

        if ($config->isUserTriggersImportEnabled()) {
            $tmpl->assign('user_triggers_import_enabled', true);
        }

        parent::__construct();
        return;
    }

    protected static function isKnownMode($mode)
    {
        if (parent::isKnownMode($mode)) {
            return true;
        }

        if (preg_match('/^list-([0-9]+).html$/', $mode)) {
            return true;
        }

        return false;
    }

    public function show()
    {
        global $query;

        if (isset($query->params) &&
            !empty($query->params) &&
            is_array($query->params) && (
                isset($query->params[0]) &&
                ( $query->params[0] == 'list.html' || (
                    preg_match('/^list-([0-9]+).html$/', $query->params[0], $parts) &&
                    isset($parts) &&
                    !empty($parts) &&
                    is_array($parts) &&
                    isset($parts[1]) &&
                    is_numeric($parts[1])
                ))) ||
                (( !isset($query->params[0]) ||
                    empty($query->params[0])
                ) &&
                    isset($query->params['items-per-page']))
        ) {
            if (isset($query->params['items-per-page'])) {
                $items_per_page = $query->params['items-per-page'];
            } else {
                $items_per_page = null;
            }
            if (isset($parts[1])) {
                $mode = $parts[1];
            } else {
                $mode = 'list';
            }
            return $this->showList($mode, $items_per_page);
        }

        return parent::show();
    }

    public function addContent($name)
    {
        if (!isset($name) || empty($name) || !is_string($name)) {
            static::raiseError(__METHOD__ .'(), $name parameter is invalid!');
            return false;
        }

        if (in_array($name, $this->known_contents)) {
            return true;
        }

        array_push($this->known_contents, $name);
        return true;
    }

    public function hasContent($name)
    {
        if (!isset($name) || empty($name) || !is_string($name)) {
            static::raiseError(__METHOD__ .'(), $name parameter is invalid!');
            return false;
        }

        if (!in_array($name, $this->known_contents)) {
            return false;
        }

        return true;
    }

    public function getContent($name, &$data = null)
    {
        if (!isset($name) || empty($name) || !is_string($name)) {
            static::raiseError(__METHOD__ .'(), $name parameter is invalid!');
            return false;
        }

        if (!preg_match('/^[a-z]+$/', $name)) {
            static::raiseError(__METHOD__ .'(), $name parameter is invalid!');
            return false;
        }

        $method_name = 'get'. ucwords(strtolower($name));

        if (!method_exists($this, $method_name) ||
            !is_callable(array($this, $method_name))
        ) {
            static::raiseError(__CLASS__ ." does not have a content method {$method_name}!");
            return false;
        }

        if (($content = $this->$method_name($data)) === false) {
            static::raiseError(__CLASS__ ."::{$method_name} returned false!");
            return false;
        }

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
