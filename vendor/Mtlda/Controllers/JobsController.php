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
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to register handlers!', true);
            return false;
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

        if (!($sessionid = $job->getSessionId())) {
            $this->raiseError(get_class($job) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($job) .'::getSessionId() has not returned a string!');
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

        if (!($sessionid = $job->getSessionId())) {
            $this->raiseError(get_class($job) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($job) .'::getSessionId() has not returned a string!');
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

        if (!($sessionid = $job->getSessionId())) {
            $this->raiseError(get_class($job) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($job) .'::getSessionId() has not returned a string!');
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

        if (!$this->scanDocument($document)) {
            $this->raiseError(__CLASS__ .'::scanDocument() returned false!');
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

        if (!($sessionid = $job->getSessionId())) {
            $this->raiseError(get_class($job) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($job) .'::getSessionId() has not returned a string!');
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

        if (!($sessionid = $job->getSessionId())) {
            $this->raiseError(get_class($job) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($job) .'::getSessionId() has not returned a string!');
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

        if (!$mbus->sendMessageToClient('delete-reply', 'Deleting document(s)', '20%')) {
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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
