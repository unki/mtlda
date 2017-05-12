<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017> <Andreas Unterkircher>
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

class InstallerController extends \Thallium\Controllers\InstallerController
{
    protected $schema_version_before;

    protected function createApplicationDatabaseTables()
    {
        global $mtlda, $db;

        if (!$db->checkTableExists("TABLEPREFIXarchive")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXarchive` (
                `document_idx` int(11) NOT NULL AUTO_INCREMENT,
                `document_guid` varchar(255) DEFAULT NULL,
                `document_title` varchar(255) DEFAULT NULL,
                `document_description` TEXT DEFAULT NULL,
                `document_file_name` varchar(255) DEFAULT NULL,
                `document_file_hash` varchar(255) DEFAULT NULL,
                `document_file_size` int(11) DEFAULT NULL,
                `document_signing_icon_position` int(11) DEFAULT NULL,
                `document_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                `document_custom_date` date NULL DEFAULT NULL,
                `document_expiry_date` date NULL DEFAULT NULL,
                `document_version` varchar(255) DEFAULT NULL,
                `document_derivation_guid` varchar(255) DEFAULT NULL,
                `document_signed_copy` varchar(1) DEFAULT NULL,
                `document_deleted` varchar(1) DEFAULT NULL,
                PRIMARY KEY (`document_idx`),
                FULLTEXT KEY `text` (`document_description`)
                    )
                    ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'archive' table");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXqueue")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXqueue` (
                `queue_idx` int(11) NOT NULL AUTO_INCREMENT,
                `queue_guid` varchar(255) DEFAULT NULL,
                `queue_title` varchar(255) default NULL,
                `queue_file_name` varchar(255) DEFAULT NULL,
                `queue_file_hash` varchar(255) DEFAULT NULL,
                `queue_file_size` int(11) DEFAULT NULL,
                `queue_description` TEXT DEFAULT NULL,
                `queue_signing_icon_position` int(11) DEFAULT NULL,
                `queue_state` varchar(255) DEFAULT NULL,
                `queue_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
                `queue_custom_date` date NULL DEFAULT NULL,
                `queue_expiry_date` date NULL DEFAULT NULL,
                `queue_in_processing` varchar(1) DEFAULT NULL,
                PRIMARY KEY (`queue_idx`),
                FULLTEXT KEY `text` (`queue_description`)
                    )
                    ENGINE=InnoDB DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'queue' table");
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
                static::raiseError("Failed to create 'keywords' table");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXassign_keywords_to_document")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXassign_keywords_to_document` (
                `akd_idx` int(11) NOT NULL auto_increment,
                `akd_guid` varchar(255) default NULL,
                `akd_archive_idx` int(11) NOT NULL,
                `akd_queue_idx` int(11) NOT NULL,
                `akd_keyword_idx` int(11) NOT NULL,
                PRIMARY KEY  (`akd_idx`),
                KEY `document_keywords` (`akd_archive_idx`,`akd_keyword_idx`),
                KEY `queue_keywords` (`akd_queue_idx`,`akd_keyword_idx`),
                KEY `documents` (`akd_archive_idx`),
                KEY `queue` (`akd_queue_idx`)
                )
                ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'assign_keywords_to_document' table");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXdocument_indices")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXdocument_indices` (
                `di_idx` int(11) NOT NULL auto_increment,
                `di_guid` varchar(255) default NULL,
                `di_file_hash` varchar(255) DEFAULT NULL
                `di_text` TEXT default NULL,
                PRIMARY KEY  (`di_idx`),
                KEY `file_hash` (di_file_hash),
                FULLTEXT KEY `text` (`di_text`)
                )
                ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'document_indices' table");
                return false;
            }
        }

        if (!$db->checkTableExists("TABLEPREFIXdocument_properties")) {
            $table_sql = "CREATE TABLE `TABLEPREFIXdocument_properties` (
                `dp_idx` int(11) NOT NULL auto_increment,
                `dp_guid` varchar(255) default NULL,
                `dp_file_hash` varchar(255) default NULL,
                `dp_property` varchar(255) default NULL,
                `dp_value` varchar(255) default NULL,
                PRIMARY KEY  (`dp_idx`),
                KEY `file_hash` (dp_file_hash),
                )
                ENGINE=MyISAM DEFAULT CHARSET=utf8;";

            if ($db->query($table_sql) === false) {
                static::raiseError("Failed to create 'document_properties' table");
                return false;
            }
        }

        if (!parent::createApplicationDatabaseTables()) {
            static::raiseError(get_class(parent) .'::createDatabaseTables() returned false!');
            return false;
        }

        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV3()
    {
        global $mtlda, $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_keywords_to_document
            ADD KEY
                `document_keywords` (akd_archive_idx, akd_keyword_idx),
            ADD KEY
                `documents` (akd_archive_idx)"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(3);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV4()
    {
        global $mtlda, $db;

        if ($db->checkColumnExists(
            'TABLEPREFIXarchive',
            'document_signed_copy'
        )) {
            $db->setDatabaseSchemaVersion(4);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD
                `document_signed_copy` varchar(1) DEFAULT NULL
            AFTER
                document_derivation_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(4);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV5()
    {
        global $mtlda, $db;


        if ($db->checkColumnExists(
            'TABLEPREFIXarchive',
            'document_description'
        )) {
            $db->setDatabaseSchemaVersion(5);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD
                `document_description` TEXT DEFAULT NULL
            AFTER
                document_derivation_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(5);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV6()
    {
        global $mtlda, $db;


        if ($db->checkColumnExists(
            'TABLEPREFIXarchive',
            'document_title'
        )) {
            $db->setDatabaseSchemaVersion(6);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD
                `document_title` varchar(255) DEFAULT NULL
            AFTER
                document_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(6);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV7()
    {
        global $mtlda, $db;

        $result = $db->query("
            UPDATE
                TABLEPREFIXarchive
            SET
                document_title=document_file_name
            WHERE
                document_title=''
            OR
                document_title IS NULL
        ");

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(7);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV8()
    {
        global $mtlda, $db;

        if ($db->checkColumnExists(
            'TABLEPREFIXassign_keywords_to_document',
            'akd_guid'
        )) {
            $db->setDatabaseSchemaVersion(8);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_keywords_to_document
            ADD
                `akd_guid` varchar(255) default NULL
            AFTER
                akd_idx"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $result = $db->query(
            "SELECT
                akd_idx
            FROM
                TABLEPREFIXassign_keywords_to_document"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ .' failed!');
            return false;
        }

        while ($row = $result->fetch()) {
            if (!$guid = $mtlda->createGuid()) {
                static::raiseError('Mtlda::createGuid() returned no valid GUID!');
                return false;
            }

            $res = $db->query(
                "UPDATE
                    TABLEPREFIXassign_keywords_to_document
                SET
                    akd_guid='{$guid}'
                WHERE
                    akd_idx LIKE '{$row->akd_idx}'"
            );
            if ($res === false) {
                static::raiseError(__METHOD__ .', update failed!');
                return false;
            }
        }

        $db->setDatabaseSchemaVersion(8);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV10()
    {
        global $mtlda, $db;

        if ($db->checkColumnExists(
            'TABLEPREFIXmessage_bus',
            'msg_session_id'
        )) {
            $db->setDatabaseSchemaVersion(10);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXmessage_bus
            CHANGE COLUMN
                msg_session msg_session_id varchar(255) NOT NULL"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(10);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV11()
    {
        global $mtlda, $db;

        if ($db->checkColumnExists('TABLEPREFIXmessage_bus', 'msg_guid') &&
            $db->checkColumnExists('TABLEPREFIXmessage_bus', 'msg_body')
        ) {
            $db->setDatabaseSchemaVersion(11);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXmessage_bus
            ADD COLUMN
                `msg_guid` varchar(255) default NULL
            AFTER
                msg_idx,
            ADD COLUMN
                `msg_body` varchar(255) NOT NULL
            AFTER
                msg_command"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(11);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV12()
    {
        global $mtlda, $db;

        if ($db->checkColumnExists(
            'TABLEPREFIXmessage_bus',
            'msg_scope'
        )) {
            $db->setDatabaseSchemaVersion(12);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXmessage_bus
            ADD COLUMN
                `msg_scope` varchar(255) default NULL
            AFTER
                msg_session_id"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(12);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV13()
    {
        global $mtlda, $db;

        if ($db->checkColumnExists('TABLEPREFIXmessage_bus', 'msg_submit_time') &&
            $db->checkColumnExists('TABLEPREFIXmessage_bus', 'msg_in_processing')
        ) {
            $db->setDatabaseSchemaVersion(13);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXmessage_bus
            ADD COLUMN
                `msg_submit_time` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)
            AFTER
                msg_session_id,
            ADD COLUMN
                `msg_in_processing` varchar(1) DEFAULT NULL
            AFTER
                msg_body"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(13);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV14()
    {
        global $mtlda, $db;

        if ($db->checkColumnExists('TABLEPREFIXmessage_bus', 'msg_value')) {
            $db->setDatabaseSchemaVersion(14);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXmessage_bus
            ADD COLUMN
                `msg_value` varchar(255) default NULL
            AFTER
                msg_body"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(14);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV15()
    {
        global $db;

        $db->setDatabaseSchemaVersion(15);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV16()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXjobs', 'job_request_guid')) {
            $db->setDatabaseSchemaVersion(16);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXjobs
            ADD COLUMN
                `job_request_guid` varchar(255) DEFAULT NULL
            AFTER
                msg_body"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(16);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV17()
    {
        global $db;

        $db->setDatabaseSchemaVersion(17);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV18()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXdocument_indices', 'di_guid')) {
            $db->setDatabaseSchemaVersion(18);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXdocument_indices
            ADD COLUMN
                `di_guid` varchar(255) default NULL
            AFTER
                di_idx"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(18);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV19()
    {
        global $db;

        $db->setDatabaseSchemaVersion(19);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV20()
    {
        global $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXdocument_indices
            DROP KEY
                document_indices,
            ADD KEY
                `document_indices` (`di_document_idx`,`di_document_guid`)"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(20);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV21()
    {
        global $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXdocument_properties
            DROP KEY
                document_properties,
            ADD KEY
                `document_properties` (`dp_document_idx`,`dp_document_guid`)"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(21);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV22()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXqueue', 'queue_description')) {
            $db->setDatabaseSchemaVersion(22);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXqueue
            ADD COLUMN
                `queue_description` TEXT DEFAULT NULL
            AFTER
                queue_file_size"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(22);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV23()
    {
        global $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD
                FULLTEXT KEY `text` (`document_description`)"
        );

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXqueue
            ADD
                FULLTEXT KEY `text` (`queue_description`)"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(23);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV24()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXarchive', 'document_custom_time')) {
            $db->setDatabaseSchemaVersion(24);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD COLUMN
                `document_custom_time` timestamp NULL DEFAULT NULL
            AFTER
                document_time"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(24);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV25()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXarchive', 'document_custom_date')) {
            $db->setDatabaseSchemaVersion(25);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            CHANGE COLUMN
                `document_custom_time` document_custom_date date NULL DEFAULT NULL"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(25);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV26()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXarchive', 'document_expiry_date')) {
            $db->setDatabaseSchemaVersion(26);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD COLUMN
                `document_expiry_date` date NULL DEFAULT NULL
            AFTER
                document_custom_date"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(26);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV27()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXarchive', 'document_deleted')) {
            $db->setDatabaseSchemaVersion(27);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            ADD COLUMN
                `document_deleted` varchar(1) DEFAULT NULL
            AFTER
                document_signed_copy"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(27);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV28()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXqueue', 'queue_in_processing')) {
            $db->setDatabaseSchemaVersion(28);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXqueue
            ADD COLUMN
                `queue_in_processing` varchar(1) DEFAULT NULL
            AFTER
                queue_time"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(28);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV29()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXqueue', 'queue_custom_date')) {
            $db->setDatabaseSchemaVersion(29);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXqueue
            ADD COLUMN
                `queue_custom_date` date NULL DEFAULT NULL
            AFTER
                queue_time,
            ADD COLUMN
                `queue_expiry_date` date NULL DEFAULT NULL
            AFTER
                queue_custom_date"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(29);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV30()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXqueue', 'queue_title')) {
            $db->setDatabaseSchemaVersion(30);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXqueue
            ADD COLUMN
                `queue_title` varchar(255) default NULL
            AFTER
                queue_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(30);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV31()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXassign_keywords_to_document', 'akd_queue_idx')) {
            $db->setDatabaseSchemaVersion(31);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_keywords_to_document
            ADD COLUMN
                `akd_queue_idx` int(11) NOT NULL
            AFTER
                akd_archive_idx,
            ADD KEY
                `queue_keywords` (`akd_queue_idx`,`akd_keyword_idx`),
            ADD KEY
                `queue` (`akd_queue_idx`)"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(31);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV32()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXdocument_indices', 'di_file_hash')) {
            $db->setDatabaseSchemaVersion(32);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXdocument_indices
            ADD COLUMN
                `di_file_hash` varchar(255) DEFAULT NULL
            AFTER
                di_document_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $result = $db->query(
            "UPDATE
                TABLEPREFIXdocument_indices di
            SET
                di.di_file_hash=(
                    SELECT
                        a.document_file_hash
                    FROM
                        TABLEPREFIXarchive a
                    WHERE
                        di.di_document_idx LIKE a.document_idx
                    AND
                        di.di_document_guid LIKE a.document_guid
                )"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXdocument_indices
            DROP KEY
                document_indices,
            ADD KEY
                `file_hash` (di_file_hash),
            DROP COLUMN
                di_document_idx,
            DROP COLUMN
                di_document_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(32);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV33()
    {
        global $db;

        if ($db->checkColumnExists('TABLEPREFIXdocument_properties', 'dp_file_hash')) {
            $db->setDatabaseSchemaVersion(33);
            return true;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXdocument_properties
            ADD COLUMN
                `dp_file_hash` varchar(255) DEFAULT NULL
            AFTER
                dp_document_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $result = $db->query(
            "UPDATE
                TABLEPREFIXdocument_properties dp
            SET
                dp.dp_file_hash=(
                    SELECT
                        a.document_file_hash
                    FROM
                        TABLEPREFIXarchive a
                    WHERE
                        dp.dp_document_idx LIKE a.document_idx
                    AND
                        dp.dp_document_guid LIKE a.document_guid
                )"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXdocument_properties
            DROP KEY
                document_properties,
            ADD KEY
                `file_hash` (dp_file_hash),
            DROP COLUMN
                dp_document_idx,
            DROP COLUMN
                dp_document_guid"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(33);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV34()
    {
        global $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXarchive
            DROP COLUMN
                document_derivation"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(34);
        return true;
    }

    protected function upgradeApplicationDatabaseSchemaV35()
    {
        global $db;

        $result = $db->query(
            "ALTER TABLE
                TABLEPREFIXassign_keywords_to_document
            DROP KEY
                `document_keywords`,
            ADD KEY
                `document_keywords` (`akd_archive_idx`,`akd_keyword_idx`)"
        );

        if ($result === false) {
            static::raiseError(__METHOD__ ." failed!");
            return false;
        }

        $db->setDatabaseSchemaVersion(35);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
