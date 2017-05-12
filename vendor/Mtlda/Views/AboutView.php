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

namespace Mtlda\Views;

class AboutView extends DefaultView
{
    protected static $view_default_mode = 'show';
    protected static $view_class_name = 'about';

    public function show()
    {
        global $db, $tmpl;

        $tmpl->assign("mtlda_version", \Mtlda\Controllers\MainController::VERSION);
        $tmpl->assign("mtlda_schema_version", $db->getApplicationDatabaseSchemaVersion());
        $tmpl->assign("framework_schema_version", $db->getFrameworkDatabaseSchemaVersion());

        return $tmpl->fetch("about.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
