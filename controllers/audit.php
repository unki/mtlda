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

class AuditController
{
    public function log($message, $entry_type, $scene, $guid = null)
    {
        global $mtlda;

        try {
            $entry = new Models\AuditEntryModel;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load AuditEntryModel");
            return false;
        }

        if (!$entry->setMessage($message)) {
            $mtlda->raiseError("AuditEntryModel::setMessage() returned false!");
            return false;
        }

        if (!empty($guid) && !$entry->setGuid($guid)) {
            $mtlda->raiseError("AuditEntryModel::setGuid() returned false!");
            return false;
        }

        if (!$entry->setEntryType($entry_type)) {
            $mtlda->raiseError("AuditEntryModel::setEntryType() returned false!");
            return false;
        }

        if (!$entry->setScene($scene)) {
            $mtlda->raiseError("AuditEntryModel::setScene() returned false!");
            return false;
        }

        if (!$entry->save()) {
            $mtlda->raiseError("AuditEntryModel::save() returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
