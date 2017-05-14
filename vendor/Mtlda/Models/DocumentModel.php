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

class DocumentModel extends DefaultModel
{
    protected static $model_table_name = 'archive';
    protected static $model_column_prefix = 'document';
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
        'description' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'file_name' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'file_hash' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'file_size' => array(
            FIELD_TYPE => FIELD_STRING,
        ),
        'signing_icon_position' => array(
            FIELD_TYPE => FIELD_INT,
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
        'version' => array(
            FIELD_TYPE => FIELD_INT,
        ),
        'derivation_guid' => array(
            FIELD_TYPE => FIELD_GUID,
        ),
        'signed_copy' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
        'deleted' => array(
            FIELD_TYPE => FIELD_YESNO,
        ),
    );

    protected $descendants = array();
    protected $keywords;
    protected $indices;
    protected $properties;

    protected function __init()
    {
        $this->permitRpcUpdates(true);
        $this->addVirtualField("keywords");
        $this->addRpcEnabledField('title');
        $this->addRpcEnabledField('description');
        $this->addRpcEnabledField('file_name');
        $this->addRpcEnabledField('custom_date');
        $this->addRpcEnabledField('expiry_date');
        $this->addRpcEnabledField('keywords');
        $this->addRpcAction('delete');
        return true;
    }

    protected function postLoad()
    {
        if (!$this->loadDescendants()) {
            static::raiseError(__CLASS__ .'::loadDescendants() returned false!');
            return false;
        }

        return true;
    }

