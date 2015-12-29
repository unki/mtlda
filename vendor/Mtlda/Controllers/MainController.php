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
    const VERSION = "0.9";

    public function __construct($mode = null)
    {
        if (!$this->setNamespacePrefix('Mtlda')) {
            $this->raiseError(__METHOD__ .'(), unable to set namespace prefix!', true);
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
        global $jobs;

        if (isset($mode) and $mode == "queue_only") {
            if (!$jobs->createJob('import-request')) {
                $this->raiseError(get_class($jobs) .'::createJob() returned false!');
                return false;
            }
        }

        try {
            $this->registerHandler('image', array($this, 'imageHandler'));
            $this->registerHandler('document', array($this, 'documentHandler'));
            $this->registerHandler('upload', array($this, 'uploadHandler'));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to register handlers!', true);
        }
        return true;
    }

    public function callHandlers()
    {
        global $router;

        if ($router->isImageCall()) {
            if (!$this->callHandler('image')) {
                $this->raiseError(__CLASS__ .'::callHandler() returned false!');
                return false;
            }
            return true;
        } elseif ($router->isDocumentCall()) {
            if (!$this->callHandler('document')) {
                $this->raiseError(__CLASS__ .'::callHandler() returned false!');
                return false;
            }
            return true;

        }

        return parent::callHandlers();
    }

    protected function imageHandler()
    {
        $this->loadController("Image", "image");
        global $image;

        if (!$image->perform()) {
            $this->raiseError(get_class($image) .'::perform() returned false!');
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
            $this->raiseError(get_class($document) .'::perform() returned false!');
            return false;
        }

        unset($document);
        return true;
    }

    private function signDocument(&$document)
    {
        if (!is_a($document, "Mtlda\Models\DocumentModel")) {
            $this->raiseError(__METHOD__ .', can only work with DocumentModels!');
            return false;
        }

        if ($document->isSignedCopy()) {
            $this->raiseError(__METHOD__ .", will not resign an already signed document!");
            return false;
        }

        try {
            $archive = new \Mtlda\Controllers\ArchiveController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load ArchiveController!');
            return false;
        }

        if (!$archive) {
            $this->raiseError(__METHOD__ .'(), unable to load ArchiveController!');
            return false;
        }

        if (!$archive->sign($document)) {
            $this->raiseError(get_class($archive) .'::sign() returned false!');
            return false;
        }

        return true;
    }

    public function scanDocument(&$document)
    {
        if (!isset($document) || empty($document) ||
            (!is_a($document, "Mtlda\Models\DocumentModel") &&
            !is_a($document, "Mtlda\Models\QueueItemModel"))
        ) {
            $this->raiseError(__METHOD__ .', unable to work with provided model!');
            return false;
        }

        if (is_a($document, "Mtlda\Models\DocumentModel") && (
            $document->isSignedCopy() || $document->getVersion() != 1)
        ) {
            $this->raiseError(__METHOD__ .", will only scan the original document!");
            return false;
        }

        try {
            $parser = new \Mtlda\Controllers\PdfIndexerController;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load PdfIndexerController!');
            return false;
        }

        if (!$parser) {
            $this->raiseError(__METHOD__ .'(), unable to load PdfIndexerController!');
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
