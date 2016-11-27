<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
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

function smarty_modifier_filesize($bytes)
{
    $symbols = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

    /* if $bytes = 0, return 0b */
    if (empty($bytes)) {
        return '0'. $symbols[0];
    }

    /* if a non-numeric value has been provided, return */
    if (!is_numeric($bytes)) {
         return "n/a";
    }

    $exp = floor(log($bytes)/log(1024));

    /* if $bytes was to small, return 0b */
    if ($exp == -INF) {
        return '0'. $symbols[0];
    }

    return sprintf('%.2f '.$symbols[$exp], ($bytes/pow(1024, floor($exp))));
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
