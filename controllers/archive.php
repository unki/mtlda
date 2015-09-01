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

class ArchiveController extends DefaultController
{
    public function checkForDuplicateFileByHash($file_hash)
    {
        global $mtlda, $db;

        if (!isset($file_hash) || empty($file_hash)) {
            $mtlda->raiseError("Require a valid file hash!");
            return false;
        }

        $sth = $db->prepare(
            "SELECT
                document_idx,
                document_guid
            FROM
                TABLEPREFIXarchive
            WHERE
                document_file_hash
            LIKE
                ?"
        );

        if (!$sth) {
            $mtldq->raiseError("Failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($file_hash))) {
            $mtlda->raiseError("Failed to execute query!");
            return false;
        }

        if (!($rows = $sth->fetchAll(\PDO::FETCH_COLUMN))) {
            return array();
        }

        if (count($rows) == 0) {
            return array();
        }

        if (count($rows) > 1) {
            $mtlda->raiseError("There are multiple documents with the same file hash! This should not happend!");
            return false;
        }

        return $rows[0];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
