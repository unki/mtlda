<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017> <Andreas Unterkircher>
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

namespace Mtlda\Views;

class DocumentView extends DefaultView
{
    protected static $view_class_name = 'document';

    public function showItem($id, $guid)
    {
        global $mtlda;

        if (empty($id) || !$mtlda->isValidId($id)) {
            static::raiseError(__METHOD__ .'(), require a valid $id to show!');
            return false;
        }

        if (empty($guid) || !$mtlda->isValidGuidSyntax($guid)) {
            static::raiseError(__METHOD__ .'(), require a valid $guid to show!');
            return false;
        }

        try {
            $item = new \Mtlda\Models\DocumentModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ ."(), failed to load DocumentModel({$id}, {$guid})!");
            return false;
        }

        try {
            $storage = new \Mtlda\Controllers\StorageController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load StorageController!');
            return false;
        }

        if (!$file = $storage->retrieveFile($item)) {
            static::raiseError(get_class($storage) .'::retrieveFile() returned false!');
            return false;
        }

        if (!isset($file) ||
            empty($file) ||
            !is_array($file) ||
            !isset($file['hash'], $file['content']) ||
            empty($file['hash']) ||
            empty($file['content'])
        ) {
            static::raiseError(get_class($storage) .'::retireveFile() returned an invalid file!');
            return false;
        }

        if (strlen($file['content']) != $item->getFileSize()) {
            static::raiseError(__METHOD__ .'(), file size of retrieved file does not match archive record!');
            return false;
        }

        if ($file['hash'] != $item->getFileHash()) {
            static::raiseError(__METHOD__ .'(), file hash of retrieved file does not match archive record!');
            return false;
        }

        header('Content-Type: application/pdf');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: '. strlen($file['content']));
        print $file['content'];
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
