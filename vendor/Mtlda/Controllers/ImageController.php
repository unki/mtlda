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
            $this->raiseError(__METHOD__ .'(), $query->view is not set!');
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
            $this->raiseError(__METHOD__ .'(), $query->params is not set!');
            return false;
        }

        $id = $query->params[0];

        if (!$mtlda->isValidId($id)) {
            $this->raiseError(__METHOD__ .'(), $id is invalid!');
            return false;
        }

        if (($id = $mtlda->parseId($id)) === false) {
            $this->raiseError(__METHOD__ .'(), unable to parse id!');
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            $this->raiseError(__METHOD__ .'(), GUID syntax is invalid!');
            return false;
        }

        if ($id->model == "queueitem") {
            $item = new \Mtlda\Models\QueueItemModel($id->id, $id->guid);
        } else {
            $this->raiseError(__METHOD__ .'(), unsupported model requested!');
            return false;
        }

        if (!$item) {
            $this->raiseError(__METHOD__ .'(), unable to load model!');
            return false;
        }

        if (isset($query->params[1]) && !empty($query->params[1]) && is_numeric($query->params[1])) {
            $page_no = $query->params[1];
        } else {
            $page_no = 1;
        }

        if (isset($query->params[2]) && !empty($query->params[2]) &&
            (is_numeric($query->params[2]) || is_string($query->params[2]))
        ) {
            $size = $query->params[2];
        } else {
            $size = 300;
        }

        if (!($image = $this->createPreviewImage($item, true, $page_no, $size))) {
            $this->raiseError("createPreviewImage() returned false!");
            return false;
        }

        header('Content-Type: image/jpeg');
        print $image;
        unset($image);

        return true;
    }

    public function createPreviewImage(&$item, $return_content = true, $page = 1, $size = 300)
    {
        global $mtlda, $config, $audit;

        if (!isset($item) || empty($item)) {
            $this->raiseError(__METHOD__ .'(), $item parameter is invalid!');
            return false;
        }

        if (!isset($return_content) || !is_bool($return_content)) {
            $this->raiseError(__METHOD__ .'(), $return_content parameter is invalid!');
            return false;
        }

        if (!isset($page) || !is_numeric($page) || $page < 1) {
            $this->raiseError(__METHOD__ .'(), $page parameter is invalid!');
            return false;
        }

        if (!isset($size) ||
            (!is_numeric($size) && !is_string($size)) ||
            (is_numeric($size) && ($size < 0 || $size > 2048)) ||
            (is_string($size) && $size != 'full')
        ) {
            $this->raiseError(__METHOD__ .'(), $size parameter is invalid!');
            return false;
        }

        if ($size == 0) {
            $size = 'full';
        }

        if (!is_a($item, 'Mtlda\Models\QueueItemModel')) {
            $this->raiseError(__METHOD__ .'(), can only operate with QueueItemModels!');
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

        if ($this->isCachedImageAvailable($item->getId(), $item->getGuid(), "queueitem_preview_{$size}", $page)) {
            return $this->loadCachedImage($item->getId(), $item->getGuid(), "queueitem_preview_{$size}", $page);
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

        $page_id = $page-1;

        try {
            $im = new \Imagick($src ."[{$page_id}]");
        } catch (ImagickException $e) {
            $this->raiseError(__METHOD__ .'(), unable to load imagick class!');
            return false;
        }

        if (method_exists($im, "setProgressMonitor")) {
            $im->setProgressMonitor(array($this, "updateProgress"));
        }

        if (!$im->setImageFormat('jpg')) {
            $this->raiseError(get_class($im) .'::setImageFormat() returned false!');
            return false;
        }

        if (is_numeric($size) && $size > 0) {
            if (!$im->scaleImage($size, $size, true)) {
                $this->raiseError(get_class($im) .'::scaleImage() returned false!');
                return false;
            }
        }

        if ($config->isImageCachingEnabled()) {
            if (!$this->saveImageToCache($item->getId(), $item->getGuid(), "queueitem_preview_{$size}", $page, $im)) {
                $this->raiseError(__CLASS__ .'::saveImageToCache() returned false!');
                return false;
            }
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

    private function isCachedImageAvailable($id, $guid, $prefix, $page)
    {
        $file = "{$this->image_cache}/{$prefix}_{$id}_{$guid}_{$page}.jpg";

        if (file_exists($file) && is_readable($file)) {
            return true;
        }

        return false;
    }

    private function loadCachedImage($id, $guid, $prefix, $page)
    {
        global $mtlda;

        $file = "{$this->image_cache}/{$prefix}_{$id}_{$guid}_{$page}.jpg";

        if (($content = file_get_contents($file)) === false) {
            $this->raiseError("Unable to read file {$file}!");
            return false;
        }

        return $content;
    }

    private function saveImageToCache($id, $guid, $prefix, $page, &$im)
    {
        global $mtlda;

        $file = "{$this->image_cache}/{$prefix}_{$id}_{$guid}_{$page}.jpg";

        if (($fp = fopen($file, 'w')) === false) {
            $this->raiseError("Unable to write to {$file}");
            return false;
        }

        if (fwrite($fp, $im->getImageBlob()) === false) {
            $this->raiseError("fwrite() returned unsuccessful");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
