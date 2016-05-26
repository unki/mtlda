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

namespace Mtlda\Controllers;

class ArchiveController extends DefaultController
{
    public function archive(&$queue_item)
    {
        global $config, $audit, $mbus;

        // verify QueueItemModel is ok()
        if (!$queue_item->verify()) {
            static::raiseError("QueueItemModel::verify() returned false!");
            return false;
        }

        if ($queue_item->isProcessing()) {
            static::raiseError(__METHOD__ .'(), QueueItemModel already in processing!');
            return false;
        }

        if (!$queue_item->setProcessingFlag(true)) {
            static::raiseError(get_class($queue_item) .'::setProcessingFlag() returned false!');
            return false;
        }

        if (!$queue_item->save()) {
            static::raiseError(get_class($queue_item) .'::save() returned false!');
            return false;
        }

        try {
            $document = new \Mtlda\Models\DocumentModel;
        } catch (\Exception $e) {
            static::raiseError("Failed to load DocumentModel!");
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError("Failed to load StorageController!");
            return false;
        }

        try {
            $audit->log(
                "archiving requested",
                "archive",
                "storage",
                $queue_item->getGuid()
            );
        } catch (\Exception $e) {
            static::raiseError("AuditController::log() returned false!");
            return false;
        }

        if (($fields = $queue_item->getFields()) === false) {
            static::raiseError(get_class($queue_item) .'::getModelFields() returned false!');
            return false;
        }

        if (!is_array($fields) || empty($fields)) {
            static::raiseError(get_class($queue_item) .'::getModelFields() returned no fields!');
            return false;
        }

        $fields_to_skip = array(
            'idx',
            'state',
            'in_processing',
            'keywords'
        );

        // copy fields from QueueItemModel to DocumentModel
        foreach ($fields as $queue_field => $queue_field_prop) {
            // fields we skip
            if (in_array($queue_field, $fields_to_skip)) {
                continue;
            }

            $document_field = str_replace("queue_", "document_", $queue_field);

            if (!$document->setFieldValue($document_field, $queue_field_prop['value'])) {
                static::raiseError(get_class($document) .'::setFieldValue() returned false!');
                return false;
            }
        }

        if (!$document->hasTitle()) {
            if (!$document->hasFileName()) {
                static::raiseError(__METHOD__ .'(), document has no title nor a filename!');
                return false;
            }
            if (($name = $document->getFileName()) === false) {
                static::raiseError(get_class($document) .'::getFileName() returned false!');
                return false;
            }
            if (!$document->setTitle($document->getFileName())) {
                static::raiseError(get_class($document) .'::setTitle() returned false!');
                return false;
            }
        }

        if (!$document->setVersion('1')) {
            static::raiseError(get_class($document) .'::setVersion() returned false!');
            return false;
        }

        //$document->document_derivation = '';
        //$document->document_derivation_guid = '';

        if (($fqfn_src = $queue_item->getFilePath()) === false) {
            static::raiseError(get_class($queue_item) .'::getFilePath() returned false!');
            return false;
        }

        if (($fqfn_dst = $document->getFilePath()) === false) {
            static::raiseError(get_class($queue_item) .'::getFilePath() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Moving document to archive store.', '30%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        // create the target directory structure
        if (!$storage->createDirectoryStructure(dirname($fqfn_dst))) {
            static::raiseError("StorageController::createDirectoryStructure() returned false!");
            return false;
        }

        try {
            $audit->log(
                "using {$fqfn_dst} as destination",
                "archive",
                "storage",
                $queue_item->getGuid()
            );
        } catch (\Exception $e) {
            static::raiseError("AuditController::log() returned false!");
            return false;
        }

        if (!$storage->copyFile($fqfn_src, $fqfn_dst)) {
            static::raiseError("StorageController::copyFile() returned false!");
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Saving document.', '40%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        // safe DocumentModel to database, remove the file from archive again
        if (!$document->save()) {
            static::raiseError("DocumentModel::save() returned false!");
            if (!$storage->deleteItemFile($document)) {
                static::raiseError("StorageController::deleteItemFile() returned false!");
            }
            return false;
        }

        // transfer keywords
        if ($queue_item->hasKeywords()) {
            if (($keywords = $queue_item->getKeywords()) === false) {
                static::raiseError(get_class($queue_item) .'::getKeywords() returned false!');
                return false;
            }
            if (isset($keywords) && is_array($keywords)) {
                if (!$document->setKeywords($keywords)) {
                    static::raiseError(get_class($document) .'::setKeywords() returned false!');
                    return false;
                }
            }
        }

        // delete QueueItemModel from database, if that fails revert
        if (!$queue_item->delete()) {
            static::raiseError("DocumentModel::delete() returned false!");
            if (!$document->delete()) {
                static::raiseError("QueueItemModel::delete() returned false!");
            }
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Embeding seal icon into document.', '50%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if ($config->isEmbeddingMtldaIcon()) {
            if (!$this->embedMtldaIcon($document)) {
                static::raiseError("embedMtldaIcon() returned false!");
                return false;
            }
            if (!$document->refresh()) {
                static::raiseError("DocumentModel::refresh() returned false!");
                return false;
            }
        }

        if ($config->isPdfIndexingEnabled() &&
            ($document->hasIndices() || !$document->hasProperties())
        ) {
            if (!$mbus->sendMessageToClient('archive-reply', 'Indexing document content.', '60%')) {
                static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }

            $state = $mbus->suppressOutboundMessaging(true);
            if (!$this->indexDocument($document)) {
                static::raiseError('indexDocument() returned false!');
                return false;
            }
            $mbus->suppressOutboundMessaging($state);
        }

        // if no more actions are necessary, we are done
        if (!$config->isPdfSigningEnabled()) {
            return true;
        }

        // if auto-signing is not enabled, we are done here
        if (!$config->isPdfAutoPdfSignOnImport()) {
            return true;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Signing document.', '80%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        $state = $mbus->suppressOutboundMessaging(true);
        if (!$this->sign($document)) {
            static::raiseError(__CLASS__ ."::sign() returned false!");
            return false;
        }
        $mbus->suppressOutboundMessaging($state);

        return true;
    }

    public function sign(&$src_item)
    {
        global $config, $audit, $mbus;

        if (!$config->isPdfSigningEnabled()) {
            static::raiseError("ConfigController::isPdfSigningEnabled() returns false!");
            return false;
        }

        try {
            $signer = new \Mtlda\Controllers\PdfSigningController;
        } catch (\Exception $e) {
            static::raiseError("Failed to load PdfSigningController");
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError("Failed to load StorageController!");
            return false;
        }

        try {
            $signing_item = new \Mtlda\Models\DocumentModel;
        } catch (\Exception $e) {
            static::raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-reply', 'Deriving copy of orignal document', '30%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!($signing_item->createClone($src_item))) {
            static::raiseError(__METHOD__ ." unable to clone DocumentModel!");
            return false;
        }

        try {
            $audit->log(
                __METHOD__,
                "read",
                "archive",
                $src_item->getGuid()
            );
        } catch (\Exception $e) {
            $signing_item->delete();
            static::raiseError("AuditController::log() raised an exception!");
            return false;
        }

        // we need to save once so the database id is written back to the document_idx field.
        if (!$signing_item->save()) {
            static::raiseError(get_class($signing_item) .'::save() returned false!');
            return false;
        }

        // append a suffix to new cloned file
        $signing_item->setFileName(str_replace(".pdf", "_signed.pdf", $signing_item->getFileName()));
        $signing_item->setDerivationId($src_item->id);
        $signing_item->setDerivationGuid($src_item->getGuid());

        if (!$signing_item->save()) {
            static::raiseError(get_class($signing_item) .'::save() returned false!');
            return false;
        }

        if ($config->isPdfSigningAttachAuditLogEnabled()) {
            if (!$this->attachAuditLogToDocument($signing_item)) {
                $signing_item->delete();
                static::raiseError(__CLASS__ .'::attachAuditLogToDocument() returned false!');
                return false;
            }
        }

        if (!$signer->signDocument($signing_item)) {
            $signing_item->delete();
            static::raiseError("PdfSigningController::ѕignDocument() returned false!");
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-reply', 'Refreshing document information', '90%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$signing_item->refresh()) {
            $signing_item->delete();
            static::raiseError("refresh() returned false!");
            return false;
        }

        $signing_item->setSignedCopy(true);

        if (!$signing_item->save()) {
            $signing_item->delete();
            static::raiseError("save() returned false!");
            return false;
        }

        try {
            $audit->log(
                $src_item->getGuid(),
                "signed",
                "archive",
                $signing_item->getGuid()
            );
        } catch (\Exception $e) {
            $signing_item->delete();
            static::raiseError("AuditController::log() raised an exception!");
            return false;
        }

        return true;
    }

    public function checkForDuplicateFileByHash($file_hash)
    {
        global $db;

        if (!isset($file_hash) || empty($file_hash)) {
            static::raiseError("Require a valid file hash!");
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
                ?
            AND (
                document_deleted <> 'Y'
            OR
                document_deleted IS NULL
            )"
        );

        if (!$sth) {
            $mtldq->raiseError("Failed to prepare query!");
            return false;
        }

        if (!$db->execute($sth, array($file_hash))) {
            static::raiseError("Failed to execute query!");
            return false;
        }

        if (($rows = $sth->fetchAll(\PDO::FETCH_COLUMN)) === false) {
            return array();
        }

        if (count($rows) == 0) {
            return array();
        }

        if (count($rows) > 1) {
            static::raiseError("There are multiple documents with the same file hash! This should not happend!");
            return false;
        }

        return $rows[0];
    }

    private function embedMtldaIcon(&$src_document)
    {
        if (!is_a($src_document, 'Mtlda\Models\DocumentModel')) {
            static::raiseError(__METHOD__ .' can only operate on DocumentModels!');
            return false;
        }

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            static::raiseError("Failed to load FPDI!");
            return false;
        }

        try {
            $logo_doc = new \Mtlda\Models\DocumentModel;
        } catch (\Exception $e) {
            static::raiseError("Failed to load DocumentModel!");
            return false;
        }

        if (!$logo_doc->createClone($src_document)) {
            static::raiseError(get_class($logo_doc) .'::createClone() returned false!');
            return false;
        }

        if ($logo_doc->setDerivationId($src_document->getId()) === false) {
            static::raiseError(get_class($src_document) .'::getId() returned false!');
            return false;
        }

        if ($logo_doc->setDerivationGuid($src_document->getGuid()) === false) {
            static::raiseError(get_class($src_document) .'::getGuid() returned false!');
            return false;
        }

        if (!$logo_doc->save()) {
            static::raiseError(get_class($logo_doc) .'::save() returned false!');
            return false;
        }

        if (($fqfn = $logo_doc->getFilePath()) === false) {
            static::raiseError("DocumentModel::getFilePath() returned false!");
            return false;
        }

        if (!isset($fqfn) || empty($fqfn)) {
            static::raiseError("DocumentModel::getFilePath() returned an invalid file name!");
            return false;
        }

        if (!file_exists($fqfn)) {
            static::raiseError("File {$fqfn} does not exist!");
            return false;
        }

        if (!is_readable($fqfn)) {
            static::raiseError("File {$fqfn} is not readable!");
            return false;
        }

        try {
            $page_count = $pdf->setSourceFile($fqfn);
        } catch (\Exception $e) {
            static::raiseError(getClass($pdf) .'::setSourceFile() has thrown an exception! '. $e->getMessage());
            return false;
        }

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
                $logo_doc->getSigningIconPosition(),
                $size['w'],
                $size['h']
            );

            if (!$signing_icon_position) {
                static::raiseError("getSigningIconPosition() returned false!");
                return false;
            }

            $pdf->Image(
                APP_BASE.'/public/resources/images/MTLDA_signed.png',
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
            "Mtlda Document Signature"
        );

        // ---------------------------------------------------------

        //Close and output PDF document
        try {
            $pdf->Output($fqfn, 'F');
        } catch (\Exception $e) {
            static::raiseError(get_class($pdf) .'::Output() has thrown an exception! '. $e->getMessage());
            return false;
        }

        try {
            @$pdf->cleanUp();
        } catch (\Exception $e) {
            static::raiseError(get_class($pdf) .'::cleanUp() has thrown an exception! '. $e->getMessage());
            return false;
        }

        if (!$logo_doc->refresh()) {
            static::raiseError(get_class($logo_doc) .'::refresh() returned false!');
            return false;
        }

        return true;
    }

    private function getSigningIconPosition($icon_position, $page_width, $page_height)
    {
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
                static::raiseError("Unkown ѕigning icon position {$icon_position}");
                return false;
        }

        return array(
            'x-pos' => $x,
            'y-pos' => $y
        );
    }

    private function attachAuditLogToDocument($document)
    {
        global $audit;

        if (!isset($document) ||
            empty($document) ||
            !is_a($document, 'Mtlda\Models\DocumentModel')
        ) {
            static::raiseError(__METHOD__ .' can only work with DocmentModels!');
            return false;
        }

        if (($fqfn = $document->getFilePath()) === false) {
            static::raiseError(get_class($document) .'::getFilePath() returned false!');
            return false;
        }

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            static::raiseError("Failed to load FPDI!");
            return false;
        }

        try {
            $storage = new StorageController;
        } catch (\Exception $e) {
            static::raiseError('Failed to load StorageController!');
            return false;
        }

        try {
            $page_count = $pdf->setSourceFile($fqfn);
        } catch (\Exception $e) {
            static::raiseError(getClass($pdf) .'::setSourceFile() has thrown an exception! '. $e->getMessage());
            return false;
        }

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
        }

        if (($audittxt = $audit->retrieveAuditLog($document->getGuid())) === false) {
            static::raiseError(get_class($audit) .'::retrieveAuditLog() returned false!');
            return false;
        }

        if (empty($audittxt)) {
            static::raiseError(__METHOD__ .' audit log is empty!');
            return false;
        }

        if (($tmpdir = $storage->createTempDir('auditlog_')) === false) {
            static::raiseError(get_class($storage) .'::createTempDir() returned false!');
            return false;
        }

        $auditlog = $tmpdir .'/AuditLog.txt';

        if (file_exists($auditlog)) {
            static::raiseError('Strangle there is already an AuditLog.txt in my temporary directory! '. $auditlog);
            return false;
        }

        if (!file_put_contents($auditlog, $audittxt)) {
            static::raiseError('file_put_contents() returned false!');
            return false;
        }

        try {
            $pdf->Annotation(
                10,
                10,
                5,
                7,
                'AuditLog.txt',
                array(
                    'Subtype' => 'FileAttachment',
                    'Name' => 'PushPin',
                    'FS' => $auditlog
                )
            );
        } catch (\Exception $e) {
            unlink($auditlog);
            rmdir($tmpdir);
            static::raiseError(get_class($pdf) .'::Annotation() has thrown an exception! '. $e->getMessage());
            return false;
        }

        unlink($auditlog);
        rmdir($tmpdir);

        try {
            $pdf->Output($fqfn, 'F');
        } catch (\Exception $e) {
            static::raiseError(get_class($pdf) .'::Output() has thrown an exception! '. $e->getMessage());
            return false;
        }

        try {
            @$pdf->cleanUp();
        } catch (\Exception $e) {
            static::raiseError(get_class($pdf) .'::cleanUp() has thrown an exception! '. $e->getMessage());
            return false;
        }

        if (!$document->refresh()) {
            static::raiseError(get_class($document) .'::refresh() returned false!');
            return false;
        }

        return true;
    }

