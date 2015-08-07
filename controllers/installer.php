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

class InstallerController
{
    private $schema_version_before;

    public function setup()
    {
        global $db, $mtlda;

        if ($db->checkTableExists("TABLEPREFIXmeta")) {
            if (($this->schema_version_before = $db->getDatabaseSchemaVersion()) === false) {
                $mtlda->raiseError("DatabaseController::getDatabaseSchemaVersion() returned false!");
                return false;
            }
        } else {
            $this->schema_version_before = 0;
        }

        if ($this->schema_version_before < $db->getSoftwareSchemaVersion()) {

            if (!$this->installDatabaseTables()) {
                $mtlda->raiseError("InstallerController::installTables() returned false!");
                return false;
            }
        }

        if (!$this->upgradeDatabaseSchema()) {
            $mtlda->raiseError("InstallerController::upgradeDatabaseSchema() returned false!");
            return false;
        }

        if (!empty($this->schema_version_before)) {
            $mtlda->write("Database schema version before upgrade: {$this->schema_version_before}");
        }
        $mtlda->write("Software supported schema version: {$db->getSoftwareSchemaVersion()}");
        $mtlda->write("Database schema version after upgrade: {$db->getDatabaseSchemaVersion()}");

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
