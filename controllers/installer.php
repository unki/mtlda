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

class InstallerController extends DefaultController
{
    private $schema_version_before;

    public function setup()
    {
        global $mtlda, $db, $config;

        if ($db->checkTableExists("TABLEPREFIXmeta")) {
            if (($this->schema_version_before = $db->getDatabaseSchemaVersion()) === false) {
                $mtlda->raiseError("DatabaseController::getDatabaseSchemaVersion() returned false!");
                return false;
            }
        } else {
            $this->schema_version_before = 0;
        }

        if ($this->schema_version_before < $db->getSoftwareSchemaVersion()) {

            if (!$this->createDatabaseTables()) {
                $mtlda->raiseError("InstallerController::createDatabaseTables() returned false!");
                return false;
            }
        }

        if (!$this->upgradeDatabaseSchema()) {
            $mtlda->raiseError("InstallerController::upgradeDatabaseSchema() returned false!");
            return false;
        }

        if (!empty($this->schema_version_before)) {
            print "Database schema version before upgrade: {$this->schema_version_before}<br />\n";
        }
        print "Software supported schema version: {$db->getSoftwareSchemaVersion()}<br />\n";
        print "Database schema version after upgrade: {$db->getDatabaseSchemaVersion()}<br />\n";

        print "<a href='{$config->getWebPath()}'>Return to application</a><br />\n";

        return true;
    }

    private function createDatabaseTables()
    {
        global $mtlda, $db;

        if (!$db->checkTableExists("TABLEPREFIXarchive")) {

            $table_sql = "CREATE TABLE `TABLEPREFIXarchive` (
                `document_idx` int(11) NOT NULL AUTO_INCREMENT,
                `document_guid` varchar(255) DEFAULT NULL,
                `document_file_name` varchar(255) DEFAULT NULL,
                `document_file_hash` varchar(255) DEFAULT NULL,
                `document_file_size` int(11) DEFAULT NULL,
                `document_signing_icon_position` int(11) DEFAULT NULL,
                `document_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                `document_version` varchar(255) DEFAULT NULL,
                `document_derivation` int(11) DEFAULT NULL,
                `document_derivation_guid` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`document_idx`)
                    )
                    ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                $mtlda->raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXaudit")) {

            $table_sql = "CREATE TABLE `TABLEPREFIXaudit` (
                `audit_idx` int(11) NOT NULL AUTO_INCREMENT,
                `audit_guid` varchar(255) DEFAULT NULL,
                `audit_type` varchar(255) DEFAULT NULL,
                `audit_scene` varchar(255) DEFAULT NULL,
                `audit_message` text,
                `audit_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                PRIMARY KEY (`audit_idx`)
                    )
                    ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                $mtlda->raiseError("Failed to create 'audit' table");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXqueue")) {

            $table_sql = "CREATE TABLE `TABLEPREFIXqueue` (
                `queue_idx` int(11) NOT NULL AUTO_INCREMENT,
                `queue_guid` varchar(255) DEFAULT NULL,
                `queue_file_name` varchar(255) DEFAULT NULL,
                `queue_file_hash` varchar(255) DEFAULT NULL,
                `queue_file_size` int(11) DEFAULT NULL,
                `queue_signing_icon_position` int(11) DEFAULT NULL,
                `queue_state` varchar(255) DEFAULT NULL,
                `queue_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                PRIMARY KEY (`queue_idx`)
                    )
                    ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                $mtlda->raiseError("Failed to create 'queue' table");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXmeta")) {

            $table_sql = "CREATE TABLE `TABLEPREFIXmeta` (
                `meta_idx` int(11) NOT NULL auto_increment,
                `meta_key` varchar(255) default NULL,
                `meta_value` varchar(255) default NULL,
                PRIMARY KEY  (`meta_idx`),
                UNIQUE KEY `meta_key` (`meta_key`)
                    )
                    ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                $mtlda->raiseError("Failed to create 'meta' table");
                return false;
            }

            if (!$this->setDatabaseSchemaVersion()) {
                $mtlda->raiseError("Failed to set schema verison!");
                return false;
            }
        }

        return true;
    }

    private function setDatabaseSchemaVersion($version = null)
    {
        global $mtlda, $db;

        if (!isset($version) || empty($version)) {
            $version = $db->getSoftwareSchemaVersion();
        }

        $result = $db->query(
            "REPLACE INTO TABLEPREFIXmeta (
            meta_key,
            meta_value
                ) VALUES (
                    'schema_version',
                    '{$version}'
                    )"
        );

        if ($result === false) {
            $mtlda->raiseError("Unable to set schema_version in meta table!");
            return false;
        }

        return true;

    }

    private function upgradeDatabaseSchema()
    {


        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
