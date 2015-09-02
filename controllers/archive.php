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

use MTLDA\Controllers;
use MTLDA\Models;

class ArchiveController extends DefaultController
{
    public function archive(&$queue_item)
    {
        global $mtlda, $config, $audit;

        // verify QueueItemModel is ok()
        if (!$queue_item->verify()) {
            $mtlda->raiseError("QueueItemModel::verify() returned false!");
            return false;
        }

        try {
            $document = new Models\DocumentModel;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load DocumentModel!");
            return false;
        }

        try {
            $storage = new StorageController;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load StorageController!");
            return false;
        }

        try {
            $audit->log(
                "archiving requested",
                "archive",
                "storage",
                $queue_item->queue_guid
            );
        } catch (Exception $e) {
            $mtlda->raiseError("AuditController::log() returned false!");
            return false;
        }

        // generate a hash-value based directory name
        if (!($store_dir_name = $storage->generateDirectoryName($queue_item->queue_guid))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($store_dir_name) || empty($store_dir_name)) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned an empty directory string");
            return false;
        }

        // create the target directory structure
        if (!$storage->createDirectoryStructure($store_dir_name)) {
            $mtlda->raiseError("StorageController::createDirectoryStructure() returned false!");
            return false;
        }

        try {
            $audit->log(
                "using {$store_dir_name} as destination",
                "archive",
                "storage",
                $queue_item->queue_guid
            );
        } catch (Exception $e) {
            $mtlda->raiseError("AuditController::log() returned false!");
            return false;
        }

        if (
            !isset($queue_item->fields) ||
            empty($queue_item->fields) ||
            !is_array($queue_item->fields)
        ) {
            $mtlda->raiseError("\$queue_item->fields not set!");
            return false;
        }

        // copy fields from QueueItemModel to DocumentModel
        foreach (array_keys($queue_item->fields) as $queue_field) {

            // fields we skip
            if (in_array($queue_field, array("queue_idx", "queue_state"))) {
                continue;
            }

            $document_field = str_replace("queue_", "document_", $queue_field);
            $document->$document_field = $queue_item->$queue_field;
        }

        $document->document_version = '1';
        $document->document_derivation = '';
        $document->document_derivation_guid = '';

        // safe DocumentModel to database, if that fails revert
        if (!$document->save()) {
            $mtlda->raiseError("DocumentModel::save() returned false!");
            return false;
        }

        if (!$storage->copyQueueItemFileToArchive($queue_item->queue_file_name, $store_dir_name)) {
            $mtlda->raiseError("StorageController::copyQueueItemFileToArchive() returned false!");
            if (!$document->delete()) {
                $mtlda->raiseError("Failed to revert on deleting document from archive!");
            }
            return false;
        }

        // delete QueueItemModel from database, if that fails revert
        // deleting the model will also remove the file
        if (!$queue_item->delete()) {
            $document->delete();
            $mtlda->raiseError("DocumentModel::delete() returned false!");
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

        if (!$this->sign($document)) {
            return false;
        }

        return true;
    }

    public function sign(&$src_item)
    {
        global $mtlda, $config, $audit;

        if (!$config->isPdfSigningEnabled()) {
            $mtlda->raiseError("ConfigController::isPdfSigningEnabled() returns false!");
            return false;
        }

        try {
            $signer = new Controllers\PdfSigningController;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load PdfSigningController");
            return false;
        }

        try {
            $storage = new Controllers\StorageController;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load StorageController!");
            return false;
        }

        try {
            $signing_item = new Models\DocumentModel;
        } catch (Exception $e) {
            $mtlda->raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!($signing_item->createClone($src_item))) {
            $mtlda->raiseError(__TRAIT__ ." unable to clone DocumentModel!");
            return false;
        }

        try {
            $audit->log(
                __METHOD__,
                "read",
                "archive",
                $src_item->document_guid
            );
        } catch (Exception $e) {
            $signing_item->delete();
            $mtlda->raiseError("AuditController::log() raised an exception!");
            return false;
        }

        $signing_item->document_file_name = str_replace(".pdf", "_signed.pdf", $signing_item->document_file_name);
        $signing_item->document_version++;
        $signing_item->document_derivation = $src_item->id;
        $signing_item->document_derivation_guid = $src_item->document_guid;
        $signing_item->save();

        // generate a hash-value based directory name
        if (!($src_dir_name = $storage->generateDirectoryName($src_item->document_guid))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            $signing_item->delete();
            return false;
        }

        if (!($dest_dir_name = $storage->generateDirectoryName($signing_item->document_guid))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            $signing_item->delete();
            return false;
        }

        // create the target directory structure
        if (!$storage->createDirectoryStructure($dest_dir_name)) {
            $mtlda->raiseError("StorageController::createDirectoryStructure() returned false!");
            $signing_item->delete();
            return false;
        }

        try {
            $audit->log(
                "using {$store_dir_name} as destination",
                "archive",
                "storage",
                $queue_item->queue_guid
            );
        } catch (Exception $e) {
            $mtlda->raiseError("AuditController::log() returned false!");
            return false;
        }

        $src = $src_dir_name  .'/'. $src_item->document_file_name;
        $dst = $dest_dir_name .'/'. $signing_item->document_file_name;

        if (!$storage->copyArchiveDocumentFile($src, $dst)) {
            $signing_item->delete();
            $mtlda->raiseError("StorageController::copyArchiveDocumentFile() returned false!");
            return false;
        }

        $fqpn_dst = $this::ARCHIVE_DIRECTORY .'/'. $dst;

        if (!$signer->signDocument($fqpn_dst, $signing_item)) {
            $signing_item->delete();
            $mtlda->raiseError("PdfSigningController::ѕignDocument() returned false!");
            return $false;
        }

        if (!$signing_item->refresh($dest_dir_name)) {
            $signing_item->delete();
            $mtlda->raiseError("refresh() returned false!");
            return false;
        }

        if (!$signing_item->save()) {
            $signing_item->delete();
            $mtlda->raiseError("save() returned false!");
            return false;
        }

        try {
            $audit->log(
                $src_item->document_guid,
                "signed",
                "archive",
                $signing_item->document_guid
            );
        } catch (Exception $e) {
            $signing_item->delete();
            $mtlda->raiseError("AuditController::log() raised an exception!");
            return false;
        }

        return true;
    }

    public function checkForDuplicateFileByHash($file_hash)
    {
        global $mtlda, $db;

        if (!isset($file_hash) || empty($file_hash)) {
            $mtlda->raiseError("Require a valid file hash!");
            return false;
        }

        $sth = $db->prepare(
            "SELECT
                document_idx,
                document_guid
            FROM
                TABLEPREFIXarchive
            WHERE
                document_file_hash
            LIKE
                ?"
        );

        if (!$sth) {
            $mtldq->raiseError("Failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($file_hash))) {
            $mtlda->raiseError("Failed to execute query!");
            return false;
        }

        if (!($rows = $sth->fetchAll(\PDO::FETCH_COLUMN))) {
            return array();
        }

        if (count($rows) == 0) {
            return array();
        }

        if (count($rows) > 1) {
            $mtlda->raiseError("There are multiple documents with the same file hash! This should not happend!");
            return false;
        }

        return $rows[0];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
