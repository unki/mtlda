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
            'queue_description' => 'string',
            'queue_signing_icon_position' => 'integer',
            'queue_state' => 'string',
            'queue_time' => 'timestamp',
            );
    public $avail_items = array();
    public $items = array();

    public function __construct($id = null, $guid = null)
    {
        global $mtlda, $db;

        if (!$this->permitRpcUpdates(true)) {
            $mtlda->raiseError("permitRpcUpdates() returned false!");
            return false;
        }

        try {
            $this->addRpcEnabledField('queue_file_name');
            $this->addRpcAction('delete');
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed on invoking addRpcEnabledField() method");
            return false;
        }

        // are we creating a new item?
        if (!isset($id) && !isset($guid)) {
            parent::__construct(null);
            return true;
        }

        if (!empty($id) && !$mtlda->isValidId($id)) {
            $mtlda->raiseError("\$id is in an invalid format", true);
            return false;
        }

        if (!empty($guid) && !$mtlda->isValidGuidSyntax($guid)) {
            $mtlda->raiseError("\$guid is in an invalid format", true);
            return false;
        }

        if (empty($id) && empty($guid)) {
            $mtlda->raiseError("Need to know either \$id or \$guid to load item!", true);
            return false;
        }

        $sql =
            "SELECT
                queue_idx
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE";

        $arr_query = array();
        if (isset($id)) {
            $sql.= "
                queue_idx LIKE ?
            ";
            $arr_query[] = $id;
        }
        if (isset($id) && isset($guid)) {
            $sql.= "
                AND
            ";
        }
        if (isset($guid)) {
            $sql.= "
                queue_guid LIKE ?
            ";
            $arr_query[] = $guid;
        };

        if (!$sth = $db->prepare($sql)) {
            $mtlda->raiseError("Failed to prepare query!", true);
            return false;
        }

        if (!$db->execute($sth, $arr_query)) {
            $mtlda->raiseError("Failed to execute query!", true);
            return false;
        }

        if (!($row = $sth->fetch())) {
            $mtlda->raiseError("Unable to find queue item with guid value {$guid}", true);
            return false;
        }

        if (!isset($row->queue_idx) || empty($row->queue_idx)) {
            $mtlda->raiseError("Unable to find queue item with guid value {$guid}", true);
            return false;
        }

        parent::__construct($row->queue_idx);

        return true;
    }

    public function verify()
    {
        global $mtlda;

        if (!isset($this->queue_file_name)) {
            $mtlda->raiseError("queue_file_name is not set!");
            return false;
        }

        if (!isset($this->queue_file_hash)) {
            $mtlda->raiseError("queue_file_hash is not set!");
            return false;
        }

        if (!$fqpn = $this->getFilePath()) {
            $mtlda->raiseError("getFilePath() returned false!");
            return false;
        }

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

    public function getFileHash()
    {
        if (!isset($this->queue_file_hash)) {
            return false;
        }

        return $this->queue_file_hash;
    }

    public function getFileName()
    {
        if (!isset($this->queue_file_name)) {
            return false;
        }

        return $this->queue_file_name;
    }

    public function getFileSize()
    {
        if (!isset($this->queue_file_size)) {
            return false;
        }

        return $this->queue_file_size;
    }

    public function preDelete()
    {
        global $mtlda;

        // load StorageController
        $storage = new \Mtlda\Controllers\StorageController;

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
        } catch (\Exception $e) {
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

        if (!isset($this->queue_file_name) ||
            empty($this->queue_file_name)
        ) {
            $mtlda->raiseError("\$queue_file_name must not be empty!");
            return false;
        }

        /* new queueitem? no more action here */
        if (!isset($this->document_idx) && !isset($this->id)) {
            return true;
        }

        if (!isset($this->init_values['queue_file_name']) ||
            empty($this->init_values['queue_file_name'])
        ) {
            return true;
        }

        /* filename hasn't changed? we are done */
        if ($this->init_values['queue_file_name'] == $this->queue_file_name) {
            return true;
        }

        if (!$fqpn = $this->getFilePath()) {
            $mtlda->raiseError(__CLASS__ ."::getFilePath() returned false!");
            return false;
        }

        $path = dirname($fqpn);

        if (empty($path)) {
            $mtlda->raiseError("why is \$path empty?");
            return false;
        }

        $old_file = $path .'/'. basename($this->init_values['queue_file_name']);
        $new_file = $path .'/'. basename($this->queue_file_name);

        if (file_exists($new_file)) {
            $mtlda->raiseError("Unable to rename {$old_file} to {$new_file} - destination already exists!");
            return false;
        }

        if (rename($old_file, $new_file) === false) {
            $mtlda->raiseError("rename() returned false!");
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
        } catch (\Exception $e) {
            $queueitem->delete();
            $mtlda->raiseError("AuditController:log() returned false!");
            return false;
        }

        return true;
    }

    public function getFilePath()
    {
        if (!($guid = $this->getGuid())) {
            $mtlda->raiseError(__CLASS__ ."::getGuid() returned false!");
            return false;
        }

        if (!($dir_name = $this->generateDirectoryName($guid))) {
            $mtlda->raiseError(__CLASS__ ."::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($dir_name) || empty($dir_name)) {
            $mtlda->raiseError("Unable to get directory name!");
            return false;
        }

        if (!($file_name = $this->getFileName())) {
            $mtlda->raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name)) {
            $mtlda->raiseError("Unable to get file name!");
            return false;
        }

        $fqpn = \Mtlda\Controllers\DefaultController::WORKING_DIRECTORY;
        $fqpn.= '/'. $dir_name .'/';
        $fqpn.= $file_name;

        return $fqpn;
    }

    public function generateDirectoryName($guid)
    {
        global $mtlda;

        $dir_name = "";

        if (empty($guid)) {
            $mtlda->raiseError("guid is empty!");
            return false;
        }

        for ($i = 0; $i < strlen($guid); $i+=2) {
            $guid_part = substr($guid, $i, 2);

            if ($guid_part === false) {
                $mtlda->raiseError("substr() returned false!");
                return false;
            }

            // stop if we reach nesting depth
            if (($i/2) > \Mtlda\Controllers\DefaultController::ARCHIVE_NESTING_DEPTH) {
                break;
            }

            $dir_name.= $guid_part.'/';
        }

        if (!isset($dir_name) || empty($dir_name)) {
            return false;
        }

        // remove trailing slash
        $dir_name = rtrim($dir_name, '/');

        return $dir_name;
    }

    public function getState()
    {
        if (!isset($this->queue_state)) {
            return false;
        }

        return $this->queue_state;
    }

    public function getTime()
    {
        if (!isset($this->queue_time)) {
            return false;
        }

        return $this->queue_time;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
