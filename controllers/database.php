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

use PDO;

class DatabaseController
{
    const SCHEMA_VERSION = 1;

    public $db;
    private $db_cfg;
    private $is_connected = false;

    public function __construct()
    {
        global $mtlda, $config;

        $this->is_connected = false;

        if (!($dbconfig = $config->getDatabaseConfiguration())) {
            $mtlda->raiseError(
                "Error - database configuration is missing or incomplete"
                ." - please check configuration!",
                true
            );
        }

        if (!isset(
                    $dbconfig['type'],
                    $dbconfig['host'],
                    $dbconfig['db_name'],
                    $dbconfig['db_user'],
                    $dbconfig['db_pass'])) {
            print "Error - incomplete database configuration - please check configuration!";
            exit(1);
        }

        $this->db_cfg = $dbconfig;
        $this->connect();

    }

    private function connect()
    {
        $options = array(
                'debug' => 2,
                'portability' => 'DB_PORTABILITY_ALL'
                );

        switch($this->db_cfg['type']) {
            default:
            case 'mysql':
                $dsn = "mysql:dbname=". $this->db_cfg['db_name'] .";host=". $this->db_cfg['host'];
                $user = $this->db_cfg['db_user'];
                $pass = $this->db_cfg['db_pass'];
                break;
            case 'sqlite':
                $dsn = "sqlite:".$this->db_cfg['host'];
                $user = null;
                $pass = null;
                break;
        }

        try {
            $this->db = new PDO($dsn, $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            print "Error - unable to connect to database: ". $e->getMessage();
            exit(1);
        }

        $this->SetConnectionStatus(true);

    }

    private function setConnectionStatus($status)
    {
        $this->is_connected = $status;
    }

    private function getConnectionStatus()
    {
        return $this->is_connected;
    }

    public function query($query = "", $mode = PDO::FETCH_OBJ)
    {
        global $config;

        if (!$this->getConnectionStatus()) {
            $this->connect();
        }

        if ($this->hasTablePrefix()) {
            $this->insertTablePrefix($query);
        }

        /* for manipulating queries use exec instead of query. can save
         * some resource because nothing has to be allocated for results.
         */
        if (preg_match('/^(update|insert)i/', $query)) {
            $result = $this->db->exec($query);
            return $result;
        }

        $result = $this->db->query($query, $mode);
        return $result;

    }

    public function prepare($query = "")
    {
        global $mtlda;

        if (!$this->getConnectionStatus()) {
            $mtlda->raiseError("Can't prepare query - we are not connected!");
        }

        if ($this->hasTablePrefix()) {
            $this->insertTablePrefix($query);
        }

        try {
            $sth = $this->db->prepare($query);
        } catch (PDOException $e) {
            $mtlda->raiseError("Unable to prepare statement: ". $e->getMessage());
            return false;
        }

        return $sth;

    } // db_prepare()

    public function execute($sth, $data = array())
    {
        global $mtlda;

        if (!$this->getConnectionStatus()) {
            $mtlda->raiseError("Can't prepare query - we are not connected!");
        }

        if (!is_object($sth)) {
            return false;
        }

        if (get_class($sth) != "PDOStatement") {
            return false;
        }

        try {
            $result = $sth->execute($data);
        } catch (PDOException $e) {
            $mtlda->raiseError("Unable to execute statement: ". $e->getMessage());
            return false;
        }

        return $result;

    } // execute()

    public function freeStatement($sth)
    {
        global $mtlda;

        if (!is_object($sth)) {
            return false;
        }

        if (get_class($sth) != "PDOStatement") {
            return false;
        }

        try {
            $sth->closeCursor();
        } catch (Exception $e) {
            $sth = null;
            return false;
        }

        return true;

    } // freeStatement()

    public function fetchSingleRow($query = "", $mode = PDO::FETCH_OBJ)
    {
        global $mtlda;

        if (!$this->getConnectionStatus()) {
            $mtlda->raiseError("Can't fetch row - we are not connected!");
        }

        if (empty($query)) {
            return false;
        }

        if (($result = $this->query($query, $mode)) === false) {
            return false;
        }

        if ($result->rowCount() == 0) {
            return false;
        }

        try {
            $row = $result->fetch($mode);
        } catch (PDOException $e) {
            $mtlda->raiseError("Unable to query database: ". $e->getMessage());
            return false;
        }

        return $row;

    } // fetchSingleRow()

    public function hasTablePrefix()
    {
        global $config;

        if (
                isset($this->db_cfg['table_prefix']) &&
                !empty($this->db_cfg['table_prefix']) &&
                is_string($this->db_cfg['table_prefix'])
           ) {
            return true;
        }

        return false;
    }

    public function getTablePrefix()
    {
        if (!isset($this->db_cfg) || empty($this->db_cfg)) {
            return false;
        }

        if (isset($this->db_cfg['table_prefix']) || empty($this->db_cfg['table_prefix'])) {
            return false;
        }

        return $this->db_cfg['table_prefix'];
    }

    public function insertTablePrefix(&$query)
    {
        global $config;
        $query = str_replace("TABLEPREFIX", $this->getTablePrefix(), $query);
    }

    public function getid()
    {
        global $mtlda;

        if (!$this->getConnectionStatus()) {
            $mtlda->raiseError("Can't fetch row - we are not connected!");
            return false;
        }

        try {
            $lastid = $this->db->lastInsertId();
        } catch (PDOException $e) {
            $mtlda->raiseError("unable to detect last inserted row ID!");
            return false;
        }

        /* Get the last primary key ID from execute query */
        return $lastid;

    }

    public function checkTableExists($table_name)
    {
        global $mtlda;

        if (!$this->getConnectionStatus()) {
            $mtlda->raiseError("Can't check table - we are not connected!");
            return false;
        }

        $result = $this->query("SHOW TABLES");

        if (!$result) {
            return false;
        }

        if ($this->hasTablePrefix()) {
            $table_name = str_replace("TABLEPREFIX", $this->getTablePrefix(), $table_name);
            $this->insertTablePrefix($query);
        }

        $tables_in = "Tables_in_{$this->db_cfg['db_name']}";

        while ($row = $result->fetch()) {
            if ($row->$tables_in == $table_name) {
                return true;
            }
        }

        return false;
    }

    public function getDatabaseSchemaVersion()
    {
        global $mtlda;

        if (!$this->getConnectionStatus()) {
            $mtlda->raiseError("Can't check table - we are not connected!");
            return false;
        }

        if (!$this->checkTableExists("TABLEPREFIXmeta")) {
            return false;
        }

        $result = $this->fetchSingleRow(
            "SELECT
                meta_value
            FROM
                TABLEPREFIXmeta
            WHERE
                meta_key LIKE 'schema version'"
        );

        if (
            isset($result->meta_value) ||
            empty($result->meta_value) ||
            !is_numeric($result->meta_value)
        ) {
            return 0;
        }

        return $result->meta_value;
    }

    public function getSoftwareSchemaVersion()
    {
        return $this::SCHEMA_VERSION;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
