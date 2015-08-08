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

namespace MTLDA\Controllers;

class SessionController
{
    public function __construct()
    {
        global $mtlda;

        if (empty(session_id())) {

            if (!session_start()) {
                $mtlda->raiseError("Failed to initialize session!");
                return false;
            }
        }
    }

    public function getOnetimeIdentifierId($name)
    {
        if (isset($this->$name) && !empty($this->$name)) {
            return $this->$name;
        }

        global $mtlda;

        if (!($guid = $mtlda->createGuid())) {
            $mtlda->raiseError("MTLDA::createGuid() returned false!");
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            $mtlda->raiseError("MTLDA::createGuid() returned an invalid GUID");
            return false;
        }

        $this->$name = $guid;
        return $this->$name;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
