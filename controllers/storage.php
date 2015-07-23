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

use MTLDA\Models;

class StorageController
{
    private $data_path = MTLDA_BASE."/data/archive";
    private $working_path = MTLDA_BASE."/data/working";
    private $nesting_depth = 5;

    public function __construct(&$item = null)
    {
        if (!isset($item) || empty($item)) {
            return true;
        }

        $this->item = $item;
        return true;
    }

    public function archive(&$queue_item)
    {
        global $mtlda;

        // verify QueueItemModel is ok()
        if (!$queue_item->verify()) {
            $mtlda->raiseError("QueueItemModel::verify() returned false!");
            return false;
        }

        // retrieve QueueItemModel file hash
        if (!($hash = $queue_item->getFileHash())) {
            $mtlda->raiseError("QueueItemModel::getFileHash() returned false!");
            return false;
        }

        // generate a hash-value based directory name
        if (!($store_dir_name = $this->generateDirectoryName($hash))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($store_dir_name) || empty($store_dir_name)) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned an empty directory string");
            return false;
        }

        // create the target directory structure
        if (!$this->createDirectoryStructure($store_dir_name)) {
            $mtlda->raiseError("StorageController::createDirectoryStructure() returned false!");
            return false;
        }

        $archive_item = new Models\ArchiveItemModel;

        if (!isset($queue_item->fields) || empty($queue_item->fields)) {
            $mtlda->raiseError("\$queue_item->fields not set!");
            return false;
        }

        // copy fields from QueueItemModel to ArchiveItemModel
        foreach (array_keys($queue_item->fields) as $queue_field) {

            if (in_array($queue_field, array("queue_state", "queue_guid"))) {
                continue;
            }

            $archive_field = str_replace("queue_", "archive_", $queue_field);
            $archive_item->$archive_field = $queue_item->$queue_field;
        }

        $archive_item->archive_version = '1';
        $archive_item->archive_derivation = '';

        // copy file from queue to data directory
        if (!$this->copyQueueItemFileToArchive($queue_item->queue_file_name, $store_dir_name)) {
            $mtlda->raiseError("StorageController::copyQueueItemFileToArchive() returned false!");
            return false;
        }

        // safe ArchiveItemModel to database, if that fails revert
        if (!$archive_item->save()) {
            $this->deleteArchiveItemFile($queue_item->queue_file_name, $store_dir_name);
            $mtlda->raiseError("ArchiveItemModel::save() returned false!");
            return false;
        }

        // delete QueueItemModel from database, if that fails revert
        if (!$queue_item->delete()) {
            $archive_item->delete();
            $mtlda->raiseError("ArchiveItemModel::delete() returned false!");
            return false;
        }

        return true;
    }

    public function generateDirectoryName($hash)
    {
        global $mtlda;

        $dir_name = "";

        if (empty($hash)) {
            $mtlda->raiseError("hash is empty!");
            return false;
        }

        for ($i = 0; $i < strlen($hash); $i+=2) {

            $hash_part = substr($hash, $i, 2);

            if (!$hash_part) {
                $mtlda->raiseError("substr() returned false!");
                return false;
            }

            // stop if we reach nesting depth
            if (($i/2) > $this->nesting_depth) {
                break;
            }

            $dir_name.= $hash_part.'/';
        }

        if (!isset($dir_name) || empty($dir_name)) {
            return false;
        }

        return $dir_name;
    }

    private function createDirectoryStructure($store_dir_name)
    {
        global $mtlda;

        if (empty($store_dir_name)) {
            return false;
        }

        $fqpn = $this->data_path .'/'. $store_dir_name;

        if (file_exists($fqpn) && is_dir($fqpn)) {
            return true;
        }

        if (file_exists($fqpn) && !is_dir($fqpn)) {
            $mtlda->raiseError("StorageController::createDirectoryStructure(), {$fqpn} exists, but is not a directory");
            return false;
        }

        if (!mkdir($fqpn, 0700, true)) {
            $mtlda->raiseError("mkdir() returned false!");
            return false;
        }

        return true;
    }

    private function copyQueueItemFileToArchive($file_name, $dest_dir)
    {
        global $mtlda;

        $fqpn_src = $this->working_path .'/'. $file_name;
        $fqpn_dst = $this->data_path .'/'. $dest_dir;

        if (!file_exists($fqpn_src)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), {$fqpn_src} does not exist!");
            return false;
        }

        if (!file_exists($fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), {$fqpn_dst} does not exist!");
            return false;
        }

        if (!is_dir($fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), {$fqpn_dst} is not a directory!");
            return false;
        }

        $fqpn_dst.= '/'. $file_name;

        if (file_exists($fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), destination file {$fqpn_dst} already exists!");
            return false;
        }

        if (!copy($fqpn_src, $fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), rename() returned false!");
            return false;
        }

        return true;
    }

    private function deleteQueueItemFile($file_name)
    {
        global $mtlda;

        $fqpn_src = $this->working_path .'/'. $file_name;

        if (!file_exists($fqpn_src)) {
            return true;
        }

        if (!unlink($fqpn_src)) {
            $mtlda->raiseError("deleteQueueItemFile(), unlink() returned false!");
            return false;
        }

        return true;
    }

    private function deleteArchiveItemFile($file_name, $dest_dir)
    {
        global $mtlda;

        $fqpn_dst = $this->data_path .'/'. $dest_dir;

        if (!file_exists($fqpn_dst)) {
            return true;
        }

        if (!unlink($fqpn_dst)) {
            $mtlda->raiseError("deleteArchiveItemFile(), unlink() returned false!");
            return false;
        }

        return true;
    }

    public function deleteItemFile()
    {
        global $mtlda;

        if (!isset($this->item) || empty($this->item)) {
            $mtlda->raiseError("\$this->item is not set!");
            return false;
        }

        if (!isset($this->item->column_name) || empty($this->item->column_name)) {
            $mtlda->raiseError("\$this->item->column_name is not set!");
            return false;
        }

        $file_name = $this->item->column_name .'_file_name';
        $file_hash = $this->item->column_name .'_file_hash';

        if (!isset($this->item->$file_name) || empty($this->item->$file_name)) {
            $mtlda->raiseError("\$this->item->{$file_name} is not set!");
            return false;
        }

        if (!isset($this->item->$file_hash) || empty($this->item->$file_hash)) {
            $mtlda->raiseError("\$this->item->{$file_hash} is not set!");
            return false;
        }

        if ($this->item->column_name == 'archive') {

            if (!($dir_name = $this->generateDirectoryName($this->item->$file_hash))) {
                $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
                return false;
            }

            if (!isset($dir_name) || empty($dir_name)) {
                $mtlda->raiseError("StorageController::generateDirectoryName() returned nothing!");
                return false;
            }

            $fqpn = $this->data_path .'/'. $dir_name .'/'. $this->item->$file_name;
        } elseif ($this->item->column_name == 'queue') {

            $fqpn = $this->working_path .'/'. $this->item->$file_name;

        } else {
            $mtlda->raiseError("Unsupported model ". $this->item->column_name);
            return false;
        }

        if (!file_exists($fqpn)) {
            $mtlda->raiseError("StorageController::deleteItemFile(), {$fqpn} does not exist!");
            return false;
        }

        if (!unlink($fqpn)) {
            $mtlda->raiseError("StorageController::deleteItemFile(), unlink() returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
