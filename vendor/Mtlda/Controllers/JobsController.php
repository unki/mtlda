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

class JobsController extends \Thallium\Controllers\JobsController
{
    protected $json_errors;

    public function __construct()
    {
        parent::__construct();

        try {
            $this->registerHandler('sign-request', array($this, 'handleSignRequest'));
            $this->registerHandler('import-request', array($this, 'handleImportRequest'));
            $this->registerHandler('mailimport-request', array($this, 'handleMailImportRequest'));
            $this->registerHandler('scan-request', array($this, 'handleScanDocumentRequests'));
            $this->registerHandler('archive-request', array($this, 'handleArchiveRequest'));
            $this->registerHandler('delete-request', array($this, 'handleDeleteRequest'));
            $this->registerHandler('preview-request', array($this, 'handlePreviewRequest'));
            $this->registerHandler('split-request', array($this, 'handleSplitRequest'));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to register handlers!', true);
            return false;
        }

        // Define the JSON errors.
        $constants = get_defined_constants(true);
        $this->json_errors = array();
        foreach ($constants["json"] as $name => $value) {
            if (!strncmp($name, "JSON_ERROR_", 11)) {
                $this->json_errors[$value] = $name;
            }
        }
    }

    protected function handleArchiveRequest($job)
    {
        global $mtlda, $mbus;

        if (empty($job) ||
            get_class($job) != 'Thallium\Models\JobModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || !($body = $job->getParameters())) {
            $this->raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($job) .'::getParameters() has not returned a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Preparing', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (($archive_request = unserialize($body)) === null) {
            $this->raiseError(__METHOD__ .', unable to unserialize message body!');
            return false;
        }

        if (!is_object($archive_request)) {
            $this->raiseError(__METHOD__ .', unserialize() has not returned an object!');
            return false;
        }

        if (!isset($archive_request->id) || empty($archive_request->id) ||
            !isset($archive_request->guid) || empty($archive_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', archive-request is incomplete!');
            return false;
        }

        if ($archive_request->id != 'all' &&
            !$mtlda->isValidId($archive_request->id)
        ) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if ($archive_request->guid != 'all' &&
            !$mtlda->isValidGuidSyntax($archive_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        try {
            $queue = new \Mtlda\Controllers\QueueController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load QueueController!");
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Loading document', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if ($archive_request->id == 'all' && $archive_request->guid == 'all') {
            if (!$queue->archiveAll()) {
                $this->raiseError(get_class($queue) .'::archiveAll() returned false!');
                return false;
            }
            if (!$mbus->sendMessageToClient('archive-reply', 'Done', '100%')) {
                $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }
            return true;
        }

        if (!$queue->archive($archive_request->id, $archive_request->guid)) {
            $this->raiseError(get_class($queue) .'::archive() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleImportRequest()
    {
        global $mbus, $config;

        if (!$config->isUserTriggersImportEnabled()) {
            $this->raiseError(get_class($config) .'::isUserTriggersImportEnabled() returned false!');
            return false;
        }

        try {
            $import = new \Mtlda\Controllers\ImportController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load ImportController!');
            return false;
        }

        if (!$mbus->sendMessageToClient('import-reply', 'Preparing', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$import->handleQueue()) {
            $this->raiseError(get_class($import) .'::handleQueue() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('import-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleMailImportRequest($job)
    {
        global $mbus, $config;

        if (!$config->isMailImportEnabled()) {
            $this->raiseError(get_class($config) .'::isUserTriggersImportEnabled() returned false!');
            return false;
        }

        if (empty($job) ||
            get_class($job) != 'Thallium\Models\JobModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a JobModel reference as parameter!');
            return false;
        }

        try {
            $importer = new MailImportController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load MailImportController!");
            return false;
        }

        if (!$importer->fetch()) {
            $this->raiseError("MailImportController::fetch() returned false!");
            return false;
        }

        if (!$mbus->sendMessageToClient('mailimport-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleScanDocumentRequests($job)
    {
        global $mtlda, $mbus, $config;

        if (!$config->isPdfIndexingEnabled()) {
            $this->raiseError(get_class($config) .'::isPdfIndexingEnabled() returned false!');
            return false;
        }

        if (empty($job) || get_class($job) != 'Thallium\Models\JobModel') {
            $this->raiseError(__METHOD__ .', requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || !($body = $job->getParameters())) {
            $this->raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($job) .'::getParameters() has not returned a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient('scan-reply', 'Preparing', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!($scan_request = unserialize($body))) {
            $this->raiseError(__METHOD__ .', unable to unserialize message body!');
            return false;
        }

        if (!is_object($scan_request)) {
            $this->raiseError(__METHOD__ .', unserialize() has not returned an object!');
            return false;
        }

        if (!isset($scan_request->id) || empty($scan_request->id) ||
            !isset($scan_request->guid) || empty($scan_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', scan-request is incomplete!');
            return false;
        }

        if (!$mtlda->isValidId($scan_request->id)) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($scan_request->guid)) {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('scan-reply', 'Loading document', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        try {
            $document = new \Mtlda\Models\DocumentModel(
                $scan_request->id,
                $scan_request->guid
            );
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .", unable to load DocumentModel!");
            return false;
        }

        if (!$mtlda->scanDocument($document)) {
            $this->raiseError(get_class($mtlda) .'::scanDocument() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('scan-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleSignRequest($job)
    {
        global $mtlda, $mbus, $config;

        if (!$config->isPdfSigningEnabled()) {
            $this->raiseError(get_class($config) .'::isPdfSigningEnabled() returned false!');
            return false;
        }

        if (empty($job) ||
            get_class($job) != 'Thallium\Models\JobModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || !($body = $job->getParameters())) {
            $this->raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($job) .'::getParameters() has not returned a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-reply', 'Preparing', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!($sign_request = unserialize($body))) {
            $this->raiseError(__METHOD__ .', unable to unserialize message body!');
            return false;
        }

        if (!is_object($sign_request)) {
            $this->raiseError(__METHOD__ .', unserialize() has not returned an object!');
            return false;
        }

        if (!isset($sign_request->id) || empty($sign_request->id) ||
            !isset($sign_request->guid) || empty($sign_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', sign-request is incomplete!');
            return false;
        }

        if (!$mtlda->isValidId($sign_request->id)) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($sign_request->guid)) {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-reply', 'Loading document', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        try {
            $document = new \Mtlda\Models\DocumentModel(
                $sign_request->id,
                $sign_request->guid
            );
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .", unable to load DocumentModel!");
            return false;
        }

        if (!$this->signDocument($document)) {
            $this->raiseError(__CLASS__ .'::signDocument() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleDeleteRequest($job)
    {
        global $mtlda, $mbus;

        if (empty($job) ||
            get_class($job) != 'Thallium\Models\JobModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || !($body = $job->getParameters())) {
            $this->raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($job) .'::getParameters() has not returned a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient('delete-reply', 'Preparing', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (($delete_request = unserialize($body)) === null) {
            $this->raiseError(__METHOD__ .', unable to unserialize message body!');
            return false;
        }

        if (!is_object($delete_request)) {
            $this->raiseError(__METHOD__ .', unserialize() has not returned an object!');
            return false;
        }

        if (!isset($delete_request->id) || empty($delete_request->id) ||
            !isset($delete_request->guid) || empty($delete_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', delete-request is incomplete!');
            return false;
        }

        if ($delete_request->id != 'all' &&
            !$mtlda->isValidId($delete_request->id)
        ) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if ($delete_request->guid != 'all' &&
            !$mtlda->isValidGuidSyntax($delete_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('delete-reply', 'Deleting...', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!isset($delete_request->model) || empty($delete_request->model)) {
            $this->raiseError(__METHOD__ .'(), delete-request does not contain model information!');
            return false;
        }

        if ($delete_request->model == 'queue') {
            $obj_name = '\Mtlda\Models\QueueModel';
            $id = null;
            $guid = null;
        } elseif ($delete_request->model == 'queueitem') {
            $obj_name = '\Mtlda\Models\QueueItemModel';
            $id = $delete_request->id;
            $guid = $delete_request->guid;
        } elseif ($delete_request->model == 'document') {
            $obj_name = '\Mtlda\Models\DocumentModel';
            $id = $delete_request->id;
            $guid = $delete_request->guid;
        } elseif ($delete_request->model == 'keyword') {
            $obj_name = '\Mtlda\Models\KeywordModel';
            $id = $delete_request->id;
            $guid = $delete_request->guid;
        } elseif ($delete_request->model == 'keywords') {
            $obj_name = '\Mtlda\Models\KeywordsModel';
            $id = $delete_request->id;
            $guid = $delete_request->guid;
        } else {
            $this->raiseError(__METHOD__ .'(), delete-request contains an unsupported model!');
            return false;
        }

        try {
            $obj = new $obj_name($id, $guid);
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load QueueModel!');
            return false;
        }

        if (!$obj->permitsRpcActions('delete')) {
            $this->raiseError(__METHOD__ ."(), {$obj_name} does not permit 'delete' action!");
            return false;
        }

        if ($delete_request->id == 'all' && $delete_request->guid == 'all') {
            if (method_exists($obj, 'flush')) {
                $rm_method = 'flush';
            } else {
                $rm_method = 'delete';
            }
            if (!$obj->$rm_method()) {
                $this->raiseError(get_class($obj) ."::${rm_method}() returned false!");
                return false;
            }
            if (!$mbus->sendMessageToClient('delete-reply', 'Done', '100%')) {
                $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }
            return true;
        }

        if (!$obj->delete()) {
            $this->raiseError(get_class($obj) .'::delete() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('delete-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handlePreviewRequest($job)
    {
        global $mtlda, $mbus;

        if (empty($job) || get_class($job) != 'Thallium\Models\JobModel') {
            $this->raiseError(__METHOD__ .', requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || !($body = $job->getParameters())) {
            $this->raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($job) .'::getParameters() has not returned a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient('preview-reply', 'Preparing', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (($preview_request = unserialize($body)) === null) {
            $this->raiseError(__METHOD__ .', unable to unserialize message body!');
            return false;
        }

        if (!is_object($preview_request)) {
            $this->raiseError(__METHOD__ .', unserialize() has not returned an object!');
            return false;
        }

        if (!isset($preview_request->id) || empty($preview_request->id) ||
            !isset($preview_request->guid) || empty($preview_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', preview-request is incomplete!');
            return false;
        }

        if ($preview_request->id != 'all' &&
            !$mtlda->isValidId($preview_request->id)
        ) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if ($preview_request->guid != 'all' &&
            !$mtlda->isValidGuidSyntax($preview_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('preview-reply', 'Preview...', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!isset($preview_request->model) || empty($preview_request->model)) {
            $this->raiseError(__METHOD__ .'(), preview-request does not contain model information!');
            return false;
        }

        if ($preview_request->model != 'queueitem') {
            $this->raiseError(__METHOD__ .'(), unsupported model!');
            return false;
        }

        try {
            $queueitem = new \Mtlda\Models\QueueItemModel(
                $preview_request->id,
                $preview_request->guid
            );
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load QueueItemModel!');
            return false;
        }

        try {
            $image = new \Mtlda\Controllers\ImageController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load ImageController!');
            return false;
        }

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load FPDI!');
            return false;
        }

        if (($fqfn = $queueitem->getFilePath()) === false) {
            $this->raiseError(get_class($queueitem) .'::getFilePath() returned false!');
            return false;
        }

        if (!isset($fqfn) || empty($fqfn)) {
            $this->raiseError(get_class($queueitem) .'::getFilePath() returned an invalid file name!');
            return false;
        }

        if (!file_exists($fqfn)) {
            $this->raiseError(__METHOD__ ."(), file {$fqfn} does not exist!");
            return false;
        }

        if (!is_readable($fqfn)) {
            $this->raiseError(__METHOD__ ."(), file {$fqfn} is not readable!");
            return false;
        }

        try {
            $page_count = $pdf->setSourceFile($fqfn);
        } catch (\Exception $e) {
            $this->raiseError(getClass($pdf) .'::setSourceFile() has thrown an exception! '. $e->getMessage());
            return false;
        }

        for ($page_no = 1; $page_no <= $page_count; $page_no++) {
            if (!$image->createPreviewImage($queueitem, false, $page_no)) {
                $this->raiseError(get_class($image) .'::createPreviewImage() returned false!');
                return false;
            }
        }

        try {
            @$pdf->cleanUp();
        } catch (\Exception $e) {
            $this->raiseError(get_class($pdf) .'::cleanUp() has thrown an exception! '. $e->getMessage());
            return false;
        }

        if (!$mbus->sendMessageToClient('preview-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleSplitRequest($job)
    {
        global $mtlda, $mbus;

        if (empty($job) || get_class($job) != 'Thallium\Models\JobModel') {
            $this->raiseError(__METHOD__ .', requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || !($body = $job->getParameters())) {
            $this->raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($job) .'::getParameters() has not returned a string!');
            return false;
        }

        if (!$mbus->sendMessageToClient('split-reply', 'Preparing', '10%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (($split_request = unserialize($body)) === null) {
            $this->raiseError(__METHOD__ .', unable to unserialize message body!');
            return false;
        }

        if (!is_object($split_request)) {
            $this->raiseError(__METHOD__ .', unserialize() has not returned an object!');
            return false;
        }

        if (!isset($split_request->id) || empty($split_request->id) ||
            !isset($split_request->guid) || empty($split_request->guid)
        ) {
            $this->raiseError(__METHOD__ .', split-request is incomplete!');
            return false;
        }

        if (!$mtlda->isValidId($split_request->id)) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($split_request->guid)) {
            $this->raiseError(__METHOD__ .', \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('split-reply', 'Preview...', '20%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!isset($split_request->model) || empty($split_request->model)) {
            $this->raiseError(__METHOD__ .'(), split-request does not contain model information!');
            return false;
        }

        if ($split_request->model != 'queueitem') {
            $this->raiseError(__METHOD__ .'(), unsupported model!');
            return false;
        }

        try {
            $queueitem = new \Mtlda\Models\QueueItemModel(
                $split_request->id,
                $split_request->guid
            );
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load QueueItemModel!');
            return false;
        }

        try {
            $splitter = new \Mtlda\Controllers\PdfSplittingController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), unable to load PdfSplittingController!');
            return false;
        }

        if (!isset($split_request->documents) ||
            empty($split_request->documents) ||
            !is_string($split_request->documents)
        ) {
            return true;
        }

        if (($json = json_decode($split_request->documents, false, 3)) === null) {
            $this->raiseError(__METHOD__ .'(), json_decode() returned false! '. $this->json_errors[json_last_error()]);
            return false;
        }

        if (empty($json)) {
            return true;
        }

        foreach ($json as $doc => $pages) {
            if (isset($pages) && !empty($pages) && is_string($pages) &&
                !$splitter->splitDocument($queueitem, $pages)) {
                $this->raiseError(get_class($splitter) .'::split() returned false!');
                return false;
            }
        }

        if (!$mbus->sendMessageToClient('split-reply', 'Done', '100%')) {
            $this->raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
