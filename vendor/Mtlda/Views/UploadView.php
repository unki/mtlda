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

namespace Mtlda\Views;

class UploadView extends DefaultView
{
    public $class_name = 'upload';
    public $item_name = 'UploadItem';
    public $default_mode = 'show';
    public $queue;

    public function __construct()
    {
        global $mtlda, $config;

        if (!$config->isHttpUploadEnabled()) {
            $mtlda->raiseError(__CLASS__ .', HTTP uploading is not enabled in configuration!', true);
            return false;
        }

        parent::__construct();
        return true;
    }

    public function show()
    {
        global $mtlda, $session, $tmpl;

        if (!($token = $session->getOnetimeIdentifierId("upload"))) {
            $mtlda->raiseError("SessionController::getOnetimeIdentifierId() returned false!");
            return false;
        }
    
        $tmpl->assign('upload_token', $token);
        return $tmpl->fetch("upload.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