    public function indexDocument(&$document)
    {
        global $config, $audit, $mbus;

        if (!$config->isPdfIndexingEnabled()) {
            static::raiseError("ConfigController::isPdfIndexingEnabled() returns false!");
            return false;
        }

        try {
            $parser = new \Mtlda\Controllers\PdfIndexerController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load PdfIndexerController');
            return false;
        }

        if (!$parser) {
            static::raiseError(__METHOD__ .'(), \$parser is invalid!');
            return false;
        }

        if (!$parser->scan($document)) {
            static::raiseError(get_class($parser) .'::scan() returned false!');
            return false;
        }

        return true;
    }

    public function deleteExpiredDocuments()
    {
        try {
            $archive = new \Mtlda\Models\ArchiveModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ArchiveModel!');
            return false;
        }

        if (($documents = $archive->getExpiredDocuments()) === false) {
            static::raiseError(get_class($archive) .'::getExpiredDocuments() returned false!');
            return false;
        }

        if (empty($documents)) {
            return true;
        }

        if (!is_array($documents)) {
            static::raiseError(__METHOD__ .'(), ArchiveController::getExpiredDocuments() has not returned an array!');
            return false;
        }

        foreach ($documents as $document) {
            if (!is_a($document, 'Mtlda\Models\DocumentModel')) {
                static::raiseError(__METHOD__ .'(), provided object is not an DocumentModel!');
                return false;
            }

            if (!$document->delete()) {
                static::raiseError(get_class($document) .'::delete() returned false!');
                return false;
            }
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
