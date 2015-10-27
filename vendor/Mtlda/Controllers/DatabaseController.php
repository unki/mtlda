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

namespace Mtlda\Controllers;

class DatabaseController extends \Thallium\Controllers\DatabaseController
{
    const SCHEMA_VERSION = 25;

    public function truncateDatabaseTables()
    {
        global $mtlda;

        if (($this->query("TRUNCATE TABLE TABLEPREFIXmeta")) === false) {
            $this->raiseError("failed to truncate 'meta' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXjobs")) === false) {
            $this->raiseError("failed to truncate 'jobs' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXmessage_bus")) === false) {
            $this->raiseError("failed to truncate 'message_bus' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXaudit")) === false) {
            $this->raiseError("failed to truncate 'audit' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXqueue")) === false) {
            $this->raiseError("failed to truncate 'queue' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXarchive")) === false) {
            $this->raiseError("failed to truncate 'archive' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXkeywords")) === false) {
            $this->raiseError("failed to truncate 'keywords' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXassign_keywords_to_document")) === false) {
            $this->raiseError("failed to truncate 'assign_keywords_to_document' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXdocument_indices")) === false) {
            $this->raiseError("failed to truncate 'document_indices' table!");
            return false;
        }

        if (($this->query("TRUNCATE TABLE TABLEPREFIXdocument_properties")) === false) {
            $this->raiseError("failed to truncate 'document_properties' table!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
