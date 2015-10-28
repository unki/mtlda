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

class MainController extends \Thallium\Controllers\MainController
{
    const VERSION = "0.7";

    public function __construct($mode = null)
    {
        if (!$this->setNamespacePrefix('Mtlda')) {
            $this->raiseError('Unable to set namespace prefix!', true);
            return false;
        }

        try {
            $this->registerModel('archive', 'ArchiveModel');
            $this->registerModel('documentindex', 'DocumentIndexModel');
            $this->registerModel('documentindices', 'DocumentIndicesModel');
            $this->registerModel('document', 'DocumentModel');
            $this->registerModel('documentproperties', 'DocumentPropertiesModel');
            $this->registerModel('documentproperty', 'DocumentPropertyModel');
            $this->registerModel('keywordassignment', 'KeywordAssignmentModel');
            $this->registerModel('keyword', 'KeywordModel');
            $this->registerModel('keywords', 'KeywordsModel');
            $this->registerModel('queueitem', 'QueueItemModel');
            $this->registerModel('queue', 'QueueModel');
        } catch (\Exception $e) {
            $this->raiseError(__CLASS__ .'::__construct(), error on registering models!"', true);
            return false;
        }

        $GLOBALS['mtlda'] =& $this;

        parent::__construct();

        if (isset($mode) and $mode == "queue_only") {
            $this->loadController("Import", "import");
            global $import;

            if (!$import->handleQueue()) {
                $this->raiseError("ImportController::handleQueue returned false!");
                return false;
            }

            unset($import);
        }

        return true;
    }