    protected function loadDescendants()
    {
        global $db;

        $guid_field = static::column('guid');

        $sql = sprintf(
            "SELECT
                    document_idx,
                    document_guid
            FROM
                TABLEPREFIX%s
            WHERE
                document_derivation_guid LIKE ?",
            static::$model_table_name
        );

        if (($sth = $db->prepare($sql)) === false) {
            static::raiseError("Failed to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($this->$guid_field))) {
            static::raiseError("Failed to execute query");
            return false;
        }

        while ($row = $sth->fetch()) {
            try {
                $this->descendants[] = new \Mtlda\Models\DocumentModel(array(
                    'idx' => $row->document_idx,
                    'guid' => $row->document_guid
                ));
            } catch (\Exception $e) {
                static::raiseError("Failed to load DocumentModel({$id}, {$guid})!");
                return false;
            }
        }

        $db->freeStatement($sth);
        return true;
    }

    public function verify()
    {
        if (!$this->hasFileName()) {
            static::raiseError(__METHOD__ .'(), file_name field is not set!');
            return false;
        }

        if (!$this->hasFileHash()) {
            static::raiseError(__METHOD__ .'(), file_hash field is not set!');
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

        if (($document_hash = $this->getFileHash()) === false) {
            static::raiseError(__CLASS__ .'::getFileHash() returned false!');
            return false;
        }

        if ($document_hash != $file_hash) {
            static::raiseError(__METHOD__ ."(), Hash value of ${file} does not match!");
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

        if (($value = $this->getFieldValue('file_hash')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
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

        if (($value = $this->getFieldValue('file_size')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
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

        if (($value = $this->getFieldValue('file_name')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
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

        if (!$this->setFieldValue('file_name', $file_name)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function getFilePath()
    {
        if (!$this->hasGuid()) {
            static::raiseError(__CLASS__ .'::hasGuid() returned false!');
            return false;
        }

        if (!$this->hasFileName()) {
            static::raiseError(__CLASS__ .'::hasFileName() returned false!');
            return false;
        }

        if (($guid = $this->getGuid()) === false) {
            static::raiseError(__CLASS__ ."::getGuid() returned false!");
            return false;
        }

        if (($dir_name = $this->generateDirectoryName($guid)) === false) {
            static::raiseError(__CLASS__ ."::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($dir_name) || empty($dir_name) || !is_string($dir_name)) {
            static::raiseError(__METHOD__ .'(), unable to get directory name!');
            return false;
        }

        if (($file_name = $this->getFileName()) === false) {
            static::raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name) || !is_string($file_name)) {
            static::raiseError(__METHOD__ .'(), unable to get file name!');
            return false;
        }

        $fqpn = \Mtlda\Controllers\DefaultController::ARCHIVE_DIRECTORY;
        $fqpn.= '/'. $dir_name .'/';
        $fqpn.= $file_name;

        return $fqpn;
    }

    protected function generateDirectoryName($guid)
    {
        $dir_name = "";

        if (empty($guid)) {
            static::raiseError("guid is empty!");
            return false;
        }

        for ($i = 0; $i < strlen($guid); $i+=2) {
            $guid_part = substr($guid, $i, 2);

            if ($guid_part === false) {
                static::raiseError("substr() returned false!");
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

    protected function preDelete()
    {
        global $db;

        if (!$this->deleteAllDescendants()) {
            static::raiseError(__CLASS__ .'::deleteAllDescendants() returned false!');
            return false;
        }

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

        if (!$this->deleteFile()) {
            static::raiseError(__CLASS__ .'::deleteFile() returned false!');
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

        try {
            $audit->log(
                $this->getFileName(),
                "deleted",
                "archive",
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
            static::raiseError(__METHOD__ .'(), duplicate documents detected!');
            return false;
        }

        if (!$this->hasFileName()) {
            static::raiseError(__CLASS__ .'::hasFileName() returned false!');
            return false;
        }

        /* new document? no more action here */
        if ($this->isNew()) {
            return true;
        }

        if (!isset($this->model_init_values['file_name']) ||
            empty($this->model_init_values['file_name'])
        ) {
            return true;
        }

        if (($file_name = $this->getFileName()) === false) {
            static::raiseError(__CLASS__ .'::getFileName() returned false!');
            return false;
        }

        /* filename hasn't changed? we are done */
        if ($this->model_init_values['file_name'] === $file_name) {
            return true;
        }

        if ($this->hasVersion() && $this->getVersion() === 1) {
            static::raiseError(__METHOD__ .'(), changing the filename of the root document is not allowed!');
            return false;
        }

        if (($fqpn = $this->getFilePath()) === false) {
            static::raiseError(__CLASS__ ."::getFilePath() returned false!");
            return false;
        }

        $path = dirname($fqpn);

        if (empty($path)) {
            static::raiseError("why is \$path empty?");
            return false;
        }

        $old_file = $path .'/'. basename($this->model_init_values['file_name']);
        $new_file = $path .'/'. basename($file_name);

        if (file_exists($new_file)) {
            static::raiseError("Unable to rename {$old_file} to {$new_file} - destination already exists!");
            return false;
        }

        if (rename($old_file, $new_file) === false) {
            static::raiseError("rename() returned false!");
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

        if (!$this->setFieldValue('file_size', $size)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        if (!$this->setFieldValue('file_hash', $hash)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        if (!$this->setFieldValue('time', time())) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        if (!$this->save()) {
            return false;
        }

        return true;
    }

    public function getDescendants()
    {
        if (!isset($this->descendants) || !is_array($this->descendants)) {
            return false;
        }

        if (empty($this->descendants)) {
            return array();
        }

        return $this->descendants;
    }

    public function hasDescendants()
    {
        if (!isset($this->descendants) ||
            empty($this->descendants) ||
            !is_array($this->descendants) ||
            count($this->descendants) < 1) {
            return false;
        }

        return true;
    }

    protected function postSave()
    {
        global $mtlda, $audit;

        if (!$this->hasFileName() ||
            !$this->hasFileSize() ||
            !$this->hasFileHash() ||
            !$this->hasGuid()
        ) {
            static::raiseError(__METHOD__ .'(), missing some document details!');
            return false;
        }

        $json_ary = array(
            'file_name' => $this->getFileName(),
            'file_size' => $this->getFileSize(),
            'file_hash' => $this->getFileHash()
        );

        if ($this->hasDerivationGuid()) {
            if (($derivation_guid = $this->getDerivationGuid()) === false) {
                static::raiseError(__CLASS__ .'::getDerivationGuid() returned false!');
                return false;
            }

            if (!$mtlda->isValidGuidSyntax($derivation_guid)) {
                static::raiseError(get_class($mtlda) .'::isValidGuidSyntax() returned false!');
                return false;
            }

            $json_ary['derivation_guid'] = $derivation_guid;
        }

        $json_str = json_encode($json_ary);

        if (!$json_str) {
            static::raiseError("json_encode() returned false!");
            return false;
        }

        try {
            $audit->log(
                $json_str,
                "saving",
                "archive",
                $this->getGuid()
            );
        } catch (\Exception $e) {
            static::raiseError(get_class($audit) .':log() returned false!');
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

        if (!$this->hasIdx()) {
            static::raiseError(__CLASS__ .'::hasIdx() returned false!');
            return false;
        }

        if (($document_idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        foreach ($values as $value) {
            $value = trim($value);
            if (!is_numeric($value)) {
                static::raiseError(__METHOD__ .'(), value found that is not a number!');
                return false;
            }

            try {
                $keyword = new \Mtlda\Models\KeywordAssignmentModel;
            } catch (\Exception $e) {
                static::raiseError("Failed to load KeywordAssignmentModel!");
                return false;
            }

            if (!$keyword->setArchive($document_idx)) {
                static::raiseError("KeywordAssignmentModel::setArchive() returned false!");
                return false;
            }

            if (!$keyword->setKeyword($value)) {
                static::raiseError("KeywordAssignmentModel::setKeyword() returned false!");
                return false;
            }

            if (!$keyword->save()) {
                static::raiseError("KeywordAssignmentModel::save() returned false!");
                return false;
            }
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

        if (($value = $this->getFieldValue('description')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setDescription($description)
    {
        global $db;

        if (!is_string($description)) {
            static::raiseError("A string must be provided as parameter to this method");
            return false;
        }

        if (!$this->hasIdx()) {
            static::raiseError(__CLASS__ .'::hasIdx() returned false!');
            return false;
        }

        if (($document_idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        $sth = $db->prepare(
            "UPDATE
                TABLEPREFIXarchive
            SET
                document_description=?
            WHERE
                document_idx LIKE ?"
        );

        if (!$sth) {
            static::raiseError("Unable to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($description, $document_idx))) {
            static::raiseError("Failed to execute query!");
            $db->freeStatement($sth);
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    protected function preClone()
    {
        if (!$this->hasVersion()) {
            if (!$this->setFieldValue('version', 1)) {
                static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
                return false;
            }
            return true;
        }

        if (($latest = $this->getLastestDocumentVersionNumber()) === false) {
            static::raiseError(__CLASS__ .'::getLastestDocumentVersionNumber() returned false');
            return false;
        }

        if (empty($latest) || !is_numeric($latest)) {
            static::raiseError(__CLASS__ .'::getLastestDocumentVersionNumber() returned an invalid number!');
            return false;
        }

        $latest+=1;

        if (!$this->setFieldValue('version', $latest)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    protected function afterClone(&$src, &$clone)
    {
        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load StorageController!');
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

        if ($src_file === $dst_file) {
            static::raiseError(__METHOD__ .'(), source- and destination-file are the same!');
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

    public function getLastestDocumentVersionNumber()
    {
        global $db;

        if (!$this->hasIdx()) {
            static::raiseError(__CLASS__ .'::hasIdx() returned false!');
            return false;
        }

        if (!$this->hasGuid()) {
            static::raiseError(__CLASS__ .'::hasGuid() returned false!');
            return false;
        }

        if (($document_idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        if (($document_guid = $this->getGuid()) === false) {
            static::raiseError(__CLASS__ .'::getGuid() returned false!');
            return false;
        }

        $sth = $db->prepare(sprintf(
            "SELECT
                    MAX(document_version) as max_version
            FROM
                TABLEPREFIX%s
            WHERE
                (
                    document_idx LIKE ?
                        AND
                    document_guid LIKE ?
                )
                OR
                (
                    document_derivation_guid LIKE ?
                )",
            self::$model_table_name
        ));

        if (!$sth) {
            static::raiseError(__METHOD__ .'(), failed to prepare SQL query!');
            return false;
        }

        if (!$db->execute($sth, array(
                $document_idx,
                $document_guid,
                $document_guid
            ))
        ) {
            static::raiseError(__METHOD__ .'(), failed to execute query!');
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $db->freeStatement($sth);

        if ($rows === false) {
            static::raiseError("PDO::fetchAll() returned false!");
            return false;
        }

        if (!is_array($rows)) {
            static::raiseError("PDO::fetchAll() has not returned an array!");
            return false;
        }

        if (count($rows) > 1) {
            static::raiseError("Strangly more than one result has been returned by query!");
            return false;
        }

        if (!isset($rows[0]['max_version']) || empty($rows[0]['max_version'])) {
            static::raiseError("failed to retrieve latest version number!");
            return false;
        };

        if (!is_numeric($rows[0]['max_version'])) {
            static::raiseError("\$max_version returned from database isn't a number!");
            return false;
        }

        return $rows[0]['max_version'];
    }

    protected function removeAssignedKeywords()
    {
        global $db;

        if (!$this->hasIdx()) {
            static::raiseError(__CLASS__ .'::hasIdx() returned false!');
            return false;
        }

        if (($document_idx = $this->getIdx()) === false) {
            static::raiseError(__CLASS__ .'::getIdx() returned false!');
            return false;
        }

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_archive_idx
            LIKE
                ?"
        );

        if (!$sth) {
            static::raiseError("Unable to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($document_idx))) {
            static::raiseError("Unable to execute query!");
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    public function delete()
    {
        if (!$this->isNoDeleteEnabled()) {
            return parent::delete();
        }

        if (!$this->deleteAllDescendants()) {
            static::raiseError(__CLASS__ .'::deleteAllDescendants() returned false!');
            return false;
        }

        if (!$this->setDeleted(true)) {
            static::raiseError(__CLASS__ .'::setDeleted() returned false!');
            return false;
        }

        if (!$this->save()) {
            static::raiseError(__CLASS__ .'::save() returned false!');
            return false;
        }

        return true;
    }

    protected function deleteFile()
    {
        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load StorageController!', false, $e);
            return false;
        }

        if (!$storage->deleteItemFile($this)) {
            static::raiseError("StorageController::deleteItemFile() returned false!");
            return false;
        }

        return true;
    }

    protected function deleteAllDescendants()
    {
        if (!$this->hasDescendants()) {
            return true;
        }

        if (($descendants = $this->getDescendants()) === false) {
            static::raiseError(__CLASS__ .'::getDescendants() returned false!');
            return false;
        }

        foreach ($descendants as $descendant) {
            if (!is_a($descendant, __CLASS__)) {
                static::raiseError(__METHOD__ .'(), received descendant is not a '. __CLASS__ .'!');
                return false;
            }
            if (!$descendant->delete()) {
                static::raiseError(get_class($descendant) .'::delete() returned false!');
                return false;
            }
        }

        return true;
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
        return true;
    }

    public function getProperties()
    {
        if (!$this->hasProperties()) {
            return false;
        }

        return $this->properties->getProperties();
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

    public function hasIndices()
    {
        if (isset($this->indices) &&
            !empty($this->indices) &&
            is_a($this->indices, 'Mtlda\Models\DocumentIndicesModel') &&
            !$this->indices->hasIndices()
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

        if (!$this->indices->hasIndices()) {
            return false;
        }

        return true;
    }

    public function getIndices()
    {
        if (!$this->hasIndices()) {
            return false;
        }

        return $this->indices->getIndices();
    }

    protected function deleteAllDocumentIndices()
    {
        if (!$this->hasIndices()) {
            return true;
        }

        if (!$this->indices->delete()) {
            static::raiseError(get_class($indices) .'::delete() returned false!');
            return false;
        }

        return true;
    }

    public function hasParent()
    {
        global $mtlda;

        if (!$this->hasDerivationGuid()) {
            return false;
        }

        return true;
    }

    public function getParent()
    {
        if (!$this->hasParent()) {
            return false;
        }

        if (!$this->hasDerivationGuid()) {
            static::raiseError(__METHOD__ .'(), neither derivation or derivation_guid are set!');
            return false;
        }

        if (($derivation_guid = $this->getDerivationGuid()) === false) {
            static::raiseError(__CLASS__ .'::getDerivationGuid() returned false!');
            return false;
        }

        try {
            $parent = new \Mtlda\Models\DocumentModel(array(
                'guid' => $derivation_guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load DocumentModel!', false, $e);
            return false;
        }

        return $parent;
    }

    public function hasVersion()
    {
        if (!$this->hasFieldValue('version')) {
            return false;
        }

        return true;
    }

    public function getVersion()
    {
        if (!$this->hasVersion()) {
            static::raiseError(__CLASS__ .'::hasVersion() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('version')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setVersion($number)
    {
        if (!isset($number) || empty($number) || !is_numeric($number)) {
            static::raiseError(__METHOD__ .'(), $number parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('version', $number)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
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
            static::raiseError(__METHOD__ .'(), \$date parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('custom_date', $date)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasCustomDate()
    {
        if (!$this->hasFieldValue('custom_date')) {
            return false;
        }

        if (($custom_date = $this->getFieldValue('custom_date')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() retuned false!');
            return false;
        }

        if ($custom_date === '0000-00-00') {
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

        if (($value = $this->getFieldValue('custom_date')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function getLastestVersion()
    {
        global $db;

        if (!$this->hasDescendants()) {
            return false;
        }

        if (($childs = $this->getDescendants()) === false) {
            static::raiseError(__CLASS__ .'::getDescendants() returned false!');
            return false;
        }

        if ($this->hasVersion()) {
            if (($version = $this->getVersion()) === false) {
                static::raiseError(__CLASS__ .'::getVersion() returned false!');
                return false;
            }
        } else {
            $version = 1;
        }

        foreach ($childs as $child) {
            if (!$child->hasVersion()) {
                continue;
            }

            if ($version >= $child->getVersion()) {
                continue;
            }

            $version = $child->getVersion();
            $latest = $child;
        }

        if (isset($latest) && !empty($latest)) {
            return $latest;
        }

        return $this;
    }

    public function setExpiryDate($date)
    {
        if (!isset($date) ||
            empty($date) ||
            (!is_string($date) && !is_numeric($date))
        ) {
            static::raiseError(__METHOD__ .'(), \$date parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('expiry_date', $date)) {
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

        if (($expiry_date = $this->getFieldValue('expiry_date')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() retuned false!');
            return false;
        }

        if ($expiry_date === '0000-00-00') {
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

        if (($value = $this->getFieldValue('expiry_date')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function isNoDeleteEnabled()
    {
        global $config;

        if (!$config->isDocumentNoDeleteEnabled()) {
            return false;
        }

        return true;
    }

    public function isDeleted()
    {
        if (!$this->hasFieldValue('deleted')) {
            return false;
        }

        if (($deleted = $this->getFieldValue('deleted')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($deleted !== 'Y') {
            return false;
        }

        return true;
    }

    public function setDeleted($value = true)
    {
        if (!isset($value) ||
            !is_bool($value)
        ) {
            static::raiseError(__METHOD__ .'(), $value is invalid!');
            return false;
        }

        if ($value === true) {
            $deleted = 'Y';
        } else {
            $deleted = 'N';
        }

        if (!$this->setFieldValue('deleted', $deleted)) {
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

        if (($value = $this->getFieldValue('title')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
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
                akd_archive_idx LIKE ?"
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

        if (($value = $this->getFieldValue('signing_icon_position')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
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

        if (($value = $this->getFieldValue('time')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setTime($timestamp)
    {
        if (!isset($timestamp) || empty($timestamp) || !is_numeric($timestamp)) {
            static::raiseError(__METHOD__ .'(), $timestamp parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('time', $timestamp)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function isSignedCopy()
    {
        if (!$this->hasFieldValue('signed_copy')) {
            return false;
        }

        if (($signed_copy = $this->getFieldValue('signed_copy')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        if ($signed_copy !== 'Y') {
            return false;
        }

        return true;
    }

    public function setSignedCopy($state)
    {
        if (!isset($state) || !is_bool($state)) {
            static::raiseError(__METHOD__ .'(), $state parameter is invalid!');
            return false;
        }

        if (!$state) {
            $signed_copy = 'N';
        } else {
            $signed_copy = 'Y';
        }

        if (!$this->setFieldValue('signed_copy', $signed_copy)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
            return false;
        }

        return true;
    }

    public function hasDerivationGuid()
    {
        if (!$this->hasFieldValue('derivation_guid')) {
            return false;
        }

        return true;
    }

    public function getDerivationGuid()
    {
        if (!$this->hasDerivationGuid()) {
            static::raiseError(__CLASS__ .'::hasDerivationGuid() returned false!');
            return false;
        }

        if (($value = $this->getFieldValue('derivation_guid')) === false) {
            static::raiseError(__CLASS__ .'::getFieldValue() returned false!');
            return false;
        }

        return $value;
    }

    public function setDerivationGuid($guid)
    {
        if (!isset($guid) || empty($guid) || !is_string($guid)) {
            static::raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        if (!$this->setFieldValue('derivation_guid', $guid)) {
            static::raiseError(__CLASS__ .'::setFieldValue() returned false!');
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

    public function verifySignature()
    {
        global $config;

        if (!$config->isPdfSignatureVerificationEnabled()) {
            static::raiseError(__METHOD__ .'(), pdf-signature-verification is not enabled!', true);
            return false;
        }

        if (($path = $this->getFilePath()) === false) {
            static::raiseError(__CLASS__ .'::getFilePath() returned false!', true);
            return false;
        }

        try {
            $pdfsig = new \Mtlda\Controllers\PdfSignatureController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PdfSignatureController', true, $e);
            return false;
        }

        if (($result = $pdfsig->verify($path)) === false) {
            static::raiseError(get_class($pdfsig) .'::verify() returned false!', true);
            return false;
        }

        if (is_null($result)) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
