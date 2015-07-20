<?php

namespace MTLDA\Controllers;

use MTLDA\Models;

class ImageController
{
    public function perform()
    {
        global $mtlda, $query;

        if (!isset($query->view) || empty($query->view)) {
            $mtlda->raiseError("\$query->view is not set!");
            return false;
        }

        if ($query->view == "preview") {
            $this->createPreviewImage();
        }

        return true;
    }

    private function createPreviewImage()
    {
        global $mtlda, $query;

        if (!isset($query->params) || !isset($query->params[0]) || empty($query->params[0])) {
            $mtlda->raiseError("\$query->params is not set!");
            return false;
        }

        $id = $query->params[0];

        if (!$mtlda->isValidId($id)) {
            $mtlda->raiseError("\$id is invalid!");
            return false;
        }

        if (($id = $mtlda->parseId($id)) === false) {
            $mtlda->raiseError("unable to parse id!");
            return false;
        }

        if (!$mtlda->isValidGuidSyntax($id->guid)) {
            $mtlda->raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($id->model == "queueitem") {

            $image = new Models\QueueItemModel($id->id, $id->guid);
            if (!$image) {
                $mtlda->raiseError("Unable to load a QueueItemModel!");
                return false;
            }

            $src = MTLDA_BASE.'/data/working/'. $image->queue_file_name;
            if (!file_exists($src)) {
                $mtlda->raiseError("Source does not exist!");
                return false;
            }

            if (!is_readable($src)) {
                $mtlda->raiseError("Source is not readable!");
                return false;
            }

            if ($this->isCachedImageAvailable($id->id, $id->guid, 'preview')) {
                header('Content-Type: image/jpeg');
                print $this->loadCachedImage($id->id, $id->guid, 'preview');
                return true;
            }

            try {
                $im = new \Imagick(MTLDA_BASE.'/data/working/'. $image->queue_file_name .'[0]');
            } catch (ImagickException $e) {
                $mtlda->raiseError("Unable to use imagick extension!");
                return false;
            }
            if (method_exists($im, "setProgressMonitor")) {
                $im->setProgressMonitor(array($this, "updateProgress"));
            }
            if (!$im->setImageFormat('jpg')) {
                $mtlda->raiseError("Unable to set jpg image format!");
                return false;
            }
            if (!$im->scaleImage(300, 300, true)) {
                $mtlda->raiseError("Unable to scale image!");
                return false;
            }

            header('Content-Type: image/jpeg');
            echo $im->getImageBlob();

            if ($mtlda->isImageCachingEnabled()) {
                $this->saveImageToCache($id->id, $id->guid, 'preview', $im);
            }
        }
    }

    public function updateProgress()
    {

    }

    private function isCachedImageAvailable($id, $guid, $prefix)
    {
        $file = MTLDA_BASE."/data/image_cache/{$prefix}_{$id}_{$guid}.jpg";

        if (file_exists($file) && is_readable($file)) {
            return true;
        }

        return false;
    }

    private function loadCachedImage($id, $guid, $prefix)
    {
        global $mtlda;

        $file = MTLDA_BASE."/data/image_cache/{$prefix}_{$id}_{$guid}.jpg";

        if (($content = file_get_contents($file)) === false) {
            $mtlda->raiseError("Unable to read file {$file}!");
            return false;
        }

        return $content;
    }

    private function saveImageToCache($id, $guid, $prefix, &$im)
    {
        global $mtlda;

        $file = MTLDA_BASE."/data/image_cache/{$prefix}_{$id}_{$guid}.jpg";

        if (($fp = fopen($file, 'w')) === false) {
            $mtlda->raiseError("Unable to write to {$file}");
            return false;
        }

        if (fwrite($fp, $im->getImageBlob()) === false) {
            $mtlda->raiseError("fwrite() returned unsuccessful");
            return false;
        }

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
