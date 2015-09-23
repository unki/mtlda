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
        }

        if (!isset($this->schema_version_before)) {
            $this->schema_version_before = 0;
        }

        if ($this->schema_version_before < $db->getSoftwareSchemaVersion()) {
            if (!$this->createDatabaseTables()) {
                $mtlda->raiseError("InstallerController::createDatabaseTables() returned false!");
                return false;
            }
        }

        if ($db->getDatabaseSchemaVersion() < $db->getSoftwareSchemaVersion()) {
            if (!$this->upgradeDatabaseSchema()) {
                $mtlda->raiseError("InstallerController::upgradeDatabaseSchema() returned false!");
                return false;
            }
        }

        if (!empty($this->schema_version_before)) {
            print "Database schema version before upgrade: {$this->schema_version_before}<br />\n";
        }
        print "Software supported schema version: {$db->getSoftwareSchemaVersion()}<br />\n";
        print "Database schema version after upgrade: {$db->getDatabaseSchemaVersion()}<br />\n";

        if (!($base_path = $config->getWebPath())) {
            $mtlda->raiseError("ConfigController::getWebPath() returned false!");
            return false;
        }

        print "<a href='{$base_path}'>Return to application</a><br />\n";

        return true;
    }

    private function createDatabaseTables()
    {
        global $mtlda, $db;

        if (!$db->checkTableExists("TABLEPREFIXarchive")) {

            $table_sql = "CREATE TABLE `TABLEPREFIXarchive` (
                `document_idx` int(11) NOT NULL AUTO_INCREMENT,
                `document_guid` varchar(255) DEFAULT NULL,
                `document_description` TEXT DEFAULT NULL,
                `document_file_name` varchar(255) DEFAULT NULL,
                `document_file_hash` varchar(255) DEFAULT NULL,
                `document_file_size` int(11) DEFAULT NULL,
                `document_signing_icon_position` int(11) DEFAULT NULL,
                `document_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                `document_version` varchar(255) DEFAULT NULL,
                `document_derivation` int(11) DEFAULT NULL,
                `document_derivation_guid` varchar(255) DEFAULT NULL,
                `document_signed_copy` varchar(1) DEFAULT NULL
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

            if (!$db->setDatabaseSchemaVersion()) {
                $mtlda->raiseError("Failed to set schema verison!");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXkeywords")) {

            $table_sql = "CREATE TABLE `TABLEPREFIXkeywords` (
                `keyword_idx` int(11) NOT NULL auto_increment,
                `keyword_name` varchar(255) default NULL,
                `keyword_guid` varchar(255) default NULL,
                PRIMARY KEY  (`keyword_idx`)
                )
                ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                $mtlda->raiseError("Failed to create 'meta' table");
                return false;
            }

            if (!$db->setDatabaseSchemaVersion()) {
                $mtlda->raiseError("Failed to set schema verison!");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXassign_keywords_to_document")) {

            $table_sql = "CREATE TABLE `TABLEPREFIXassign_keywords_to_document` (
                `akd_idx` int(11) NOT NULL auto_increment,
                `akd_archive_idx` int(11) NOT NULL,
                `akd_keyword_idx` int(11) NOT NULL,
                PRIMARY KEY  (`akd_idx`),
                UNIQUE KEY `document_keywords` (`akd_archive_idx`,`akd_keyword_idx`),
                KEY `documents` (`akd_archive_idx`)
                )
                ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                $mtlda->raiseError("Failed to create 'meta' table");
                return false;
            }

            if (!$db->setDatabaseSchemaVersion()) {
                $mtlda->raiseError("Failed to set schema verison!");
                return false;
            }
        }

        if (!$db->getDatabaseSchemaVersion()) {
            if (!$db->setDatabaseSchemaVersion()) {
                $mtlda->raiseError("DatabaseController:setDatabaseSchemaVersion() returned false!");
                return false;
            }
        }

        return true;
    }

    private function upgradeDatabaseSchema()
    {
        global $mtlda, $db;

        if ($db->getDatabaseSchemaVersion() < 3) {
            $this->upgradeDatabaseSchemaV3();
        }

        if ($db->getDatabaseSchemaVersion() < 4) {
            $this->upgradeDatabaseSchemaV4();
        }

        if ($db->getDatabaseSchemaVersion() < 5) {
            $this->upgradeDatabaseSchemaV5();
        }

        /* final action in this function
        // disabled for now as of 20150923
        if (!$db->setDatabaseSchemaVersion()) {
            $mtlda->raiseError("DatabaseController:setDatabaseSchemaVersion() returned false!");
            return false;
        }*/

        return true;
    }

    private function upgradeDatabaseSchemaV3()
    {
        global $mtlda, $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_keywords_to_document
            ADD
                unique index `document_keywords` (akd_archive_idx, akd_keyword_idx),
            ADD index `documents` (akd_archive_idx)"
        );

        if ($result === false) {
            $mtlda->raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(3);
        return true;
    }

    private function upgradeDatabaseSchemaV4()
    {
        global $mtlda, $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD
                `document_signed_copy` varchar(1) DEFAULT NULL
            AFTER
                document_derivation_guid"
        );

        if ($result === false) {
            $mtlda->raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(4);
        return true;
    }

    private function upgradeDatabaseSchemaV5()
    {
        global $mtlda, $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD
                `document_description` TEXT DEFAULT NULL
            AFTER
                document_derivation_guid"
        );

        if ($result === false) {
            $mtlda->raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(5);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
