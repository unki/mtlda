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

        if (!$jobs->deleteExpiredJobs(self::EXPIRE_TIMEOUT)) {
            $mtlda->raiseError(get_class($jobs) .'::deleteExpiredJobs() returned false!');
            return false;
        }

        return true;
    }

    public function createJob($sessionid = null)
    {
        global $mtlda;

        if (isset($sessionid) && (empty($session) || !is_string($sessionid))) {
            $mtlda->raiseError(__METHOD__ .', parameter \$sessionid has to be a string!');
            return false;
        }

        try {
            $job = new Models\JobModel;
        } catch (\Exception $e) {
            $mtlda->raiseError(__METHOD__ .', unable to load JobModel!');
            return false;
        }

        if (isset($sessionid) && !$job->setSessionId($sessionid)) {
            $mtlda->raiseError(get_class($job) .'::setSessionId() returned false!');
            return false;
        }

        if (!$job->save()) {
            $mtlda->raiseError(get_class($job) .'::save() returned false!');
            return false;
        }

        if (
            !isset($job->job_guid) ||
            empty($job->job_guid) ||
            !$mtlda->isValidGuidSyntax($job->job_guid)
        ) {
            $mtlda->raiseError(get_class($job) .'::save() has not lead to a valid GUID!');
            return false;
        }

        return $job->job_guid;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
