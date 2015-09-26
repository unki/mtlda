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

class PreviewView extends DefaultView
{
    public $class_name = 'preview';
    public $default_mode = 'show';

    public function show()
    {
        global $mtlda, $query, $config;

        if (!isset($query) || !isset($query->params) || empty($query->params)) {
            $mtlda->raiseError("\$query->params not set!");
            return false;
        }

        if (!isset($query->params['id']) || empty($query->params['id'])) {
            $mtlda->raiseError("\$query->id not set or empty!");
            return false;
        }

        if (!$mtlda->isValidId($query->params['id'])) {
            $mtlda->raiseError("\$query->id has an invalid syntax!");
            return false;
        }

        if (!($item = $mtlda->parseId($query->params['id']))) {
            $mtlda->raiseError("Unable to parse \$query->id: ". htmlentities($query->params['id'], ENT_QUOTES));
            return false;
        }

        $queueitem = new Models\QueueItemModel($item->id, $item->guid);
        if (!isset($queueitem)) {
            $mtlda->raiseError("Unable to locate QueueItem!");
            return false;
        }

        if (!($base_path = $config->getWebPath())) {
            $mtlda->raiseError("Web path is missing!");
            return false;
        }

        if ($base_path == '/') {
            $base_path = '';
        }

        $img_url = $base_path .'/preview/'. $query->params['id'];
        $img_load = $base_path .'/resources/images/load.gif';

        $this->assign('img_url', $img_url);
        $this->assign('img_load', $img_load);
        $this->assign('img_id', $query->params['id']);
        $this->assign('img_name', $queueitem->queue_file_name);

        return $this->fetch("preview.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
