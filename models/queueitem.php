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

namespace MTLDA\Models ;

use MTLDA\Controllers;

class QueueItemModel extends DefaultModel
{
    public $table_name = 'queue';
    public $column_name = 'queue';
    public $fields = array(
            'queue_idx' => 'integer',
            'queue_guid' => 'string',
            'queue_file_name' => 'string',
            'queue_file_hash' => 'string',
            'queue_file_size' => 'integer',
            'queue_signing_icon_position' => 'integer',
            'queue_state' => 'string',
            'queue_time' => 'integer',
            );
    public $avail_items = array();
    public $items = array();
    private $working_directory = "../data/working";

    public function __construct($id = null, $guid = null)
    {
        global $mtlda, $db;

        if (
            !isset($id) || !isset($guid) ||
            empty($id) || empty($guid)
        ) {

            parent::__construct();
            return true;

        }

        if (!$mtlda->isValidId($id)) {
            $mtlda->raiseError("\$id is in an invalid format", true);
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($guid)) {
            $mtlda->raiseError("\$guid is in an invalid format", true);
            return false;
        }

        // get $id from db
        $sth = $db->prepare(
            "SELECT
                queue_idx
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                queue_idx LIKE ?
            AND
                queue_guid LIKE ?"
        );

        if (!$db->execute($sth, array($id, $guid))) {
            $mtlda->raiseError("Failed to execute query");
        }

        if (!($row = $sth->fetch())) {
            $mtlda->raiseError("Unable to find queue item with guid value {$guid}");
            return false;
        }

        if (!isset($row->queue_idx) || empty($row->queue_idx)) {
            $mtlda->raiseError("Unable to find queue item with guid value {$guid}");
            return false;
        }

        parent::__construct($row->queue_idx);


        $db->freeStatement($sth);

        return true;
    }

    public function verify()
    {
        global $mtlda;

        if (!isset($this->working_directory)) {
            $mtlda->raiseError("working_directory is not set!");
            return false;
        }

        if (!isset($this->queue_file_name)) {
            $mtlda->raiseError("queue_file_name is not set!");
            return false;
        }

        if (!isset($this->queue_file_hash)) {
            $mtlda->raiseError("queue_file_hash is not set!");
            return false;
        }

        $fqpn = $this->working_directory .'/'. $this->queue_file_name;

        if (!file_exists($fqpn)) {
            $mtlda->raiseError("File {$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $mtlda->raiseError("File {$fqpn} is not readable!");
            return false;
        }

        if (($file_hash = sha1_file($fqpn)) === false) {
            $mtlda->raiseError("Unable to calculate SHA1 hash of file {$fqpn}!");
            return false;
        }

        if (isset($hash) && $hash != $file_hash) {
            $mtlda->raiseError("Hash value of ${file} does not match!");
            return false;
        }

        return true;
    }

    public function getGuid()
    {
        if (!isset($this->queue_guid)) {
            return false;
        }

        return $this->queue_guid;
    }

    public function getFileHash()
    {
        if (!isset($this->queue_file_hash)) {
            return false;
        }

        return $this->queue_file_hash;
    }

    public function preDelete()
    {
        global $mtlda;

        // load StorageController
        $storage = new Controllers\StorageController;

        if (!$storage) {
            $mtlda->raiseError("unable to load StorageController!");
            return false;
        }

        if (!$storage->deleteItemFile($this)) {
            $mtlda->raiseError("StorageController::deleteItemFile() returned false!");
            return false;
        }

        return true;
    }

    public function postDelete()
    {
        global $mtlda, $audit;

        try {
            $audit->log(
                $this->queue_file_name,
                "deleted",
                "queue",
                $this->queue_guid
            );
        } catch (Exception $e) {
            $mtlda->raiseError("AuditController::log() returned false!");
            return false;
        }

        return true;
    }

    public function preSave()
    {
        global $mtlda;

        if ($this->isDuplicate()) {
            $mtlda->raiseError("Duplicated record detected!");
            return false;
        }

        return true;
    }

    public function postSave()
    {
        global $audit;

        $json_str = json_encode(
            array(
                'file_name' => $this->queue_file_name,
                'file_size' => $this->queue_file_size,
                'file_hash' => $this->queue_file_hash,
                'state' => $this->queue_state,
            )
        );

        if (!$json_str) {
            $mtlda->raiseError("json_encode() returned false!");
            return false;
        }

        try {
            $audit->log(
                $json_str,
                "saving",
                "queue",
                $this->queue_guid
            );
        } catch (Exception $e) {
            $queueitem->delete();
            $mtlda->raiseError("AuditController:log() returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
