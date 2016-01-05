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

class DocumentModel extends DefaultModel
{
    protected $table_name = 'archive';
    protected $column_name = 'document';
    protected $fields = array(
            'document_idx' => 'integer',
            'document_guid' => 'string',
            'document_title' => 'string',
            'document_description' => 'string',
            'document_file_name' => 'string',
            'document_file_hash' => 'string',
            'document_file_size' => 'integer',
            'document_signing_icon_position' => 'integer',
            'document_time' => 'timestamp',
            'document_custom_date' => 'date',
            'document_expiry_date' => 'date',
            'document_version' => 'integer',
            'document_derivation' => 'integer',
            'document_derivation_guid' => 'string',
            'document_signed_copy' => 'string',
            'document_deleted' => 'string',
            );
    protected $avail_items = array();
    protected $items = array();
    protected $descendants = array();
    protected $keywords;
    protected $indices;
    protected $properties;

    public function __construct($id = null, $guid = null)
    {
        global $db;

        // are we creating a new item?
        if (!isset($id) && !isset($guid)) {
            parent::__construct(null);
            return true;
        }

        // get $id from db
        $sql = "
            SELECT
                document_idx
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
        ";

        $arr_query = array();
        if (isset($id)) {
            $sql.= "
                document_idx LIKE ?
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
                document_guid LIKE ?
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

        if (($row = $sth->fetch()) === false ||
            !isset($row->document_idx) || empty($row->document_idx)
        ) {
            $this->raiseError(__METHOD__ ."(), unable to find archive item with guid value {$guid}!", true);
            return false;
        }

        $db->freeStatement($sth);
        parent::__construct($row->document_idx);

        if (!$this->permitRpcUpdates(true)) {
            $this->raiseError(__CLASS__ .'::permitRpcUpdates() returned false!', true);
            return false;
        }

        try {
            $this->addVirtualField("document_keywords");
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to add virtual field!', true, $e);
            return false;
        }

        try {
            $this->addRpcEnabledField('document_title');
            $this->addRpcEnabledField('document_description');
            $this->addRpcEnabledField('document_file_name');
            $this->addRpcEnabledField('document_custom_date');
            $this->addRpcEnabledField('document_expiry_date');
            $this->addRpcEnabledField('document_keywords');
            $this->addRpcAction('delete');
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed on invoking addRpcEnabledField() method!', true, $e);
            return false;
        }

        return true;
    }

    protected function postLoad()
    {
        if (!$this->loadDescendants()) {
            $this->raiseError(__CLASS__ .'::loadDescendants() returned false!');
            return false;
        }

        return true;
    }

    protected function loadDescendants()
    {
        global $db;

        $idx_field = $this->column_name ."_idx";
        $guid_field = $this->column_name ."_guid";

        $sql =
            "SELECT
                    document_idx,
                    document_guid
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                document_derivation LIKE ?
            AND
                document_derivation_guid LIKE ?";

        if (!$sth = $db->prepare($sql)) {
            $this->raiseError("Failed to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($this->$idx_field, $this->$guid_field))) {
            $this->raiseError("Failed to execute query");
            return false;
        }

        while ($row = $sth->fetch()) {
            try {
                $this->descendants[] = new DocumentModel(
                    $row->document_idx,
                    $row->document_guid
                );
            } catch (\Exception $e) {
                $this->raiseError("Failed to load DocumentModel({$id}, {$guid})!");
                return false;
            }
        }

        $db->freeStatement($sth);
        return true;
    }

    public function verify()
    {
        if (!isset($this->document_file_name)) {
            $this->raiseError("document_file_name is not set!");
            return false;
        }

        if (!isset($this->document_file_hash)) {
            $this->raiseError("document_file_hash is not set!");
            return false;
        }

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

        if (($file_hash = sha1_file($fqpn)) === false) {
            $this->raiseError("Unable to calculate SHA1 hash of file {$fqpn}!");
            return false;
        }

        if ($this->document_file_hash != $file_hash) {
            $this->raiseError("Hash value of ${file} does not match!");
            return false;
        }

        return true;
    }

    public function getFileHash()
    {
        if (!isset($this->document_file_hash)) {
            return false;
        }

        return $this->document_file_hash;
    }

    public function getFileSize()
    {
        if (!isset($this->document_file_size)) {
            return false;
        }

        return $this->document_file_size;
    }

    public function setFileSize($file_size)
    {
        if (!isset($file_size) || empty($file_size) || !is_numeric($file_size)) {
            $this->raiseError(__METHOD__ .'(), $file_size parameter is invalid!');
            return false;
        }

        $this->document_file_size = $file_size;
        return false;
    }

    public function getFileName()
    {
        if (!isset($this->document_file_name)) {
            return false;
        }

        return $this->document_file_name;
    }

    public function setFileName($file_name)
    {
        if (!isset($file_name) || empty($file_name) || !is_string($file_name)) {
            $this->raiseError(__METHOD__ .'(), $file_name parameter is invalid!');
            return false;
        }

        if (strpos($file_name, '/') || strpos($file_name, '\\') || strpos($file_name, '..')) {
            $this->raiseError(__METHOD__ .'(), $file_name parameter contains forbidden characters!');
            return false;
        }

        $this->document_file_name = basename($file_name);
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
            $this->raiseError("Unable to get directory name!");
            return false;
        }

        if (!($file_name = $this->getFileName())) {
            $this->raiseError(__CLASS__ ."::getFileName() returned false!");
            return false;
        }

        if (!isset($file_name) || empty($file_name)) {
            $this->raiseError("Unable to get file name!");
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
            $this->raiseError("guid is empty!");
            return false;
        }

        for ($i = 0; $i < strlen($guid); $i+=2) {
            $guid_part = substr($guid, $i, 2);

            if ($guid_part === false) {
                $this->raiseError("substr() returned false!");
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

    public function delete()
    {
        if (!$this->isNoDeleteEnabled()) {
            return parent::delete();
        }

        if (!$this->setDeleted(true)) {
            $this->raiseError(__CLASS__ .'::setDeleted() returned false!');
            return false;
        }

        if (!$this->save()) {
            $this->raiseError(__CLASS__ .'::save() returned false!');
            return false;
        }

        if (!$this->hasDescendants()) {
            return true;
        }

        if (!$this->deleteAllDescendants()) {
            $this->raiseError(__CLASS__ .'::deleteAllDescendants() returned false!');
            return false;
        }

        return true;
    }

    protected function preDelete()
    {
        global $db;

        if ($this->hasDescendants()) {
            if (!$this->deleteAllDescendants()) {
                $this->raiseError(__CLASS__ .'::deleteAllDescendants() returned false!');
                return false;
            }
        }

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

        if ($this->isNoDeleteEnabled()) {
            return true;
        }

        if (!$this->deleteFile()) {
            $this->raiseError(__CLASS__ .'::deleteFile() returned false!');
            return false;
        }

        return true;
    }

    protected function postDelete()
    {
        global $audit;

        try {
            $audit->log(
                $this->document_file_name,
                "deleted",
                "archive",
                $this->document_guid
            );
        } catch (\Exception $e) {
            $this->raiseError("AuditController::log() returned false!");
            return false;
        }

        return true;
    }

    protected function preSave()
    {
        if ($this->isDuplicate()) {
            $this->raiseError("Duplicated record detected!");
            return false;
        }

        if (!isset($this->document_file_name) ||
            empty($this->document_file_name)
        ) {
            $this->raiseError("\$document_file_name must not be empty!");
            return false;
        }

        /* new document? no more action here */
        if (!isset($this->document_idx) && !isset($this->id)) {
            return true;
        }

        if (!isset($this->init_values['document_file_name']) ||
            empty($this->init_values['document_file_name'])
        ) {
            return true;
        }

        /* filename hasn't changed? we are done */
        if ($this->init_values['document_file_name'] == $this->document_file_name) {
            return true;
        }

        if ($this->document_version == 1) {
            $this->raiseError("Change the filename of the root document is not allowed!");
            return false;
        }

        if (!$fqpn = $this->getFilePath()) {
            $this->raiseError(__CLASS__ ."::getFilePath() returned false!");
            return false;
        }

        $path = dirname($fqpn);

        if (empty($path)) {
            $this->raiseError("why is \$path empty?");
            return false;
        }

        $old_file = $path .'/'. basename($this->init_values['document_file_name']);
        $new_file = $path .'/'. basename($this->document_file_name);

        if (file_exists($new_file)) {
            $this->raiseError("Unable to rename {$old_file} to {$new_file} - destination already exists!");
            return false;
        }

        if (rename($old_file, $new_file) === false) {
            $this->raiseError("rename() returned false!");
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

        $this->document_file_size = $size;
        $this->document_file_hash = $hash;
        $this->document_time = time();

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

        $json_ary = array(
            'file_name' => $this->document_file_name,
            'file_size' => $this->document_file_size,
            'file_hash' => $this->document_file_hash,
        );

        if (isset($this->document_derivation_guid) &&
            !empty($this->document_derivation_guid) &&
            $mtlda->isValidGuidSyntax($this->document_derivation_guid)
        ) {
            $json_ary['derivation_guid'] = $this->document_derivation_guid;
        }

        $json_str = json_encode($json_ary);

        if (!$json_str) {
            $this->raiseError("json_encode() returned false!");
            return false;
        }

        try {
            $audit->log(
                $json_str,
                "saving",
                "archive",
                $this->document_guid
            );
        } catch (\Exception $e) {
            $queueitem->delete();
            $this->raiseError("AuditController:log() returned false!");
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

            if (!$keyword->setArchive($this->document_idx)) {
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

    public function hasDescription()
    {
        if (!isset($this->document_description) || empty($this->document_description)) {
            return false;
        }

        return true;
    }

    public function getDescription()
    {
        if (!$this->hasDescription()) {
            $this->raiseError(__CLASS__ .'::hasDescription() returned false!');
            return false;
        }

        return $this->document_description;
    }

    public function setDescription($description)
    {
        global $db;

        if (!is_string($description)) {
            $this->raiseError("A string must be provided as parameter to this method");
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
            $this->raiseError("Unable to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($description, $this->document_idx))) {
            $this->raiseError("Failed to execute query!");
            $db->freeStatement($sth);
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    protected function preClone()
    {
        if (!isset($this->document_version) || empty($this->document_version)) {
            $this->document_version = 1;
            return true;
        }

        if (!$latest = $this->getLastestDocumentVersionNumber()) {
            $this->raiseError(__CLASS__ .'::getLastestDocumentVersionNumber() returned false');
            return false;
        }

        if (empty($latest)) {
            $this->raiseError(__CLASS__ .'::getLastestDocumentVersionNumber() returned an invalid number!');
            return false;
        }

        $latest+=1;
        $this->document_version = $latest;
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
            $this->raiseError(__METHOD__ .', unable to retrieve source objects full qualified path name!');
            return false;
        }

        if (!$dst_file = $this->getFilePath()) {
            $this->raiseError(__CLASS__ .'::getFilePath() returned false!');
            return false;
        }

        if (!$storage->createDirectoryStructure(dirname($dst_file))) {
            $this->raiseError("StorageController::createDirectoryStructure() returned false!");
            return false;
        }

        if (!$storage->copyFile($src_file, $dst_file)) {
            $this->raiseError("StorageController::copyFile() returned false!");
            return false;
        }

        return true;
    }

    public function getLastestDocumentVersionNumber()
    {
        global $db;

        if (!isset($this->document_idx) || empty($this->document_idx)) {
            $this->raiseError("Unable to lookup latest document version without known \$document_idx");
            return false;
        }

        if (!isset($this->document_guid) || empty($this->document_guid)) {
            $this->raiseError("Unable to lookup latest document version without known \$document_guid");
            return false;
        }

        $sth = $db->prepare(
            "SELECT
                    MAX(document_version) as max_version
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                (
                    document_idx LIKE ?
                        AND
                    document_guid LIKE ?
                )
                OR
                (
                    document_derivation LIKE ?
                        AND
                    document_derivation_guid LIKE ?
                )"
        );

        if (!$sth) {
            $this->raiseError("Failed to prepare query");
            return false;
        }

        if (!$db->execute($sth, array(
                $this->document_idx,
                $this->document_guid,
                $this->document_idx,
                $this->document_guid
            ))
        ) {
            $this->raiseError("Failed to execute query");
            return false;
        }

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
        $db->freeStatement($sth);

        if ($rows === false) {
            $this->raiseError("PDO::fetchAll() returned false!");
            return false;
        }

        if (!is_array($rows)) {
            $this->raiseError("PDO::fetchAll() has not returned an array!");
            return false;
        }

        if (count($rows) > 1) {
            $this->raiseError("Strangly more than one result has been returned by query!");
            return false;
        }

        if (!isset($rows[0]['max_version']) || empty($rows[0]['max_version'])) {
            $this->raiseError("failed to retrieve latest version number!");
            return false;
        };

        if (!is_numeric($rows[0]['max_version'])) {
            $this->raiseError("\$max_version returned from database isn't a number!");
            return false;
        }

        return $rows[0]['max_version'];
    }

    protected function removeAssignedKeywords()
    {
        global $db;

        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIXassign_keywords_to_document
            WHERE
                akd_archive_idx
            LIKE
                ?"
        );

        if (!$sth) {
            $this->raiseError("Unable to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($this->document_idx))) {
            $this->raiseError("Unable to execute query!");
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    protected function deleteFile()
    {
        // load StorageController
        $storage = new \Mtlda\Controllers\StorageController;

        if (!$storage) {
            $this->raiseError("unable to load StorageController!");
            return false;
        }

        if (!$storage->deleteItemFile($this)) {
            $this->raiseError("StorageController::deleteItemFile() returned false!");
            return false;
        }

        return true;
    }

    protected function deleteAllDescendants()
    {
        if (!$this->hasDescendants()) {
            return true;
        }

        foreach ($this->getDescendants() as $descendant) {
            if (!$descendant->delete()) {
                $this->raiseError(get_class($descendant) .'::delete() returned false!');
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
            $this->raiseError(get_class($indices) .'::delete() returned false!');
            return false;
        }

        return true;
    }

    public function hasParent()
    {
        global $mtlda;

        if (!isset($this->document_derivation) ||
            empty($this->document_derivation) ||
            !$mtlda->isValidId($this->document_derivation) ||
            !isset($this->document_derivation_guid) ||
            empty($this->document_derivation_guid) ||
            !$mtlda->isValidGuidSyntax($this->document_derivation_guid)
        ) {
            return false;
        }

        return true;
    }

    public function getParent()
    {
        if (!$this->hasParent()) {
            return false;
        }

        try {
            $parent = new \Mtlda\Models\DocumentModel(
                $this->document_derivation,
                $this->document_derivation_guid
            );
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load DocumentModel!');
            return false;
        }

        return $parent;
    }

    public function hasVersion()
    {
        if (!isset($this->document_version) || empty($this->document_version)) {
            return false;
        }

        return true;
    }

    public function getVersion()
    {
        if (!$this->hasVersion()) {
            $this->raiseError(__CLASS__ .'::hasVersion() returned false!');
            return false;
        }

        return $this->document_version;
    }

    public function setVersion($number)
    {
        if (!isset($number) || empty($number) || !is_numeric($number)) {
            $this->raiseError(__METHOD__ .'(), $number parameter is invalid!');
            return false;
        }

        $this->document_version = $number;
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

        $this->document_custom_date = $date;
        return true;
    }

    public function hasCustomDate()
    {
        if (!isset($this->document_custom_date) ||
            empty($this->document_custom_date) ||
            $this->document_custom_date == '0000-00-00'
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

        return $this->document_custom_date;
    }

    public function getLastestVersion()
    {
        global $db;

        if (!$this->hasDescendants()) {
            return false;
        }

        if (($childs = $this->getDescendants()) === false) {
            $this->raiseError(__CLASS__ .'::getDescendants() returned false!');
            return false;
        }

        if (($version = $this->getVersion()) === false) {
            $this->raiseError(__CLASS__ .'::getVersion() returned false!');
            return false;
        }

        foreach ($childs as $child) {
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
            $this->raiseError(__METHOD__ .'(), \$date parameter is invalid!');
            return false;
        }

        $this->document_expiry_date = $date;
        return true;
    }

    public function hasExpiryDate()
    {
        if (!isset($this->document_expiry_date) ||
            empty($this->document_expiry_date) ||
            $this->document_expiry_date == '0000-00-00'
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

        return $this->document_expiry_date;
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
        if (!isset($this->document_deleted) || empty($this->document_deleted)) {
            return false;
        }

        if ($this->document_deleted != 'Y') {
            return false;
        }

        return true;
    }

    public function setDeleted($value = true)
    {
        if (!isset($value) ||
            !is_bool($value)
        ) {
            $this->raiseError(__METHOD__ .'(), $value is invalid!');
            return false;
        }

        if ($value == true) {
            $this->document_deleted = 'Y';
        } else {
            $this->document_deleted = 'N';
        }

        return true;
    }

    public function hasTitle()
    {
        if (!isset($this->document_title) || empty($this->document_title)) {
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

        return $this->document_title;
    }

    public function setTitle($title)
    {
        if (!isset($title) || !is_string($title)) {
            $this->raiseError(__METHOD__ .'(), $title parameter is invalid!');
            return false;
        }

        $this->document_title = $title;
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

    public function getSigningIconPosition()
    {
        if (!isset($this->document_signing_icon_position)) {
            return false;
        }

        return $this->document_signing_icon_position;
    }

    public function setSigningIconPosition($position)
    {
        if (!isset($position) || empty($position) || !is_numeric($position)) {
            $this->raiseError(__METHOD__ .'(), $position parameter is invalid!');
            return false;
        }

        $this->document_signing_icon_position = $position;
        return true;
    }

    public function getTime()
    {
        if (!isset($this->document_time)) {
            return false;
        }

        return $this->document_time;
    }

    public function setTime($timestamp)
    {
        if (!isset($timestamp) || empty($timestamp) || !is_numeric($timestamp)) {
            $this->raiseError(__METHOD__ .'(), $timestamp parameter is invalid!');
            return false;
        }

        $this->document_time = $timestamp;
        return true;
    }

    public function isSignedCopy()
    {
        if (!isset($this->document_signed_copy) ||
            empty($this->document_signed_copy) ||
            $this->document_signed_copy != 'Y'
        ) {
            return false;
        }

        return true;
    }

    public function setSignedCopy($state)
    {
        if (!isset($state) || !is_bool($state)) {
            $this->raiseError(__METHOD__ .'(), $state parameter is invalid!');
            return false;
        }

        if (!$state) {
            $this->document_signed_copy = 'N';
            return true;
        }

        $this->document_signed_copy = 'Y';
        return true;
    }

    public function hasDerivationId()
    {
        if (!isset($this->document_derivation)) {
            return false;
        }

        return $this->document_derivation;
    }

    public function getDerivationId()
    {
        if (!$this->hasDerivationId()) {
            $this->raiseError(__CLASS__ .'::hasDerivationId() returned false!');
            return false;
        }

        return $this->document_derivation;
    }

    public function setDerivationId($idx)
    {
        if (!isset($idx) || empty($idx) || !is_numeric($idx)) {
            $this->raiseError(__METHOD__ .'(), $idx parameter is invalid!');
            return false;
        }

        $this->document_derivation = $idx;
        return true;
    }

    public function hasDerivationGuid()
    {
        if (!isset($this->document_derivation_guid)) {
            return false;
        }

        return $this->document_derivation_guid;
    }

    public function getDerivationGuid()
    {
        if (!$this->hasDerivationGuid()) {
            $this->raiseError(__CLASS__ .'::hasDerivationGuid() returned false!');
            return false;
        }

        return $this->document_derivation_guid;
    }

    public function setDerivationGuid($guid)
    {
        if (!isset($guid) || empty($guid) || !is_string($guid)) {
            $this->raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        $this->document_derivation_guid = $guid;
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

        if (($base = pathinfo($file_name, PATHINFO_FILENAME)) === false) {
            return false;
        }

        return $base;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
