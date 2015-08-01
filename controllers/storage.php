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
use MTLDA\Controllers;

class StorageController
{
    private $archive_path = MTLDA_BASE."/data/archive";
    private $working_path = MTLDA_BASE."/data/working";
    private $nesting_depth = 5;

    public function archive(&$queue_item)
    {
        global $mtlda, $config;

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

            if (in_array($queue_field, array("queue_idx", "queue_state", "queue_guid"))) {
                continue;
            }

            $archive_field = str_replace("queue_", "archive_", $queue_field);
            $archive_item->$archive_field = $queue_item->$queue_field;
        }

        $archive_item->archive_version = '1';
        $archive_item->archive_derivation = '';
        $archive_item->archive_derivation_guid = '';

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

        // if no more actions are necessary, we are done
        if (!$config->isPdfSigningEnabled()) {
            return true;
        }

        // if auto-signing is not enabled, we are done here
        if (!$config->isPdfAutoPdfSignOnImport()) {
            return true;
        }

        if (!$this->sign($archive_item)) {
            return false;
        }

        return true;

    }

    public function sign(&$src_item)
    {
        global $mtlda, $config;

        if ($config->isPdfSigningEnabled()) {

            try {
                $signer = new Controllers\PdfSigningController;
            } catch (Exception $e) {
                $archive_item->delete();
                $mtlda->raiseError("Failed to load PdfSigningController");
                return false;
            }
        }

        $signing_item = new models\ArchiveItemModel;
        if (!($signing_item->createClone($src_item))) {
            $mtlda->raiseError(__TRAIT__ ." unable to clone ArchiveItemModel!");
            return false;
        }

        // retrieve ArchiveItem file hash
        if (!($hash = $src_item->getFileHash())) {
            $mtlda->raiseError("ArchiveItemModel::getFileHash() returned false!");
            return false;
        }

        // generate a hash-value based directory name
        if (!($store_dir_name = $this->generateDirectoryName($hash))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            return false;
        }

        $signing_item->archive_file_name = str_replace(".pdf", "_signed.pdf", $signing_item->archive_file_name);
        $signing_item->archive_version++;
        $signing_item->archive_derivation = $src_item->id;
        $signing_item->archive_derivation_guid = $src_item->archive_guid;
        $signing_item->save();

        $src = $store_dir_name .'/'. $src_item->archive_file_name;
        $dst = $store_dir_name .'/'. $signing_item->archive_file_name;

        if (!$this->copyArchiveItemFile($src, $dst)) {
            $signing_item->delete();
            $mtlda->raiseError("StorageController::copyArchiveItemFile() returned false!");
            return false;
        }

        $fqpn_dst = $this->archive_path .'/'. $dst;

        if (!$signer->signDocument($fqpn_dst, $signing_item->archive_signing_icon_position)) {
            $signing_item->delete();
            $mtlda->raiseError("PdfSigningController::ѕignDocument() returned false!");
            return $false;
        }

        if (!$signing_item->refresh($store_dir_name)) {
            $signing_item->delete();
            $mtlda->raiseError("refresh() returned false!");
            return false;
        }

        if (!$signing_item->save()) {
            $signing_item->delete();
            $mtlda->raiseError("save() returned false!");
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

        // remove trailing slash
        $dir_name = rtrim($dir_name, '/');

        return $dir_name;
    }

    private function createDirectoryStructure($store_dir_name)
    {
        global $mtlda;

        if (empty($store_dir_name)) {
            return false;
        }

        $fqpn = $this->archive_path .'/'. $store_dir_name;

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
        $fqpn_dst = $this->archive_path .'/'. $dest_dir;

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

    private function copyArchiveItemFile($src, $dst)
    {
        global $mtlda;

        $fqpn_src = $this->archive_path .'/'. $src;
        $fqpn_dst = $this->archive_path .'/'. $dst;

        if (!file_exists($fqpn_src)) {
            $mtlda->raiseError("copyArchiveItemFile(), {$fqpn_src} does not exist!");
            return false;
        }

        if (file_exists($fqpn_dst)) {
            $mtlda->raiseError("copyArchiveItemFile(), {$fqpn_dst} already exist!");
            return false;
        }

        if (!copy($fqpn_src, $fqpn_dst)) {
            $mtlda->raiseError("copyArchiveItemFile(), rename() returned false!");
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

        $fqpn_dst = $this->archive_path .'/'. $dest_dir;

        if (!file_exists($fqpn_dst)) {
            return true;
        }

        if (!unlink($fqpn_dst)) {
            $mtlda->raiseError("deleteArchiveItemFile(), unlink() returned false!");
            return false;
        }

        return true;
    }

    public function deleteItemFile(&$item)
    {
        global $mtlda;

        if (!isset($item) || empty($item)) {
            $mtlda->raiseError("\$item is not set!");
            return false;
        }

        if (!isset($item->column_name) || empty($item->column_name)) {
            $mtlda->raiseError("\$item->column_name is not set!");
            return false;
        }

        $file_name = $item->column_name .'_file_name';
        $file_hash = $item->column_name .'_file_hash';

        if (!isset($item->$file_name) || empty($item->$file_name)) {
            $mtlda->raiseError("\$item->{$file_name} is not set!");
            return false;
        }

        if (!isset($item->$file_hash) || empty($item->$file_hash)) {
            $mtlda->raiseError("\$item->{$file_hash} is not set!");
            return false;
        }

        if ($item->column_name == 'archive') {

            if (!($dir_name = $this->generateDirectoryName($item->$file_hash))) {
                $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
                return false;
            }

            if (!isset($dir_name) || empty($dir_name)) {
                $mtlda->raiseError("StorageController::generateDirectoryName() returned nothing!");
                return false;
            }

            $fqpn = $this->archive_path .'/'. $dir_name .'/'. $item->$file_name;
        } elseif ($item->column_name == 'queue') {

            $fqpn = $this->working_path .'/'. $item->$file_name;

        } else {
            $mtlda->raiseError("Unsupported model ". $item->column_name);
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

    public function retrieveFile(&$document, $hash, $from = 'archive')
    {
        global $mtlda;

        if ($from == 'archive') {
            $src = $this->archive_path;
        } else {
            $src = $this->working_path;
        }

        if (!($dir_name = $this->generateDirectoryName($hash))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($dir_name) || empty($dir_name)) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned an empty directory string");
            return false;
        }

        $src.= '/'. $dir_name .'/';

        if ($from == 'archive') {
            $src.= $document->archive_file_name;
        } else {
            $src.= $document->queue_file_name;
        }

        if (!file_exists($src)) {
            $mtlda->raiseError("Source does not exist!");
            return false;
        }

        if (!is_readable($src)) {
            $mtlda->raiseError("Source is not readable!");
            return false;
        }

        if (!($content = file_get_contents($src))) {
            $mtlda->raiseError("file_get_contents() returned false!");
            return false;
        }

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
