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

class ImageController extends DefaultController
{
    private $image_cache;

    public function __construct()
    {
        $this->image_cache = $this::CACHE_DIRECTORY ."/image_cache";
    }

    public function perform()
    {
        global $mtlda, $query;

        if (!isset($query->view) || empty($query->view)) {
            $this->raiseError("\$query->view is not set!");
            return false;
        }

        if ($query->view == "preview") {
            $this->requestPreviewImage();
        }

        return true;
    }

    private function requestPreviewImage()
    {
        global $mtlda, $query;

        if (!isset($query->params) || !isset($query->params[0]) || empty($query->params[0])) {
            $this->raiseError("\$query->params is not set!");
            return false;
        }

        $id = $query->params[0];

        if (!$mtlda->isValidId($id)) {
            $this->raiseError("\$id is invalid!");
            return false;
        }

        if (($id = $mtlda->parseId($id)) === false) {
            $this->raiseError("unable to parse id!");
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            $this->raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($id->model == "queueitem") {
            $item = new \Mtlda\Models\QueueItemModel($id->id, $id->guid);
            if (!$item) {
                $this->raiseError("Unable to load a QueueItemModel!");
                return false;
            }
        }

        if (!($image = $this->createPreviewImage($item))) {
            $this->raiseError("createPreviewImage() returned false!");
            return false;
        }

        header('Content-Type: image/jpeg');
        print $image;
        unset($image);

        return true;
    }

    public function createPreviewImage(&$item, $return_content = true)
    {
        global $mtlda, $config, $audit;

        if (!isset($item) || empty($item)) {
            $this->raiseError("createPreviewImage() invalid parameter!");
            return false;
        }

        if (!isset($item->queue_idx, $item->queue_guid, $item->queue_file_name)) {
            $this->raiseError("createPreviewImage() parameter incomplete!");
            return false;
        }

        if (!$src = $item->getFilePath()) {
            $this->raiseError(get_class($item) ."::getFilePath() returned false!");
            return false;
        }

        if (!file_exists($src)) {
            $this->raiseError("Source does not exist!");
            return false;
        }

        if (!is_readable($src)) {
            $this->raiseError("Source is not readable!");
            return false;
        }

        if ($this->isCachedImageAvailable($item->queue_idx, $item->queue_guid, 'queueitem_preview')) {
            return $this->loadCachedImage($item->queue_idx, $item->queue_guid, 'queueitem_preview');
        }

        try {
            $audit->log(
                __METHOD__,
                "read",
                "queue",
                $item->getGuid()
            );
        } catch (\Exception $e) {
            $this->raiseError("AuditController::log() raised an exception!");
            return false;
        }

        try {
            $im = new \Imagick($src .'[0]');
        } catch (ImagickException $e) {
            $this->raiseError("Unable to load imagick class!");
            return false;
        }

        if (method_exists($im, "setProgressMonitor")) {
            $im->setProgressMonitor(array($this, "updateProgress"));
        }

        if (!$im->setImageFormat('jpg')) {
            $this->raiseError("Unable to set jpg image format!");
            return false;
        }

        if (!$im->scaleImage(300, 300, true)) {
            $this->raiseError("Unable to scale image!");
            return false;
        }

        if ($config->isImageCachingEnabled()) {
            $this->saveImageToCache($item->queue_idx, $item->queue_guid, 'queueitem_preview', $im);
        }

        if (!($content = $im->getImageBlob())) {
            $this->raiseError("imagick returned nothing!");
            return false;
        }

        if ($return_content) {
            return $content;
        }

        return true;
    }

    public function updateProgress()
    {
    }

    private function isCachedImageAvailable($id, $guid, $prefix)
    {
        $file = "{$this->image_cache}/{$prefix}_{$id}_{$guid}.jpg";

        if (file_exists($file) && is_readable($file)) {
            return true;
        }

        return false;
    }

    private function loadCachedImage($id, $guid, $prefix)
    {
        global $mtlda;

        $file = "{$this->image_cache}/{$prefix}_{$id}_{$guid}.jpg";

        if (($content = file_get_contents($file)) === false) {
            $this->raiseError("Unable to read file {$file}!");
            return false;
        }

        return $content;
    }

    private function saveImageToCache($id, $guid, $prefix, &$im)
    {
        global $mtlda;

        $file = "{$this->image_cache}/{$prefix}_{$id}_{$guid}.jpg";

        if (($fp = fopen($file, 'w')) === false) {
            $this->raiseError("Unable to write to {$file}");
            return false;
        }

        if (fwrite($fp, $im->getImageBlob()) === false) {
            $this->raiseError("fwrite() returned unsuccessful");
            return false;
        }
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4: