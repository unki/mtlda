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

namespace Mtlda\Models ;

class JobsModel extends DefaultModel
{
    public $table_name = 'jobs';
    public $column_name = 'job';
    public $fields = array(
            'queue_idx' => 'integer',
            );
    public $avail_items = array();
    public $items = array();

    public function deleteExpiredJobs($timeout)
    {
        global $mtlda, $db;

        if (!isset($timeout) || empty($timeout) || !is_numeric($timeout)) {
            $mtlda->raiseError(__METHOD__ .', parameter needs to be an integer!');
            return false;
        }

        $now = microtime(true);
        $oldest = $now-$timeout;

        $sql =
            "DELETE FROM
                TABLEPREFIXjobs
            WHERE
                job_time < ?";

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError(__METHOD__ .', failed to prepare query!');
            return false;
        }

        if (!($db->execute($sth, array($oldest)))) {
            $mtlda->raiseError(__METHOD__ .', failed to execute query!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
