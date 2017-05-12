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

namespace Mtlda\Models ;

class QueueItemModel extends DefaultModel
{
    protected static $model_table_name = 'queue';
    protected static $model_column_prefix = 'queue';
    protected static $model_fields = array(
        'idx' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'title' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'file_name' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'file_hash' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'file_size' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'description' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'signing_icon_position' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'state' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'time' => array(
            FIELD_TYPE => FIELD_TIMESTAMP,
        ),
        'custom_date' => array(
            FIELD_TYPE => FIELD_DATE,
        ),
        'expiry_date' => array(
            FIELD_TYPE => FIELD_DATE,
        ),
        'in_processing' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );

    protected $keywords;
    protected $indices;
    protected $properties;

    protected function __init()
    {
        global $mtlda, $db;

        $this->permitRpcUpdates(true);
        $this->addVirtualField('keywords');
        $this->addRpcEnabledField('file_name');
        $this->addRpcEnabledField('custom_date');
        $this->addRpcEnabledField('expiry_date');
        $this->addRpcEnabledField('title');
        $this->addRpcEnabledField('description');
        $this->addRpcEnabledField('keywords');
        $this->addRpcAction('delete');
        return true;
    }

    public function verify()
    {
        if (!$this->hasFieldValue('file_name')) {
            static::raiseError(__METHOD__ .'(), queue_file_name is not set!');
            return false;
        }

        if (!$this->hasFieldValue('file_hash')) {
            static::raiseError(__METHOD__ .'(), queue_file_hash is not set!');
            return false;
        }

        if (($fqpn = $this->getFilePath()) === false) {
            static::raiseError(__CLASS__ .'::getFilePath() returned false!');
            return false;
        }

        if (!file_exists($fqpn)) {
            static::raiseError(__METHOD__ ."(), file {$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            static::raiseError(__METHOD__ ."(), file {$fqpn} is not readable!");
            return false;
        }

        if (($file_hash = sha1_file($fqpn)) === false) {
            static::raiseError(__METHOD__ ."(), unable to calculate SHA1 hash of file {$fqpn}!");
            return false;
        }

        if (isset($hash) && $hash != $file_hash) {
            static::raiseError(__METHOD__ ."(), hash value of ${file} does not match!");
            return false;
        }

        return true;
    }

    public function hasFileHash()
    {
        if (!$this->hasFieldValue('file_hash')) {
            return false;
        }

        return true;
    }

    public function getFileHash()
    {
        if (!$this->hasFileHash()) {
            static::raiseError(__CLASS__ .'::hasFileHash() returned false!');
            return false;
        }

        if (($hash = $this->getFieldValue('file_hash')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $hash;
    }

    public function setFileHash($file_hash)
    {
        if (!isset($file_hash) || empty($file_hash) || !is_string($file_hash)) {
            static::raiseError(__METHOD__ .'(), $file_hash parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('file_hash', $file_hash)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasFileName()
    {
        if (!$this->hasFieldValue('file_name')) {
            return false;
        }

        return true;
    }

    public function getFileName()
    {
        if (!$this->hasFileName()) {
            static::raiseError(__CLASS__ .'::hasFileName() returned false!');
            return false;
        }

        if (($name = $this->getFieldValue('file_name')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $name;
    }

    public function setFileName($file_name)
    {
        if (!isset($file_name) || empty($file_name) || !is_string($file_name)) {
            static::raiseError(__METHOD__ .'(), $file_name parameter is invalid!');
            return false;
        }

        if (strpos($file_name, '/') || strpos($file_name, '\\') || strpos($file_name, '..')) {
            static::raiseError(__METHOD__ .'(), $file_name parameter contains forbidden characters!');
            return false;
        }

        if (!$this->setFieldValue('file_name', basename($file_name))) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasFileSize()
    {
        if (!$this->hasFieldValue('file_size')) {
            return false;
        }

        return true;
    }

    public function getFileSize()
    {
        if (!$this->hasFileSize()) {
            static::raiseError(__CLASS__ .'::hasFileSize() returned false!');
            return false;
        }

        if (($size = $this->getFieldValue('file_size')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $size;
    }

    public function setFileSize($file_size)
    {
        if (!isset($file_size) || empty($file_size) || !is_numeric($file_size)) {
            static::raiseError(__METHOD__ .'(), $file_size parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('file_size', $file_size)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    protected function preDelete()
    {
        if (!$this->removeAssignedKeywords()) {
            static::raiseError(__CLASS__ .'::removeAssignedKeywords() returned false!');
            return false;
        }

        if (!$this->deleteAllDocumentIndices()) {
            static::raiseError(__CLASS__ .'::deleteAllDocumentIndices() returned false!');
            return false;
        }

        if (!$this->deleteAllDocumentProperties()) {
            static::raiseError(__CLASS__ .'::deleteAllDocumentProperties() returned false!');
            return false;
        }

        // load StorageController
        $storage = new \Mtlda\Controllers\StorageController;

        if (!$storage) {
            static::raiseError(__METHOD__ .'(), failed to load StorageController!');
            return false;
        }

        if (!$storage->deleteItemFile($this)) {
            static::raiseError(get_class($storage) .'::deleteItemFile() returned false!');
            return false;
        }

        return true;
    }

    protected function postDelete()
    {
        global $audit;

        if (!$this->hasFileName()) {
            return true;
        }

        if (($name = $this->getFileName()) === false) {
            static::raiseError(__CLASS__ .'::getFileName() returned false!');
            return false;
        }

        try {
            $audit->log(
                $name,
                "deleted",
                "queue",
                $this->getGuid()
            );
        } catch (\Exception $e) {
            static::raiseError("AuditController::log() returned false!");
            return false;
        }

        return true;
    }

    protected function preSave()
    {
        if ($this->isDuplicate()) {
            static::raiseError(__METHOD__ .'(), duplicated record detected!');
            return false;
        }

        if (!$this->hasFileName()) {
            static::raiseError(__METHOD__ .'(), no filename is known!');
            return false;
        }

        /* new queueitem? no more action here */
        if ($this->isNew()) {
            return true;
        }

        if (!isset($this->model_init_values['file_name']) ||
            empty($this->model_init_values['file_name'])
        ) {
            return true;
        }

        /* filename hasn't changed? we are done */
        if ($this->model_init_values['file_name'] == $this->getFileName()) {
            return true;
        }

        if (($fqpn = $this->getFilePath()) === false) {
            static::raiseError(__CLASS__ .'::getFilePath() returned false!');
            return false;
        }

        $path = dirname($fqpn);

        if (empty($path)) {
            static::raiseError(__METHOD__ .'(), why is $path empty?');
            return false;
        }

        $old_file = $path .'/'. basename($this->model_init_values['file_name']);
        $new_file = $path .'/'. basename($this->getFileName());

        if (file_exists($new_file)) {
            static::raiseError(
                __METHOD__ ."(), unable to rename {$old_file} to {$new_file} - destination already exists!"
            );
            return false;
        }

        if (rename($old_file, $new_file) === false) {
            static::raiseError(__METHOD__ .'(), rename() returned false!');
            return false;
        }

        return true;
    }

    protected function postSave()
    {
        global $audit;

        $json_str = json_encode(
            array(
                'file_name' => $this->getFileName(),
                'file_size' => $this->getFileSize(),
                'file_hash' => $this->getFileHash(),
                'state' => $this->getState(),
            )
        );

        if (!$json_str) {
            static::raiseError(__METHOD__ .'(), json_encode() returned false!');
            return false;
        }

        try {
            $audit->log(
                $json_str,
                "saving",
                "queue",
                $this->getGuid()
            );
        } catch (\Exception $e) {
            $queueitem->delete();
            static::raiseError("AuditController::log() returned false!");
            return false;
        }

        return true;
    }

    public function getFilePath()
    {
        if (!($guid = $this->getGuid())) {
            static::raiseError(__CLASS__ ."::getGuid() returned false!");
            return false;
        }

        if (!($dir_name = $this->generateDirectoryName($guid))) {
            static::raiseError(__CLASS__ ."::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($dir_name) || empty($dir_name)) {
            static::raiseError(__METHOD__ .'(), unable to get directory name!');
            return false;
        }

        if (($file_name = $this->getFileName()) === false) {
            static::raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name)) {
            static::raiseError(__METHOD__ .'(), unable to get file name!');
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
            static::raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        for ($i = 0; $i < strlen($guid); $i+=2) {
            $guid_part = substr($guid, $i, 2);

            if ($guid_part === false) {
                static::raiseError(__METHOD__ .'(), substr() returned false!');
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

    public function hasState()
    {
        if (!$this->hasFieldValue('state')) {
            return false;
        }

        return true;
    }

    public function getState()
    {
        if (!$this->hasState()) {
            static::raiseError(__CLASS__ .'::hasState() returned false!');
            return false;
        }

        if (($state = $this->getFieldValue('state')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $state;
    }

    public function setState($state)
    {
        if (!isset($state) || empty($state) || !is_string($state)) {
            static::raiseError(__METHOD__ .'(), $state parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('state', $state)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasTime()
    {
        if (!$this->hasFieldValue('time')) {
            return false;
        }

        return true;
    }

    public function getTime()
    {
        if (!$this->hasTime()) {
            static::raiseError(__CLASS__ .'::hasTime() returned false!');
            return false;
        }

        if (($time = $this->getFieldValue('time')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $time;
    }

    public function setTime($time)
    {
        if (!isset($time) || empty($time) || !is_numeric($time)) {
            static::raiseError(__METHOD__ .'(), $time parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('time', $time)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasProcessingFlag()
    {
        if (!$this->hasFieldValue('in_processing')) {
            return false;
        }

        return true;
    }

    public function getProcessingFlag()
    {
        if (!$this->hasProcessingFlag()) {
            static::raiseError(__CLASS__ .'::hasProcessingFlag() returned false!');
            return false;
        }

        if (($flag = $this->getFieldValue('in_processing')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $flag;
    }

    public function setProcessingFlag($flag = true)
    {
        if (!isset($flag) || !is_bool($flag)) {
            static::raiseError(__METHOD__ .'(), $flag parameter is invalid!');
            return false;
        }

        if (!$flag) {
            $flag = 'N';
        } else {
            $flag = 'Y';
        }

        if (!$this->setFieldValue('in_processing', $flag)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function isProcessing()
    {
        if ($this->hasProcessingFlag()) {
            return false;
        }

        if (($flag = $this->getProcessingFlag()) === false) {
            static::raiseError(__CLASS__ .'::getProcessingFlag() returned false!');
            return false;
        }

        if ($flag !== 'Y') {
            return false;
        }

        return true;
    }

    public function hasCustomDate()
    {
        if (!$this->hasFieldValue('custom_date')) {
            return false;
        }

        if ($this->getFieldValue('custom_date') === '0000-00-00') {
            return false;
        }

        return true;
    }

    public function getCustomDate()
    {
        if (!$this->hasCustomDate()) {
            static::raiseError(__CLASS__ .'::hasCustomDate() returned false!');
            return false;
        }

        if (($date = $this->getFieldValue('custom_date')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $date;
    }

    public function setCustomDate($custom_date)
    {
        if (!isset($custom_date) ||
             empty($custom_date) ||
            (!is_string($date) && !is_numeric($date))
        ) {
            static::raiseError(__METHOD__ .'(), $custom_date parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('custom_date', $custom_date)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasExpiryDate()
    {
        if (!$this->hasFieldValue('expiry_date')) {
            return false;
        }

        if ($this->getFieldValue('expiry_date') === '0000-00-00') {
            return false;
        }

        return true;
    }

    public function getExpiryDate()
    {
        if (!$this->hasExpiryDate()) {
            static::raiseError(__CLASS__ .'::hasExpiryDate() returned false!');
            return false;
        }

        if (($date = $this->getFieldValue('expiry_date')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $date;
    }

    public function setExpiryDate($expiry_date)
    {
        if (!isset($expiry_date) ||
             empty($expiry_date) ||
            (!is_string($date) && !is_numeric($date))
        ) {
            static::raiseError(__METHOD__ .'(), $expiry_date parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('expiry_date', $expiry_date)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasTitle()
    {
        if (!$this->hasFieldValue('title')) {
            return false;
        }

        return true;
    }

    public function getTitle()
    {
        if (!$this->hasTitle()) {
            static::raiseError(__CLASS__ .'::hasTitle() returned false!');
            return false;
        }

        if (($title = $this->getFieldValue('title')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $title;
    }

    public function setTitle($title)
    {
        if (!isset($title) || !is_string($title)) {
            static::raiseError(__METHOD__ .'(), $title parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('title', $title)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasDescription()
    {
        if (!$this->hasFieldValue('description')) {
            return false;
        }

        return true;
    }

    public function getDescription()
    {
        if (!$this->hasDescription()) {
            static::raiseError(__CLASS__ .'::hasDescription() returned false!');
            return false;
        }

        if (($desc = $this->getFieldValue('title')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $desc;
    }

    public function setDescription($description)
    {
        if (!isset($description) || empty($description) || !is_string($description)) {
            static::raiseError(__METHOD__ .'(), $description parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('description', $description)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

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
            static::raiseError(__METHOD__ .'(), $values parameter is invalid!');
            return false;
        }

        if (!$this->removeAssignedKeywords()) {
            static::raiseError(__CLASS__ .'::removeAssignedKeywords() returned false');
            return false;
        }

        if (empty($values)) {
            return true;
        }

        foreach ($values as $value) {
            if (!isset($value)) {
                static::raiseError(__METHOD__ .'(), invalid value found!');
                return false;
            }

            $value = trim($value);

            if (!is_numeric($value)) {
                static::raiseError(__METHOD__ .'(), value found that is not a number!');
                return false;
            }

            try {
                $keyword = new \Mtlda\Models\KeywordAssignmentModel;
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load KeywordAssignmentModel!');
                return false;
            }

            if (!$keyword->setQueue($this->getIdx())) {
                static::raiseError(get_class($keyword) .'::setArchive() returned false!');
                return false;
            }

            if (!$keyword->setKeyword($value)) {
                static::raiseError(get_class($keyword) .'::setKeyword() returned false!');
                return false;
            }

            if (!$keyword->save()) {
                static::raiseError(get_class($keyword) .'::save() returned false!');
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
            static::raiseError(__METHOD__ .", failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($this->getIdx()))) {
            static::raiseError(__METHOD__ .", failed to execute query!");
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_COLUMN);

        if ($rows === false) {
            static::raiseError(__METHOD__ .", failed to fetch result!");
            return false;
        }

        if (!is_array($rows)) {
            static::raiseError(__METHOD__ .", PDO::fetchAll has not returned an array!");
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
            static::raiseError(__CLASS__ .'::getKeywords() returned false!');
            return false;
        }

        if (empty($keywords)) {
            return false;
        }

        return true;
    }

    protected function removeAssignedKeywords()
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
            static::raiseError("Unable to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($this->getIdx()))) {
            static::raiseError("Unable to execute query!");
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    public function getAssignedKeywords()
    {
        if (($keywords = $this->getKeywords()) === false) {
            static::raiseError(__CLASS__ .'::getKeywords() returned false!');
            return false;
        }

        if (empty($keywords)) {
            return null;
        }

        $names = array();

        foreach ($keywords as $keyword_idx) {
            try {
                $keyword = new \Mtlda\Models\KeywordModel(array(
                    'idx' => $keyword_idx
                ));
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ ."(), failed to load KeywordModel({$keyword_idx})!");
                return false;
            }
            if (($name = $keyword->getName()) === false) {
                static::raiseError(get_class($keyword) .'::getName() returned false!');
                return false;
            }
            array_push($names, $name);
        }

        return implode(', ', $names);
    }

    public function hasSigningIconPosition()
    {
        if (!$this->hasFieldValue('signing_icon_position')) {
            return false;
        }

        return true;
    }

    public function getSigningIconPosition()
    {
        if (!$this->hasSigningIconPosition()) {
            static::raiseError(__CLASS__ .'::hasSigningIconPosition() returned false!');
            return false;
        }

        if (($pos = $this->getFieldValue('signing_icon_position')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $pos;
    }

    public function setSigningIconPosition($position)
    {
        if (!isset($position) || empty($position) || !is_numeric($position)) {
            static::raiseError(__METHOD__ .'(), $position parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('signing_icon_position', $position)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    protected function afterClone(&$src, $clone)
    {
        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError("Failed to load StorageController!");
            return false;
        }

        if (($src_file = $src->getFilePath()) === false) {
            static::raiseError(__METHOD__ .'(), unable to retrieve source objects full qualified path name!');
            return false;
        }

        if (($dst_file = $clone->getFilePath()) === false) {
            static::raiseError(__CLASS__ .'::getFilePath() returned false!');
            return false;
        }

        if (!$storage->createDirectoryStructure(dirname($dst_file))) {
            static::raiseError(get_class($storage) .'::createDirectoryStructure() returned false!');
            return false;
        }

        if (!$storage->copyFile($src_file, $dst_file)) {
            static::raiseError(get_class($storage) .'::copyFile() returned false!');
            return false;
        }

        return true;
    }

    public function refresh()
    {
        if (($fqpn = $this->getFilePath()) === false) {
            static::raiseError("getFilePath() returned false!");
            return false;
        }

        if (!file_exists($fqpn)) {
            static::raiseError("File {$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            static::raiseError("File {$fqpn} is not readable!");
            return false;
        }

        clearstatcache(true, $fqpn);

        if (($hash = sha1_file($fqpn)) === false) {
            static::raiseError(__METHOD__ ." SHA1 value of {$fqpn} can not be calculated!");
            return false;
        }

        if (empty($hash)) {
            static::raiseError(__METHOD__ ." sha1_file() returned an empty hash value!");
            return false;
        }

        if (($size = filesize($fqpn)) === false) {
            static::raiseError(__METHOD__ ." filesize of {$fqpn} is not available!");
            return false;
        }

        if (empty($size) || !is_numeric($size) || ($size <= 0)) {
            static::raiseError(__METHOD__ ." fizesize of {$fqpn} is invalid!");
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
            $this->indices->hasItems()
        ) {
            return true;
        }

        if (($hash = $this->getFileHash()) === false) {
            static::raiseError(__CLASS__ .'::getFileHash() returned false!');
            return false;
        }

        try {
            $indices = new \Mtlda\Models\DocumentIndicesModel(array(
                'file_hash' => $hash
            ));
        } catch (\Exception $e) {
            return false;
        }

        $this->indices = $indices;

        if (!$this->indices->hasItems()) {
            return false;
        }

        return true;
    }

    public function getIndices($load = false)
    {
        if (!$this->hasIndices()) {
            static::raiseError(__CLASS__ .'::hasIndices() returned false!');
            return false;
        }

        if (($indices = $this->indices->getIndices()) === false) {
            static::raiseError(get_class($this->indices) .'::getIndices() returned false!');
            return false;
        }

        if (!isset($load) || !$load || $load !== true) {
            return $indices;
        }

        $indices_models = array();

        foreach ($indices as $index) {
            if (!isset($index) || empty($index) || !is_array($index)) {
                static::raiseError(__METHOD__ .'(), encountered an invalid index!');
                return false;
            }

            if (!array_key_exists('model', $index) ||
                !array_key_exists('idx', $index) ||
                !array_key_exists('guid', $index)
            ) {
                static::raiseError(__METHOD__ .'(), index misses mandatory parameters!');
                return false;
            }

            try {
                $index_model = new $index['model'](array(
                    FIELD_IDX => $index['idx'],
                    FIELD_GUID => $index['guid'],
                ));
            } catch (\Exception $e) {
                static::raiseErrror(__METHOD__ ."(), failed to load ${index['model']}!");
                return false;
            }

            array_push($indices_models, $index_model);
        }

        return $indices_models;
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
            $properties = new \Mtlda\Models\DocumentPropertiesModel(array(
                'idx' => $this->getIdx(),
                'guid' => $this->getGuid()
            ));
        } catch (\Exception $e) {
            return false;
        }

        $this->properties = $properties;


        if (!$this->properties->hasItems()) {
            return false;
        }

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

        if (!isset($this->indices) || empty($this->indices)) {
            static::raiseError(__METHOD__ .'(), indices not available!');
            return false;
        }

        if (!$this->indices->delete()) {
            static::raiseError(get_class($this->indices) .'::delete() returned false!');
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
            static::raiseError(get_class($properties) .'::delete() returned false!');
            return false;
        }

        return true;
    }

    public function getFileNameExtension()
    {
        if (($file_name = $this->getFileName()) === false) {
            static::raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name) || !is_string($file_name)) {
            static::raiseError(__CLASS__ ."::getFileName() returned invalid data!");
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
        if (!$this->hasFileName()) {
            static::raiseError(__CLASS__ .'::hasFileName() returned false!');
            return false;
        }

        if (($file_name = $this->getFileName()) === false) {
            static::raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name) || !is_string($file_name)) {
            static::raiseError(__CLASS__ ."::getFileName() returned invalid data!");
            return false;
        }

        if (($base = pathinfo($file_name, PATHINFO_FILENAME)) === false) {
            return false;
        }

        return $base;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
