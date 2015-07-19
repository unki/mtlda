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

        if (($id_parts = $mtlda->parseId($id)) === false) {
            $mtlda->raiseError("unable to parse id!");
            return false;
        }

        if (!isset($id_parts) || empty($id_parts) || count($id_parts) != 3) {
            $mtlda->raiseError("incomplete id!");
            return false;
        }

        $item = $id_parts[0];
        $id = $id_parts[1];
        $guid = $id_parts[2];

        if (!$mtlda->isValidGuidSyntax($guid)) {
            $mtlda->raiseError("GUID syntax is invalid!");
            return false;
        }

        if ($item == "queueitem") {
            $image = new Models\QueueItemModel($id, $guid);
            if (!$image) {
                $mtlda->raiseError("Unable to load a QueueItemModel!");
                return false;
            }

            $im = new \imagick('../data/working/'. $image->queue_file_name .'[0]');
            $im->setImageFormat('jpg');
            header('Content-Type: image/jpeg');
            echo $im;
        }
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
