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

namespace MTLDA\Views;

use MTLDA\Models;

class UploadView extends Templates
{
    public $class_name = 'upload';
    public $item_name = 'UploadItem';
    public $default_mode = 'show';
    public $queue;

    public function show()
    {
        global $mtlda, $session;

        if (!($token = $session->getOnetimeIdentifierId("upload"))) {
            $mtlda->raiseError("SessionController:getOnetimeIdentifierId() returned false!");
            return false;
        }
    
        $this->assign('upload_token', $token);
        return $this->fetch("upload.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
