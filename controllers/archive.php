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

        if ($config->isEmbeddingMtldaIcon()) {
            if (!$this->embedMtldaIcon($document)) {
                $mtlda->raiseError("embedMtldaIcon() returned false!");
                if (!$document->delete()) {
                    $mtlda->raiseError("Failed to revert on deleting document from archive!");
                }
                return false;
            }
            if (!$document->refresh($store_dir_name)) {
                $mtlda->raiseError("DocumentModel::refresh() returned false!");
                if (!$document->delete()) {
                    $mtlda->raiseError("Failed to revert on deleting document from archive!");
                }
                return false;
            }
        }

        // delete QueueItemModel from database, if that fails revert
        if (!$queue_item->delete()) {
            $mtlda->raiseError("DocumentModel::delete() returned false!");
            if (!$document->delete()) {
                $mtlda->raiseError("QueueItemModel::delete() returned false!");
            }
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
            $mtlda->raiseError(__CLASS__ ."::sign() returned false!");
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
                "using {$dest_dir_name} as destination",
                "archive",
                "storage",
                $signing_item->document_guid
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
            return false;
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

    private function embedMtldaIcon(&$document)
    {
        global $mtlda;

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            $mtlda->raiseError("Failed to load FPDI!");
            return false;
        }

        if (!($fqpn = $document->getFilePath())) {
            $mtlda->raiseError("DocumentModel::getFilePath() returned false!");
            return false;
        }

        if (!isset($fqpn) || empty($fqpn)) {
            $mtlda->raiseError("DocumentModel::getFilePath() returned an invalid file name!");
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

        $page_count = $pdf->setSourceFile($fqpn);

        for ($page_no = 1; $page_no <= $page_count; $page_no++) {

            // import a page
            $templateId = $pdf->importPage($page_no);
            // get the size of the imported page
            $size = $pdf->getTemplateSize($templateId);

            // create a page (landscape or portrait depending on the imported page size)
            if ($size['w'] > $size['h']) {
                $pdf->AddPage('L', array($size['w'], $size['h']));
            } else {
                $pdf->AddPage('P', array($size['w'], $size['h']));
            }

            // use the imported page
            $pdf->useTemplate($templateId);

            if ($page_no != 1) {
                continue;
            }

            $signing_icon_position = $this->getSigningIconPosition(
                $document->document_signing_icon_position,
                $size['w'],
                $size['h']
            );

            if (!$signing_icon_position) {
                $mtlda->raiseError("getSigningIconPosition() returned false!");
                return false;
            }

            $pdf->Image(
                MTLDA_BASE.'/public/resources/images/MTLDA_signed.png',
                $signing_icon_position['x-pos'],
                $signing_icon_position['y-pos'],
                16 /* width */,
                16 /* height */,
                'PNG',
                null,
                null,
                true /* resize */
            );
        }

        // set additional information
        /*$info = array(
            'Name' => $this->pdf_cfg['author'],
            'Location' => $this->pdf_cfg['location'],
            'Reason' => $this->pdf_cfg['reason'],
            'ContactInfo' => $this->pdf_cfg['contact'],
        );*/

        // define active area for signature appearance
        $pdf->setSignatureAppearance(
            $signing_icon_position['x-pos'],
            $signing_icon_position['y-pos'],
            16,
            16,
            1 /* page number */,
            "MTLDA Document Signature"
        );

        // ---------------------------------------------------------

        //Close and output PDF document
        $pdf->Output($fqpn, 'F');
        return true;
    }

    private function getSigningIconPosition($icon_position, $page_width, $page_height)
    {
        global $mtlda;

        if (empty($icon_position)) {
            return false;
        }

        $known_positions = array(
            SIGN_TOP_LEFT,
            SIGN_TOP_CENTER,
            SIGN_TOP_RIGHT,
            SIGN_MIDDLE_LEFT,
            SIGN_MIDDLE_CENTER,
            SIGN_MIDDLE_RIGHT,
            SIGN_BOTTOM_LEFT,
            SIGN_BOTTOM_CENTER,
            SIGN_BOTTOM_RIGHT
        );

        if (!in_array($icon_position, $known_positions)) {
            return false;
        }

        switch ($icon_position) {
            case SIGN_TOP_LEFT:
                $x = 50;
                $y = 10;
                break;
            case SIGN_TOP_CENTER:
                $x = ($page_width/2)-8;
                $y = 10;
                break;
            case SIGN_TOP_RIGHT:
                $x = $page_width - 50;
                $y = 10;
                break;
            case SIGN_MIDDLE_LEFT:
                $x = 50;
                $y = ($page_height/2)-8;
                break;
            case SIGN_MIDDLE_CENTER:
                $x = ($page_width/2)-8;
                $y = ($page_height/2)-8;
                break;
            case SIGN_MIDDLE_RIGHT:
                $x = $page_width - 50;
                $y = ($page_height/2)-8;
                break;
            case SIGN_BOTTOM_LEFT:
                $x = 50;
                $y = $page_height - 50;
                break;
            case SIGN_BOTTOM_CENTER:
                $x = ($page_width/2)-8;
                $y = $page_height - 50;
                break;
            case SIGN_BOTTOM_RIGHT:
                $x = $page_width - 50;
                $y = $page_height - 50;
                break;
            default:
                $mtlda->raiseError("Unkown ѕigning icon position {$icon_position}");
                return false;
        }

        return array(
            'x-pos' => $x,
            'y-pos' => $y
        );
    }
}
// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