    public function startup()
    {
        global $config, $db, $router, $query;

        if (!isset($query->view)) {
            $this->raiseError("Error - parsing request URI hasn't unveiled what to view!");
            return false;
        }

        $this->loadController("Views", "views");
        global $views;

        if ($router->isRpcCall()) {
            if (!$this->rpcHandler()) {
                $this->raiseError("MainController::rpcHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($router->isImageCall()) {
            if (!$this->imageHandler()) {
                $this->raiseError("MainController::imageHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($router->isDocumentCall()) {
            if (!$this->documentHandler()) {
                $this->raiseError("MainController::documentHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($router->isUploadCall()) {
            if (!$this->uploadHandler()) {
                $this->raiseError("MainController::uploadHandler() returned false!");
                return false;
            }
            return true;

        } elseif ($page_name = $views->getViewName($query->view)) {
            if (!$page = $views->load($page_name)) {
                $this->raiseError("ViewController:load() returned false!");
                return false;
            }

            print $page;
            return true;
        }

        $this->raiseError("Unable to find a view for ". $query->view);
        return false;
    }

    protected function imageHandler()
    {
        $this->loadController("Image", "image");
        global $image;

        if (!$image->perform()) {
            $this->raiseError("ImageController::perform() returned false!");
            return false;
        }

        unset($image);
        return true;
    }

    protected function documentHandler()
    {
        $this->loadController("Document", "document");
        global $document;

        if (!$document->perform()) {
            $this->raiseError("DocumentController::perform() returned false!");
            return false;
        }

        unset($document);
        return true;
    }

    protected function handleMessage(&$message)
    {
        global $jobs;

        if (!($result = parent::handleMessage($message))) {
            $this->raiseError(get_class(parent) .'::handleMessage() returned false!');
            return false;
        }

        $command = $result['command'];
        $job = $result['job'];

        switch ($command) {
            default:
                $this->raiseError(__METHOD__ .', unknown command \"'. $command .'\" found!');
                return false;
                break;

            case 'sign-request':
                if (!$this->handleSignRequest($message)) {
                    $this->raiseError('handleSignRequest() returned false!');
                    return false;
                }
                break;

            case 'mailimport-request':
                if (!$this->handleMailImportRequest($message)) {
                    $this->raiseError('handleMailImportRequest() returned false!');
                    return false;
                }
                break;

            case 'scan-request':
                if (!$this->handleScanDocumentRequests($message)) {
                    $this->raiseError('handleScanDocumentRequests() returned false!');
                    return false;
                }
                break;
        }

        if (!$jobs->deleteJob($job)) {
            $this->raiseError(get_class($jobs) .'::deleteJob() returned false!');
            return false;
        }

        return true;
    }

    protected function handleSignRequest(&$message)
    {
        global $mbus;

        if (empty($message) ||
            get_class($message) != 'Thallium\Models\MessageModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a MessageModel reference as parameter!');
            return false;
        }

        if (!$message->hasBody() || !($body = $message->getBody())) {
            $this->raiseError(get_class($message) .'::getBody() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($message) .'::getBody() has not returned a string!');
            return false;
        }

        if (!($sessionid = $message->getSessionId())) {
            $this->raiseError(get_class($message) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($message) .'::getSessionId() has not returned a string!');
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

        if (!$this->isValidId($sign_request->id)) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if (!$this->isValidGuidSyntax($sign_request->guid)) {
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

    protected function handleMailImportRequest(&$message)
    {
        global $mbus;

        if (empty($message) ||
            get_class($message) != 'Thallium\Models\MessageModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a MessageModel reference as parameter!');
            return false;
        }

        if (!($sessionid = $message->getSessionId())) {
            $this->raiseError(get_class($message) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($message) .'::getSessionId() has not returned a string!');
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

    private function signDocument(&$document)
    {
        if (get_class($document) != "Mtlda\Models\DocumentModel") {
            $this->raiseError(__METHOD__ .', can only work with DocumentModels!');
            return false;
        }

        if ($document->document_signed_copy == 'Y') {
            $this->raiseError(__METHOD__ .", will not resign an already signed document!");
            return false;
        }

        try {
            $archive = new \Mtlda\Controllers\ArchiveController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load ArchiveController!");
            return false;
        }

        if (!$archive) {
            $this->raiseError("Unable to load ArchiveController!");
            return false;
        }

        if (!$archive->sign($document)) {
            $this->raiseError("ArchiveController::sign() returned false!");
            return false;
        }

        return true;
    }

    private function handleScanDocumentRequests(&$message)
    {
        global $mbus;

        if (empty($message) ||
            get_class($message) != 'Thallium\Models\MessageModel'
        ) {
            $this->raiseError(__METHOD__ .', requires a MessageModel reference as parameter!');
            return false;
        }

        if (!$message->hasBody() || !($body = $message->getBody())) {
            $this->raiseError(get_class($message) .'::getBody() returned false!');
            return false;
        }

        if (!is_string($body)) {
            $this->raiseError(get_class($message) .'::getBody() has not returned a string!');
            return false;
        }

        if (!($sessionid = $message->getSessionId())) {
            $this->raiseError(get_class($message) .'::getSessionId() returned false!');
            return false;
        }

        if (!is_string($sessionid)) {
            $this->raiseError(get_class($message) .'::getSessionId() has not returned a string!');
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

        if (!$this->isValidId($scan_request->id)) {
            $this->raiseError(__METHOD__ .', \$id is invalid!');
            return false;
        }

        if (!$this->isValidGuidSyntax($scan_request->guid)) {
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

    private function scanDocument(&$document)
    {
        if (get_class($document) != "Mtlda\Models\DocumentModel") {
            $this->raiseError(__METHOD__ .', can only work with DocumentModels!');
            return false;
        }

        if ($document->document_signed_copy == 'Y' || $document->document_version != 1) {
            $this->raiseError(__METHOD__ .", will only scan the original document!");
            return false;
        }

        try {
            $parser = new \Mtlda\Controllers\PdfIndexerController;
        } catch (\Exception $e) {
            $this->raiseError("Failed to load PdfIndexerController!");
            return false;
        }

        if (!$parser) {
            $this->raiseError("Unable to load PdfIndexerController!");
            return false;
        }

        if (!$parser->scan($document)) {
            $this->raiseError(get_class($parser) .'::scan() returned false!');
            return false;
        }

        return true;
    }

    public function isBelowDirectory($dir, $topmost = self::DATA_DIRECTORY)
    {
        if (!(parent::isBelowDirectory($dir, $topmost))) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
