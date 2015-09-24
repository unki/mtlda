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

abstract class DefaultController
{
    const CONFIG_DIRECTORY = MTLDA_BASE ."/config";
    const CACHE_DIRECTORY = MTLDA_BASE ."/cache";
    const DATA_DIRECTORY = MTLDA_BASE ."/data";
    const ARCHIVE_DIRECTORY = self::DATA_DIRECTORY ."/archive";
    const INCOMING_DIRECTORY = self::DATA_DIRECTORY ."/incoming";
    const WORKING_DIRECTORY = self::DATA_DIRECTORY ."/working";

    const ARCHIVE_NESTING_DEPTH = 5;
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
