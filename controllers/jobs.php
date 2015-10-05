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

use MTLDA\Models;

class JobsController extends DefaultController
{
    const EXPIRE_TIMEOUT = 300;

    public function __construct()
    {
        global $mtlda;

        if (!$this->removeExpiredJobs()) {
            $mtlda->raiseError('removeExpiredJobs() returned false!', true);
            return false;
        }

        return true;
    }

    private function removeExpiredJobs()
    {
        global $mtlda;

        try {
            $jobs = new Models\JobsModel;
        } catch (\Exception $e) {
            $mtlda->raiseError('Failed to load JobsModel!');
            return false;
        }

        if (!$jobs->deleteExpiredMessages(self::EXPIRE_TIMEOUT)) {
            $mtlda->raiseError(get_class($jobs) .'::deleteExpiredMessages() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
