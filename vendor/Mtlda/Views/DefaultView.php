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

abstract class DefaultView extends \Thallium\Views\DefaultView
{
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
        return true;
    }

    public function raiseError($string, $stop_execution = false, $exception = null)
    {
        global $mtlda;

        $mtlda->raiseError(
            $string,
            $stop_execution,
            $exception
        );

        return true;
    }

    protected function isKnownMode($mode)
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
            is_array($query->params) &&
            isset($query->params[0]) &&
            preg_match('/^list-([0-9]+).html$/', $query->params[0], $parts) &&
            isset($parts) &&
            !empty($parts) &&
            is_array($parts) &&
            isset($parts[1]) &&
            is_numeric($parts[1])
        ) {
            return $this->showList($parts[1]);
        }

        return parent::show();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
