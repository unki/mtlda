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
            $this->registerHandler('preview-request', array($this, 'handlePreviewRequest'));
            $this->registerHandler('split-request', array($this, 'handleSplitRequest'));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to register handlers!', true);
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

        if (!$mbus->sendMessageToClient('archive-reply', 'Preparing', '10%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'(), requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || ($archive_request = $job->getParameters()) === false) {
            static::raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_object($archive_request)) {
            static::raiseError(get_class($job) .'::getParameters() returned invalid data!');
            return false;
        }

        if (!isset($archive_request->id) || empty($archive_request->id) ||
            !isset($archive_request->guid) || empty($archive_request->guid)
        ) {
            static::raiseError(__METHOD__ .'(), archive-request is incomplete!');
            return false;
        }

        if ($archive_request->id != 'all' &&
            !$mtlda->isValidId($archive_request->id)
        ) {
            static::raiseError(__METHOD__ .'(), \$id is invalid!');
            return false;
        }

        if ($archive_request->guid != 'all' &&
            !$mtlda->isValidGuidSyntax($archive_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() \$guid is invalid!');
            return false;
        }

        try {
            $queue = new \Mtlda\Controllers\QueueController;
        } catch (\Exception $e) {
            static::raiseError("Failed to load QueueController!");
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Loading document', '20%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if ($archive_request->id == 'all' && $archive_request->guid == 'all') {
            if (!$queue->archiveAll()) {
                static::raiseError(get_class($queue) .'::archiveAll() returned false!');
                return false;
            }
            if (!$mbus->sendMessageToClient('archive-reply', 'Done', '100%')) {
                static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
                return false;
            }
            return true;
        }

        if (!$queue->archive($archive_request->id, $archive_request->guid)) {
            static::raiseError(get_class($queue) .'::archive() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('archive-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleImportRequest()
    {
        global $mbus, $config;

        if (!$config->isUserTriggersImportEnabled()) {
            static::raiseError(get_class($config) .'::isUserTriggersImportEnabled() returned false!');
            return false;
        }

        try {
            $import = new \Mtlda\Controllers\ImportController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ImportController!');
            return false;
        }

        if (!$mbus->sendMessageToClient('import-reply', 'Preparing', '10%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$import->handleQueue()) {
            static::raiseError(get_class($import) .'::handleQueue() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('import-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleMailImportRequest($job)
    {
        global $mbus, $config;

        if (!$config->isMailImportEnabled()) {
            static::raiseError(get_class($config) .'::isUserTriggersImportEnabled() returned false!');
            return false;
        }

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'(), requires a JobModel reference as parameter!');
            return false;
        }

        try {
            $importer = new \Mtlda\Controllers\MailImportController;
        } catch (\Exception $e) {
            static::raiseError("Failed to load MailImportController!");
            return false;
        }

        if (!$importer->fetch()) {
            static::raiseError("MailImportController::fetch() returned false!");
            return false;
        }

        if (!$mbus->sendMessageToClient('mailimport-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleScanDocumentRequests($job)
    {
        global $mtlda, $mbus, $config;

        if (!$mbus->sendMessageToClient('scan-reply', 'Preparing', '10%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$config->isPdfIndexingEnabled()) {
            static::raiseError(get_class($config) .'::isPdfIndexingEnabled() returned false!');
            return false;
        }

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'(), requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || ($scan_request = $job->getParameters()) === false) {
            static::raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_object($scan_request)) {
            static::raiseError(get_class($job) .'::getParameters() returned invalid data!');
            return false;
        }

        if (!isset($scan_request->id) || empty($scan_request->id) ||
            !isset($scan_request->guid) || empty($scan_request->guid) ||
            !isset($scan_request->model) || empty($scan_request->model) ||
            !in_array($scan_request->model, array('document', 'queueitem'))
        ) {
            static::raiseError(__METHOD__ .'(), scan-request is incomplete!');
            return false;
        }

        if (!$mtlda->isValidId($scan_request->id)) {
            static::raiseError(__METHOD__ .'(), \$id is invalid!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($scan_request->guid)) {
            static::raiseError(__METHOD__ .'(), \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('scan-reply', 'Loading document', '20%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (($document = $mtlda->loadModel(
            $scan_request->model,
            $scan_request->id,
            $scan_request->guid
        )) === false) {
            static::raiseError(__METHOD__ .", unable to load DocumentModel!");
            return false;
        }

        if (!$mtlda->scanDocument($document)) {
            static::raiseError(get_class($mtlda) .'::scanDocument() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('scan-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleSignRequest($job)
    {
        global $mtlda, $mbus, $config;

        if (!$mbus->sendMessageToClient('sign-reply', 'Preparing', '10%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!$config->isPdfSigningEnabled()) {
            static::raiseError(get_class($config) .'::isPdfSigningEnabled() returned false!');
            return false;
        }

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'(), requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || ($sign_request = $job->getParameters()) === false) {
            static::raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_object($sign_request)) {
            static::raiseError(get_class($job) .'::getParameters() returned invalid data!');
            return false;
        }

        if (!isset($sign_request->id) || empty($sign_request->id) ||
            !isset($sign_request->guid) || empty($sign_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() sign-request is incomplete!');
            return false;
        }

        if (!$mtlda->isValidId($sign_request->id)) {
            static::raiseError(__METHOD__ .'() \$id is invalid!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($sign_request->guid)) {
            static::raiseError(__METHOD__ .'() \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-reply', 'Loading document', '20%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        try {
            $document = new \Mtlda\Models\DocumentModel(array(
                'idx' => $sign_request->id,
                'guid' => $sign_request->guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .", unable to load DocumentModel!");
            return false;
        }

        if (!$this->signDocument($document)) {
            static::raiseError(__CLASS__ .'::signDocument() returned false!');
            return false;
        }

        if (!$mbus->sendMessageToClient('sign-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handlePreviewRequest($job)
    {
        global $mtlda, $mbus;

        if (!$mbus->sendMessageToClient('preview-reply', 'Preparing', '10%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'() requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || ($preview_request = $job->getParameters()) === false) {
            static::raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_object($preview_request)) {
            static::raiseError(get_class($job) .'::getParameters() returned invalid data!');
            return false;
        }

        if (!isset($preview_request->id) || empty($preview_request->id) ||
            !isset($preview_request->guid) || empty($preview_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() preview-request is incomplete!');
            return false;
        }

        if ($preview_request->id != 'all' &&
            !$mtlda->isValidId($preview_request->id)
        ) {
            static::raiseError(__METHOD__ .'() \$id is invalid!');
            return false;
        }

        if ($preview_request->guid != 'all' &&
            !$mtlda->isValidGuidSyntax($preview_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('preview-reply', 'Preview...', '20%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!isset($preview_request->model) || empty($preview_request->model)) {
            static::raiseError(__METHOD__ .'(), preview-request does not contain model information!');
            return false;
        }

        if ($preview_request->model != 'queueitem') {
            static::raiseError(__METHOD__ .'(), unsupported model!');
            return false;
        }

        try {
            $queueitem = new \Mtlda\Models\QueueItemModel(array(
                'idx' => $preview_request->id,
                'guid' => $preview_request->guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load QueueItemModel!');
            return false;
        }

        try {
            $image = new \Mtlda\Controllers\ImageController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load ImageController!');
            return false;
        }

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load FPDI!');
            return false;
        }

        if (($fqfn = $queueitem->getFilePath()) === false) {
            static::raiseError(get_class($queueitem) .'::getFilePath() returned false!');
            return false;
        }

        if (!isset($fqfn) || empty($fqfn)) {
            static::raiseError(get_class($queueitem) .'::getFilePath() returned an invalid file name!');
            return false;
        }

        if (!file_exists($fqfn)) {
            static::raiseError(__METHOD__ ."(), file {$fqfn} does not exist!");
            return false;
        }

        if (!is_readable($fqfn)) {
            static::raiseError(__METHOD__ ."(), file {$fqfn} is not readable!");
            return false;
        }

        try {
            $page_count = $pdf->setSourceFile($fqfn);
        } catch (\Exception $e) {
            static::raiseError(getClass($pdf) .'::setSourceFile() has thrown an exception! '. $e->getMessage());
            return false;
        }

        for ($page_no = 1; $page_no <= $page_count; $page_no++) {
            if (!$image->createPreviewImage($queueitem, false, $page_no, 300)) {
                static::raiseError(get_class($image) .'::createPreviewImage() returned false!');
                return false;
            }
            if (!$image->createPreviewImage($queueitem, false, $page_no, 'full')) {
                static::raiseError(get_class($image) .'::createPreviewImage() returned false!');
                return false;
            }
        }

        try {
            @$pdf->cleanUp();
        } catch (\Exception $e) {
            static::raiseError(get_class($pdf) .'::cleanUp() has thrown an exception! '. $e->getMessage());
            return false;
        }

        if (!$mbus->sendMessageToClient('preview-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }

    protected function handleSplitRequest($job)
    {
        global $mtlda, $mbus;

        if (!$mbus->sendMessageToClient('split-reply', 'Preparing', '10%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (empty($job) || !is_a($job, 'Thallium\Models\JobModel')) {
            static::raiseError(__METHOD__ .'() requires a JobModel reference as parameter!');
            return false;
        }

        if (!$job->hasParameters() || ($split_request = $job->getParameters()) === false) {
            static::raiseError(get_class($job) .'::getParameters() returned false!');
            return false;
        }

        if (!is_object($split_request)) {
            static::raiseError(get_class($job) .'::getParameters() returned invalid data!');
            return false;
        }

        if (!isset($split_request->id) || empty($split_request->id) ||
            !isset($split_request->guid) || empty($split_request->guid)
        ) {
            static::raiseError(__METHOD__ .'() split-request is incomplete!');
            return false;
        }

        if (!$mtlda->isValidId($split_request->id)) {
            static::raiseError(__METHOD__ .'() \$id is invalid!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($split_request->guid)) {
            static::raiseError(__METHOD__ .'() \$guid is invalid!');
            return false;
        }

        if (!$mbus->sendMessageToClient('split-reply', 'Splitting...', '20%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        if (!isset($split_request->model) || empty($split_request->model)) {
            static::raiseError(__METHOD__ .'(), split-request does not contain model information!');
            return false;
        }

        if ($split_request->model != 'queueitem') {
            static::raiseError(__METHOD__ .'(), unsupported model!');
            return false;
        }

        try {
            $queueitem = new \Mtlda\Models\QueueItemModel(array(
                'idx' => $split_request->id,
                'guid' => $split_request->guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load QueueItemModel!');
            return false;
        }

        try {
            $splitter = new \Mtlda\Controllers\PdfSplittingController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), unable to load PdfSplittingController!');
            return false;
        }

        if (!isset($split_request->documents) ||
            empty($split_request->documents) ||
            !is_string($split_request->documents)
        ) {
            return true;
        }

        if (($json = json_decode($split_request->documents, false, 3)) === null) {
            static::raiseError(__METHOD__ .'(), json_decode() returned false! '. $this->json_errors[json_last_error()]);
            return false;
        }

        if (empty($json)) {
            return true;
        }

        foreach ($json as $doc => $options) {
            if (!isset($options) || empty($options) || !is_object($options) || !is_a($options, 'stdClass')) {
                static::raiseError(__METHOD__ ."(), parameters for document {$doc} are invalid!");
                return false;
            }

            if (!isset($options->pages) || empty($options->pages)) {
                continue;
            }

            if (($newdoc = $splitter->splitDocument($queueitem, $options->pages)) === false) {
                static::raiseError(get_class($splitter) .'::splitDocument() returned false!');
                return false;
            }

            if (isset($options->title) && !empty($options->title) && is_string($options->title)) {
                if (!$newdoc->setTitle($options->title)) {
                    static::raiseError(get_class($newdoc) .'::setTitle() returned false!');
                    return false;
                }
            }

            if (isset($options->file_name) && !empty($options->file_name) && is_string($options->file_name)) {
                if (!$newdoc->setFileName($options->file_name)) {
                    static::raiseError(get_class($newdoc) .'::setFileName() returned false!');
                    return false;
                }
            }

            if (!$newdoc->save()) {
                static::raiseError(get_class($newdoc) .'::save() returned false!');
                return false;
            }
        }

        if (!$mbus->sendMessageToClient('split-reply', 'Done', '100%')) {
            static::raiseError(get_class($mbus) .'::sendMessageToClient() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
