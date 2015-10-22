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

        parent::__construct();
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
