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

class DocumentModel extends DefaultModel
{
    public $table_name = 'archive';
    public $column_name = 'document';
    public $fields = array(
            'document_idx' => 'integer',
            'document_guid' => 'string',
            'document_file_name' => 'string',
            'document_file_hash' => 'string',
            'document_file_size' => 'integer',
            'document_signing_icon_position' => 'integer',
            'document_time' => 'integer',
            'document_version' => 'integer',
            'document_derivation' => 'integer',
            'document_derivation_guid' => 'string',
            );
    public $avail_items = array();
    public $items = array();
    public $descendants = array();
    private $working_directory = MTLDA_BASE."/data/working";
    private $archive_directory = MTLDA_BASE."/data/archive";

    public function __construct($id = null, $guid = null)
    {
        global $mtlda, $db;

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

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError("Failed to prepare query");
            return false;
        }

        if (!$db->execute($sth, $arr_query)) {
            $mtlda->raiseError("Failed to execute query");
            return false;
        }

        if (!($row = $sth->fetch())) {
            $mtlda->raiseError("Unable to find archive item with guid value {$guid}");
            return false;
        }

        if (!isset($row->document_idx) || empty($row->document_idx)) {
            $mtlda->raiseError("Unable to find archive item with guid value {$guid}");
            return false;
        }

        $db->freeStatement($sth);

        parent::__construct($row->document_idx);

        return true;
    }

    public function postLoad()
    {
        global $db;

        $idx_field = $this->column_name ."_idx";
        $guid_field = $this->column_name ."_guid";
        $version_field = $this->column_name ."_version";

        // check if there are descendants of this item available
        if (!isset($this->$version_field) || $this->$version_field != 1) {
            return true;
        }

        $sth = $db->prepare(
            "SELECT
                    document_idx,
                    document_guid
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                document_derivation LIKE ?
                AND
                document_derivation_guid LIKE ?"
        );

        if (!$sth) {
            $mtlda->raiseError("Failed to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($this->$idx_field, $this->$guid_field))) {
            $mtlda->raiseError("Failed to execute query");
            return false;
        }

        while ($row = $sth->fetch()) {
            $this->descendants[] = array(
                'idx' => $row->document_idx,
                'guid' => $row->document_guid
            );
        }

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

        if (!isset($this->document_file_name)) {
            $mtlda->raiseError("document_file_name is not set!");
            return false;
        }

        if (!isset($this->document_file_hash)) {
            $mtlda->raiseError("document_file_hash is not set!");
            return false;
        }

        $fqpn = $this->working_directory .'/'. $this->document_file_name;

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
        if (!isset($this->document_guid)) {
            return false;
        }

        return $this->document_guid;
    }

    public function getFileHash()
    {
        if (!isset($this->document_file_hash)) {
            return false;
        }

        return $this->document_file_hash;
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

        if (!isset($this->descendants) && !is_array($this->descendants)) {
            $mtlda->raiseError("descendants are not set!");
            return false;
        }

        if (empty($this->descendants)) {
            return true;
        }

        foreach ($this->descendants as $descendant) {

            if (!isset($descendant['idx'], $descendant['guid'])) {
                $mtlda->raiseError("descendant is invalid!");
                return false;
            }

            $child = new DocumentModel($descendant['idx'], $descendant['guid']);

            if (!$child) {
                $mtlda->raiseError("Unable to find document with guid value {$descendant['guid']}");
                return false;
            }

            if (!$child->delete()) {
                $mtlda->raiseError("descendant {$descendant['guid']} delete() returned false!");
                return false;
            }
        }

        try {
            $audit->log(
                $this->document_file_name,
                "deleted",
                "archive",
                $this->document_guid
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

    public function refresh($path)
    {
        global $mtlda;

        $fqpn = $this->archive_directory .'/'. $path .'/'. $this->document_file_name;

        if (!file_exists($fqpn)) {
            $mtlda->raiseError("File {$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $mtlda->raiseError("File {$fqpn} is not readable!");
            return false;
        }

        if (($hash = sha1_file($fqpn)) === false) {
            $mtdla->raiseError(__TRAIT__ ." SHA1 value of {$fqpn} can not be calculated!");
            return false;
        }

        if (empty($hash)) {
            $mtlda->raiseError(__TRAIT__ ." sha1_file() returned an empty hash value!");
            return false;
        }

        if (($size = filesize($fqpn)) === false) {
            $mtdla->raiseError(__TRAIT__ ." filesize of {$fqpn} is not available!");
            return false;
        }

        if (empty($size) || !is_numeric($size) || ($size <= 0)) {
            $mtlda->raiseError(__TRAIT__ ." fizesize of {$fqpn} is invalid!");
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
        $version = $this->column_name.'_version';

        if ($this->$version != 1) {
            return array();
        }

        if (!isset($this->descendants) || !is_array($this->descendants)) {
            return false;
        }

        if (empty($this->descendants)) {
            return array();
        }

        $ary_descendants = array();

        foreach ($this->descendants as $descendant) {

            $ary_descendants[] = new DocumentModel(
                $descendant['idx'],
                $descendant['guid']
            );
        }

        return $ary_descendants;
    }

    public function postSave()
    {
        global $mtlda, $audit;

        $json_ary = array(
            'file_name' => $this->document_file_name,
            'file_size' => $this->document_file_size,
            'file_hash' => $this->document_file_hash,
        );

        if (
            isset($this->document_derivation_guid) &&
            !empty($this->document_derivation_guid) &&
            $mtlda->isValidGuidSyntax($this->document_derivation_guid)
        ) {
            $json_ary['derivation_guid'] = $this->document_derivation_guid;
        }

        $json_str = json_encode($json_ary);

        if (!$json_str) {
            $mtlda->raiseError("json_encode() returned false!");
            return false;
        }

        try {
            $audit->log(
                $json_str,
                "saving",
                "archive",
                $this->document_guid
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
