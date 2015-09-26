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

use MTLDA\Controllers;

class AboutView extends DefaultView
{
    public $default_mode = 'show';
    public $class_name = 'about';

    public function show()
    {
        global $db;

        $this->assign("mtlda_version", Controllers\MTLDA::VERSION);
        $this->assign("mtlda_schema_version", $db->getDatabaseSchemaVersion());

        return $this->fetch("about.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
