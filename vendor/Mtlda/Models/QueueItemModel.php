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
    protected $table_name = 'queue';
    protected $column_name = 'queue';
    protected $fields = array(
            'queue_idx' => 'integer',
            'queue_guid' => 'string',
            'queue_title' => 'string',
            'queue_file_name' => 'string',
            'queue_file_hash' => 'string',
            'queue_file_size' => 'integer',
            'queue_description' => 'string',
            'queue_signing_icon_position' => 'integer',
            'queue_state' => 'string',
            'queue_time' => 'timestamp',
            'queue_custom_date' => 'date',
            'queue_expiry_date' => 'date',
            'queue_in_processing' => 'string',
            );
    protected $avail_items = array();
    protected $items = array();
    private $keywords;
    private $indices;
    private $properties;

    public function __construct($id = null, $guid = null)
    {
        global $mtlda, $db;

        if (!$this->permitRpcUpdates(true)) {
            $this->raiseError(__METHOD__ .'(), permitRpcUpdates() returned false!', true);
            return false;
        }

        try {
            $this->addVirtualField("queue_keywords");
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to add virtual field!', true);
            return false;
        }

        try {
            $this->addRpcEnabledField('queue_file_name');
            $this->addRpcEnabledField('queue_custom_date');
            $this->addRpcEnabledField('queue_expiry_date');
            $this->addRpcEnabledField('queue_title');
            $this->addRpcEnabledField('queue_description');
            $this->addRpcEnabledField('queue_keywords');
            $this->addRpcAction('delete');
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed on invoking addRpcEnabledField() method', true);
            return false;
        }

        // are we creating a new item?
        if (!isset($id) && !isset($guid)) {
            parent::__construct(null);
            return true;
        }

        if (!empty($id) && !$mtlda->isValidId($id)) {
            $this->raiseError(__METHOD__ .'(), $id is in an invalid format', true);
            return false;
        }

        if (!empty($guid) && !$mtlda->isValidGuidSyntax($guid)) {
            $this->raiseError(__METHOD__ .'(), $guid is in an invalid format', true);
            return false;
        }

        if (empty($id) && empty($guid)) {
            $this->raiseError(__METHOD__ .'(), need to know either $id or $guid to load item!', true);
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

        if (($sth = $db->prepare($sql)) === false) {
            $this->raiseError(get_class($db) .'::prepare() returned false!', true);
            return false;
        }

        if (!$db->execute($sth, $arr_query)) {
            $this->raiseError(get_class($db) .'::execute() returned false!', true);
            return false;
        }

        if (($row = $sth->fetch()) === false) {
            $this->raiseError(get_class($sth) .'::fetch() returned false!', true);
            return false;
        }

        if (!isset($row->queue_idx) || empty($row->queue_idx)) {
            $this->raiseError(__METHOD__ ."(), unable to find queue item with guid value {$guid}", true);
            return false;
        }

        parent::__construct($row->queue_idx);

        return true;
    }

    public function verify()
    {
        if (!isset($this->queue_file_name)) {
            $this->raiseError(__METHOD__ .'(), queue_file_name is not set!');
            return false;
        }

        if (!isset($this->queue_file_hash)) {
            $this->raiseError(__METHOD__ .'(), queue_file_hash is not set!');
            return false;
        }

        if (($fqpn = $this->getFilePath()) === false) {
            $this->raiseError(__CLASS__ .'::getFilePath() returned false!');
            return false;
        }

        if (!file_exists($fqpn)) {
            $this->raiseError(__METHOD__ ."(), file {$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $this->raiseError(__METHOD__ ."(), file {$fqpn} is not readable!");
            return false;
        }

        if (($file_hash = sha1_file($fqpn)) === false) {
            $this->raiseError(__METHOD__ ."(), unable to calculate SHA1 hash of file {$fqpn}!");
            return false;
        }

        if (isset($hash) && $hash != $file_hash) {
            $this->raiseError(__METHOD__ ."(), hash value of ${file} does not match!");
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

    public function setFileHash($filehash)
    {
        if (!isset($filehash) || empty($filehash) || !is_string($filehash)) {
            $this->raiseError(__METHOD__ .'(), $filehash parameter is invalid!');
            return false;
        }

        $this->queue_file_hash = $filehash;
        return true;
    }

    public function getFileName()
    {
        if (!isset($this->queue_file_name)) {
            return false;
        }

        return $this->queue_file_name;
    }

    public function setFileName($filename)
    {
        if (!isset($filename) || empty($filename) || !is_string($filename)) {
            $this->raiseError(__METHOD__ .'(), $filename parameter is invalid!');
            return false;
        }

        $this->queue_file_name = $filename;
        return true;
    }

    public function getFileSize()
    {
        if (!isset($this->queue_file_size)) {
            return false;
        }

        return $this->queue_file_size;
    }

    public function setFileSize($filesize)
    {
        if (!isset($filesize) || empty($filesize) || !is_numeric($filesize)) {
            $this->raiseError(__METHOD__ .'(), $filesize parameter is invalid!');
            return false;
        }

        $this->queue_file_size = $filesize;
        return true;
    }

    public function preDelete()
    {
        if (!$this->removeAssignedKeywords()) {
            $this->raiseError(__CLASS__ .'::removeAssignedKeywords() returned false!');
            return false;
        }

        if (!$this->deleteAllDocumentIndices()) {
            $this->raiseError(__CLASS__ .'::deleteAllDocumentIndices() returned false!');
            return false;
        }

        if (!$this->deleteAllDocumentProperties()) {
            $this->raiseError(__CLASS__ .'::deleteAllDocumentProperties() returned false!');
            return false;
        }

        // load StorageController
        $storage = new \Mtlda\Controllers\StorageController;

        if (!$storage) {
            $this->raiseError(__METHOD__ .'(), failed to load StorageController!');
            return false;
        }

        if (!$storage->deleteItemFile($this)) {
            $this->raiseError(get_class($storage) .'::deleteItemFile() returned false!');
            return false;
        }

        return true;
    }

    public function postDelete()
    {
        global $audit;

        try {
            $audit->log(
                $this->queue_file_name,
                "deleted",
                "queue",
                $this->queue_guid
            );
        } catch (\Exception $e) {
            $this->raiseError("AuditController::log() returned false!");
            return false;
        }

        return true;
    }

    public function preSave()
    {
        if ($this->isDuplicate()) {
            $this->raiseError(__METHOD__ .'(), duplicated record detected!');
            return false;
        }

        if (!isset($this->queue_file_name) ||
            empty($this->queue_file_name)
        ) {
            $this->raiseError(__METHOD__ .'(), $queue_file_name must not be empty!');
            return false;
        }

        /* new queueitem? no more action here */
        if (!isset($this->queue_idx) && !isset($this->id)) {
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
            $this->raiseError(__CLASS__ .'::getFilePath() returned false!');
            return false;
        }

        $path = dirname($fqpn);

        if (empty($path)) {
            $this->raiseError(__METHOD__ .'(), why is $path empty?');
            return false;
        }

        $old_file = $path .'/'. basename($this->init_values['queue_file_name']);
        $new_file = $path .'/'. basename($this->queue_file_name);

        if (file_exists($new_file)) {
            $this->raiseError(
                __METHOD__ ."(), unable to rename {$old_file} to {$new_file} - destination already exists!"
            );
            return false;
        }

        if (rename($old_file, $new_file) === false) {
            $this->raiseError(__METHOD__ .'(), rename() returned false!');
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
            $this->raiseError(__METHOD__ .'(), json_encode() returned false!');
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
            $this->raiseError("AuditController::log() returned false!");
            return false;
        }

        return true;
    }

    public function getFilePath()
    {
        if (!($guid = $this->getGuid())) {
            $this->raiseError(__CLASS__ ."::getGuid() returned false!");
            return false;
        }

        if (!($dir_name = $this->generateDirectoryName($guid))) {
            $this->raiseError(__CLASS__ ."::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($dir_name) || empty($dir_name)) {
            $this->raiseError(__METHOD__ .'(), unable to get directory name!');
            return false;
        }

        if (($file_name = $this->getFileName()) === false) {
            $this->raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name)) {
            $this->raiseError(__METHOD__ .'(), unable to get file name!');
            return false;
        }

        $fqpn = \Mtlda\Controllers\DefaultController::WORKING_DIRECTORY;
        $fqpn.= '/'. $dir_name .'/';
        $fqpn.= $file_name;

        return $fqpn;
    }

    protected function generateDirectoryName($guid)
    {
        $dir_name = "";

        if (!isset($guid) || empty($guid)) {
            $this->raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        for ($i = 0; $i < strlen($guid); $i+=2) {
            $guid_part = substr($guid, $i, 2);

            if ($guid_part === false) {
                $this->raiseError(__METHOD__ .'(), substr() returned false!');
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

    public function setState($state)
    {
        if (!isset($state) || empty($state) || !is_string($state)) {
            $this->raiseError(__METHOD__ .'(), $state parameter is invalid!');
            return false;
        }

        $this->queue_state = $state;
        return true;
    }

    public function getTime()
    {
        if (!isset($this->queue_time)) {
            return false;
        }

        return $this->queue_time;
    }

    public function setTime($time)
    {
        if (!isset($time) || empty($time) || !is_numeric($time)) {
            $this->raiseError(__METHOD__ .'(), $time parameter is invalid!');
            return false;
        }

        $this->queue_time = $time;
        return true;
    }

    public function setProcessingFlag($value = true)
    {
        if (!$value) {
            $this->queue_in_processing = 'N';
            return true;
        }

        $this->queue_in_processing = 'Y';
        return true;
    }

    public function getProcessingFlag()
    {
        if (!isset($this->queue_in_processing)) {
            return 'N';
        }

        return $this->queue_in_processing;
    }

    public function isProcessing()
    {
        if (!isset($this->getProcessingFlag)) {
            return false;
        }

        if ($this->queue_in_processing != 'Y') {
            return false;
        }

        return true;
    }

    public function setCustomDate($date)
    {
        if (!isset($date) ||
            empty($date) ||
            (!is_string($date) && !is_numeric($date))
        ) {
            $this->raiseError(__METHOD__ .'(), \$date parameter is invalid!');
            return false;
        }

        $this->queue_custom_date = $date;
        return true;
    }

    public function hasCustomDate()
    {
        if (!isset($this->queue_custom_date) ||
            empty($this->queue_custom_date) ||
            $this->queue_custom_date == '0000-00-00'
        ) {
            return false;
        }

        return true;
    }

    public function getCustomDate()
    {
        if (!$this->hasCustomDate()) {
            $this->raiseError(__CLASS__ .'::hasCustomDate() returned false!');
            return false;
        }

        return $this->queue_custom_date;
    }

    public function setExpiryDate($date)
    {
        if (!isset($date) ||
            empty($date) ||
            (!is_string($date) && !is_numeric($date))
        ) {
            $this->raiseError(__METHOD__ .'(), \$date parameter is invalid!');
            return false;
        }

        $this->queue_expiry_date = $date;
        return true;
    }

    public function hasExpiryDate()
    {
        if (!isset($this->queue_expiry_date) ||
            empty($this->queue_expiry_date) ||
            $this->queue_expiry_date == '0000-00-00'
        ) {
            return false;
        }

        return true;
    }

    public function getExpiryDate()
    {
        if (!$this->hasExpiryDate()) {
            $this->raiseError(__CLASS__ .'::hasExpiryDate() returned false!');
            return false;
        }

        return $this->queue_expiry_date;
    }

    public function hasTitle()
    {
        if (!isset($this->queue_title) || empty($this->queue_title)) {
            return false;
        }

        return true;
    }

    public function getTitle()
    {
        if (!$this->hasTitle()) {
            $this->raiseError(__CLASS__ .'::hasTitle() returned false!');
            return false;
        }

        return $this->queue_title;
    }

    public function setTitle($title)
    {
        if (!isset($title) || !is_string($title)) {
            $this->raiseError(__METHOD__ .'(), $title parameter is invalid!');
            return false;
        }

        $this->queue_title = $title;
        return true;
    }

    public function getDescription()
    {
        if (!isset($this->queue_description)) {
            return false;
        }

        return $this->queue_description;
    }

    public function setDescription($description)
    {
        if (!isset($description) || empty($description) || !is_string($description)) {
            $this->raiseError(__METHOD__ .'(), $description parameter is invalid!');
            return false;
        }

        $this->queue_description = $description;
        return true;
    }

    public function setKeywords($values)
    {
        global $db;

        if (!is_array($values) && preg_match('/^([0-9]+)$/', $values)) {
            $values = array($values);
        } elseif (!is_array($values) && preg_match('/^([0-9]+),([0-9]+)/', $values)) {
            $values = explode(',', $values);
        } elseif (!isset($values) || empty($values)) {
            $values = array();
        } elseif (is_array($values)) {
            array_filter($values, function ($value) {
                if (!is_numeric($value)) {
                    return false;
                }
                return true;
            });
        } else {
            $this->raiseError(__METHOD__ .'(), $values parameter is invalid!');
            return false;
        }

        if (!$this->removeAssignedKeywords()) {
            $this->raiseError(__CLASS__ .'::removeAssignedKeywords() returned false');
            return false;
        }

        if (empty($values)) {
            return true;
        }

        foreach ($values as $value) {
            $value = trim($value);
            if (!is_numeric($value)) {
                $this->raiseError(__METHOD__ .'(), value found that is not a number!');
                return false;
            }

            try {
                $keyword = new KeywordAssignmentModel;
            } catch (\Exception $e) {
                $this->raiseError("Failed to load KeywordAssignmentModel!");
                return false;
            }

            if (!$keyword->setQueue($this->getId())) {
                $this->raiseError("KeywordAssignmentModel::setArchive() returned false!");
                return false;
            }

            if (!$keyword->setKeyword($value)) {
                $this->raiseError("KeywordAssignmentModel::setKeyword() returned false!");
                return false;
            }

            if (!$keyword->save()) {
                $this->raiseError("KeywordAssignmentModel::save() returned false!");
                return false;
            }
        }

        return true;
    }

    public function getKeywords()
    {
        global $db;

        if (isset($this->keywords) && !empty($this->keywords)) {
            return $this->keywords;
        }

        $sth = $db->prepare(
            "SELECT
                akd_keyword_idx
            FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_queue_idx LIKE ?"
        );

        if (!$sth) {
            $this->raiseError(__METHOD__ .", failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($this->getId()))) {
            $this->raiseError(__METHOD__ .", failed to execute query!");
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_COLUMN);

        if ($rows === false) {
            $this->raiseError(__METHOD__ .", failed to fetch result!");
            return false;
        }

        if (!is_array($rows)) {
            $this->raiseError(__METHOD__ .", PDO::fetchAll has not returned an array!");
            return false;
        }

        if (is_null($rows)) {
            return array();
        }

        $this->keywords = $rows;
        return $rows;
    }

    public function hasKeywords()
    {
        if (($keywords = $this->getKeywords()) === false) {
            $this->raiseError(__CLASS__ .'::getKeywords() returned false!');
            return false;
        }

        if (empty($keywords)) {
            return false;
        }

        return true;
    }

    private function removeAssignedKeywords()
    {
        global $db;

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_queue_idx
            LIKE
                ?"
        );

        if (!$sth) {
            $this->raiseError("Unable to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($this->getId()))) {
            $this->raiseError("Unable to execute query!");
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    public function getAssignedKeywords()
    {
        if (($keywords = $this->getKeywords()) === false) {
            $this->raiseError(__CLASS__ .'::getKeywords() returned false!');
            return false;
        }

        if (empty($keywords)) {
            return null;
        }

        $names = array();

        foreach ($keywords as $keyword_idx) {
            try {
                $keyword = new \Mtlda\Models\KeywordModel($keyword_idx);
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ ."(), failed to load KeywordModel({$keyword_idx})!");
                return false;
            }
            if (($name = $keyword->getName()) === false) {
                $this->raiseError(get_class($keyword) .'::getName() returned false!');
                return false;
            }
            array_push($names, $name);
        }

        return implode(', ', $names);
    }

    public function getSigningIconPosition()
    {
        if (!isset($this->queue_signing_icon_position)) {
            return false;
        }

        return $this->queue_signing_icon_position;
    }

    public function setSigningIconPosition($position)
    {
        if (!isset($position) || empty($position) || !is_numeric($position)) {
            $this->raiseError(__METHOD__ .'(), $position parameter is invalid!');
            return false;
        }

        $this->queue_signing_icon_position = $position;
        return true;
    }

    protected function postClone(&$srcobj)
    {
        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load StorageController!");
            return false;
        }

        if (!$guid = $this->getGuid()) {
            $this->raiseError(__CLASS__ .'::getGuid() returned false!');
            return false;
        }

        if (!$src_file = $srcobj->getFilePath()) {
            $this->raiseError(__METHOD__ .'(), unable to retrieve source objects full qualified path name!');
            return false;
        }

        if (!$dst_file = $this->getFilePath()) {
            $this->raiseError(__CLASS__ .'::getFilePath() returned false!');
            return false;
        }

        if (!$storage->createDirectoryStructure(dirname($dst_file))) {
            $this->raiseError(get_class($storage) .'::createDirectoryStructure() returned false!');
            return false;
        }

        if (!$storage->copyFile($src_file, $dst_file)) {
            $this->raiseError(get_class($storage) .'::copyFile() returned false!');
            return false;
        }

        return true;
    }

    public function refresh()
    {
        if (!$fqpn = $this->getFilePath()) {
            $this->raiseError("getFilePath() returned false!");
            return false;
        }

        if (!file_exists($fqpn)) {
            $this->raiseError("File {$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $this->raiseError("File {$fqpn} is not readable!");
            return false;
        }

        clearstatcache(true, $fqpn);

        if (($hash = sha1_file($fqpn)) === false) {
            $this->raiseError(__METHOD__ ." SHA1 value of {$fqpn} can not be calculated!");
            return false;
        }

        if (empty($hash)) {
            $this->raiseError(__METHOD__ ." sha1_file() returned an empty hash value!");
            return false;
        }

        if (($size = filesize($fqpn)) === false) {
            $this->raiseError(__METHOD__ ." filesize of {$fqpn} is not available!");
            return false;
        }

        if (empty($size) || !is_numeric($size) || ($size <= 0)) {
            $this->raiseError(__METHOD__ ." fizesize of {$fqpn} is invalid!");
            return false;
        }

        $this->setFileSize($size);
        $this->setFileHash($hash);
        $this->setTime(time());

        if (!$this->save()) {
            return false;
        }

        return true;
    }

    public function hasIndices()
    {
        if (isset($this->indices) &&
            !empty($this->indices) &&
            is_a($this->indices, 'Mtlda\Models\DocumentIndicesModel') &&
            count($this->indices->items) > 0
        ) {
            return true;
        }

        if (($hash = $this->getFileHash()) === false) {
            $this->raiseError(__CLASS__ .'::getFileHash() returned false!');
            return false;
        }

        try {
            $indices = new \Mtlda\Models\DocumentIndicesModel($hash);
        } catch (\Exception $e) {
            return false;
        }

        $this->indices = $indices;

        if (count($this->indices->items) <= 0) {
            return false;
        }

        return true;
    }

    public function getIndices()
    {
        if (!isset($this->indices)) {
            return false;
        }

        return $this->indices->getIndices();
    }

    public function hasProperties()
    {
        if (isset($this->properties) &&
            !empty($this->properties) &&
            is_a($this->properties, 'Mtlda\Models\DocumentPropertiesModel')
        ) {
            return true;
        }

        try {
            $properties = new \Mtlda\Models\DocumentPropertiesModel(
                $this->getId(),
                $this->getGuid()
            );
        } catch (\Exception $e) {
            return false;
        }

        $this->properties = $properties;
        return true;
    }

    public function getProperties()
    {
        if (!$this->hasProperties()) {
            return false;
        }

        return $this->properties->getProperties();
    }

    protected function deleteAllDocumentIndices()
    {
        if (!$this->hasIndices()) {
            return true;
        }

        if (!$this->indices->delete()) {
            $this->raiseError(get_class($indices) .'::delete() returned false!');
            return false;
        }

        return true;
    }

    protected function deleteAllDocumentProperties()
    {
        if (!$this->hasProperties()) {
            return true;
        }

        if (!$this->properties->delete()) {
            $this->raiseError(get_class($properties) .'::delete() returned false!');
            return false;
        }

        return true;
    }

    public function getFileNameExtension()
    {
        if (($file_name = $this->getFileName()) === false) {
            $this->raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name) || !is_string($file_name)) {
            $this->raiseError(__CLASS__ ."::getFileName() returned invalid data!");
            return false;
        }

        if (!strpos($file_name, '.')) {
            return false;
        }

        if (($suffix = pathinfo($file_name, PATHINFO_EXTENSION)) === false) {
            return false;
        }

        return $suffix;
    }

    public function getFileNameBase()
    {
        if (($file_name = $this->getFileName()) === false) {
            $this->raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name) || !is_string($file_name)) {
            $this->raiseError(__CLASS__ ."::getFileName() returned invalid data!");
            return false;
        }

        return basename($file_name);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
