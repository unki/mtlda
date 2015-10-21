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

class StorageController extends DefaultController
{
    public function createDirectoryStructure($fqpn)
    {
        if (empty($fqpn)) {
            return false;
        }

        if (file_exists($fqpn) && is_dir($fqpn)) {
            return true;
        }

        if (file_exists($fqpn) && !is_dir($fqpn)) {
            $this->raiseError("StorageController::createDirectoryStructure(), {$fqpn} exists, but is not a directory");
            return false;
        }

        if (!mkdir($fqpn, 0700, true)) {
            $this->raiseError("mkdir() returned false!");
            return false;
        }

        return true;
    }

    public function copyFile($fqpn_src, $fqpn_dst)
    {
        if (!file_exists($fqpn_src)) {
            $this->raiseError(__METHOD__ .", {$fqpn_src} does not exist!");
            return false;
        }

        if (!is_dir(dirname($fqpn_dst))) {
            $this->raiseError(__METHOD__ .", {$fqpn_dst} is not a directory!");
            return false;
        }

        if (file_exists($fqpn_dst)) {
            $this->raiseError(__METHOD__ .", destination file {$fqpn_dst} already exists!");
            return false;
        }

        if (!copy($fqpn_src, $fqpn_dst)) {
            $this->raiseError(__METHOD__ .", copy() returned false!");
            return false;
        }

        return true;
    }

    public function deleteItemFile(&$item)
    {
        global $audit;

        if (!isset($item) || empty($item)) {
            $this->raiseError("\$item is not set!");
            return false;
        }

        if (!isset($item->column_name) || empty($item->column_name)) {
            $this->raiseError("\$item->column_name is not set!");
            return false;
        }

        $file_name_field = $item->column_name .'_file_name';
        $guid = $item->column_name .'_guid';

        if (!isset($item->$file_name_field) || empty($item->$file_name_field)) {
            $this->raiseError("\$item->{$file_name_field} is not set!");
            return false;
        }

        if (!isset($item->$guid) || empty($item->$guid)) {
            $this->raiseError("\$item->{$guid} is not set!");
            return false;
        }

        if ($item->column_name != 'document' &&
            $item->column_name != 'queue'
        ) {
            $this->raiseError("Unsupported model ". $item->column_name);
            return false;
        }

        if (!method_exists($item, 'getFilePath')) {
            $this->raiseError('Class '. get_class($item) .' does not provide getFilePath() method!');
            return false;
        }

        if (!($fqfn = $item->getFilePath())) {
            $this->raiseError(get_class($item) ."::getFilePath() returned false!");
            return false;
        }

        if (!$this->deleteFile($fqfn)) {
            $this->raiseError(__CLASS__ .'::deleteFile() returned false');
            return false;
        }


        try {
            $audit->log(
                $item->$file_name_field,
                "delete",
                "storage",
                $item->$guid
            );
        } catch (\Exception $e) {
            $this->raiseError("AuditController::log() returned false!");
            return false;
        }

        if (!$this->cleanDirectoryHierarchy(dirname($fqfn))) {
            $this->raiseError(__CLASS__ .'::cleanDirectoryHierarchy() returned false!');
            return false;
        }

        return true;
    }

    public function retrieveFile(&$document)
    {
        if (!is_object($document)) {
            $this->raiseError(__METHOD__ .', first parameter should be an object!');
            return false;
        }

        if (is_a($document, 'Mtlda\Models\DocumentModel')) {
            $guid_field = "document_guid";
            $name_field = "document_file_name";
        } elseif (is_a($document, 'Mtlda\Models\QueueItemModel')) {
            $guid_field = "queue_guid";
            $name_field = "queue_file_name";
        } else {
            $this->raiseError("Unsupported model: ". get_class($document) .'!');
            return false;
        }

        if (!method_exists($document, 'getFilePath') ||
            !is_callable(array($document, 'getFilePath'))
        ) {
            $this->raiseError('Class '. get_class($document) .' does not provide getFilePath() method!');
            return false;
        }

        if (!($src = $document->getFilePath())) {
            $this->raiseError(get_class($document) ."::getFilePath() returned false!");
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

        if (!($content = file_get_contents($src))) {
            $this->raiseError("file_get_contents() returned false!");
            return false;
        }

        if (!is_string($content) || strlen($content) <= 0) {
            $this->raiseError("file_get_contents() returned an invalid file!");
            return false;
        }

        if (!($hash = sha1($content))) {
            $this->raiseError("sha1() returned false!");
            return false;
        }

        return array(
            'hash' => $hash,
            'content' => $content
        );
    }

    public function deleteFile($fqfn)
    {
        global $mtlda;

        if (!file_exists($fqfn)) {
            $this->raiseError(__METHOD__ .", {$fqfn} does not exist!");
            return false;
        }

        if (!is_file($fqfn)) {
            $this->raiseError(__METHOD__ .", {$fqfn} is not a regular file!");
            return false;
        }

        if (!$mtlda->isBelowDirectory(dirname($fqfn), self::DATA_DIRECTORY)) {
            $this->raiseError(__METHOD__ ."(), will only handle requested within ". $this::DATA_DIRECTORY ."!");
            return false;
        }

        if (!unlink($fqfn)) {
            $this->raiseError(__METHOD__ ."(), unlink({$fqfn}) returned false!");
            return false;
        }

        return true;
    }

    public function flushArchive()
    {
        if (!file_exists($this::ARCHIVE_DIRECTORY)) {
            $this->raiseError($this::ARCHIVE_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_dir($this::ARCHIVE_DIRECTORY)) {
            $this->raiseError($this::ARCHIVE_DIRECTORY ." is not a directory!");
            return false;
        }

        if (!$this->unlinkDirectory($this::ARCHIVE_DIRECTORY)) {
            $this->raiseError("StorageController::unlinkDirectory() returned false!");
            return false;
        }

        return true;
    }

    public function flushQueue()
    {
        if (!file_exists($this::WORKING_DIRECTORY)) {
            $this->raiseError($this::WORKING_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_dir($this::WORKING_DIRECTORY)) {
            $this->raiseError($this::WORKING_DIRECTORY ." is not a directory!");
            return false;
        }

        if (!$this->unlinkDirectory($this::WORKING_DIRECTORY)) {
            $this->raiseError("StorageController::unlinkDirectory() returned false!");
            return false;
        }

        return true;
    }

    private function unlinkDirectory($dir)
    {
        global $mtlda;

        if (($files = scandir($dir)) === false) {
            $this->raiseError("scandir on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {
            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                $this->raiseError("realpath() on ". $dir .'/'. $file ." returned false!");
                return false;
            }

            if (!$mtlda->isBelowDirectory(dirname($fqfn), self::DATA_DIRECTORY)) {
                $this->raiseError("will only handle requested within ". $this::DATA_DIRECTORY ."!");
                return false;
            }

            if (is_dir($fqfn)) {
                if (!$this->unlinkDirectory($fqfn)) {
                    return false;
                }
            } else {
                if (!unlink($fqfn)) {
                    $this->raiseError("unlink() on {$fqfn} returned false!");
                    return false;
                }
            }
        }

        if (!rmdir($dir)) {
            $this->raiseError("rmdir() on {$dir} returned false!");
            return false;
        }

        return true;
    }

    public function checkIfDirectoryEmpty($path)
    {
        if (empty($path) && !is_string($path)) {
            $this->raiseError(__METHOD__ .', first parameter needs to be a path!');
            return false;
        }

        if (!file_exists($path)) {
            $this->raiseError("{$path} does not exist!");
            return false;
        }

        if (!is_dir($path)) {
            $this->raiseError("{$path} is not a directory!");
            return false;
        }

        if ($path != realpath($path)) {
            $this->raiseError("Are you trying to fooling me?");
            return false;
        }

        if (count(glob($path .'/*', GLOB_NOSORT)) === 0) {
            return true;
        }

        return false;
    }

    public function cleanDirectoryHierarchy($path, $upperpath = null)
    {
        global $mtlda;

        if (empty($upperpath) && strstr($path, self::ARCHIVE_DIRECTORY)) {
            $upperpath = self::ARCHIVE_DIRECTORY;
        } elseif (empty($upperpath) && strstr($path, self::INCOMING_DIRECTORY)) {
            $upperpath = self::INCOMING_DIRECTORY;
        } elseif (empty($upperpath) && strstr($path, self::WORKING_DIRECTORY)) {
            $upperpath = self::WORKING_DIRECTORY;
        } elseif (empty($upperpath)) {
            $upperpath = self::DATA_DIRECTORY;
        }

        // nothing strange in the path?
        if ($path != realpath($path)) {
            $this->raiseError(__METHOD__ .", are you trying to fooling me?");
            return false;
        }

        // avoid traversing too much up the hierarchy
        if (!$mtlda->isBelowDirectory($path, $upperpath)) {
            return true;
        }

        // if directory isn't empty, we are done
        if (!$this->checkIfDirectoryEmpty($path)) {
            return true;
        }

        if (!rmdir($path)) {
            $this->raiseError(__METHOD__ .", rmdir({$path}) returned false!");
            return false;
        }

        $next_path = dirname($path);

        if (!$this->cleanDirectoryHierarchy($next_path, $upperpath)) {
            return false;
        }

        return true;
    }

    public function createTempDir($prefix = null)
    {
        $tmpdir_created = false;

        if (!isset($prefix) || empty($prefix)) {
            $prefix = 'mtlda_';
        }

        if (!file_exists(self::CACHE_DIRECTORY)) {
            $this->raiseError(self::CACHE_DIRECTORY .' does not exist!');
            return false;
        }

        if (!is_writeable(self::CACHE_DIRECTORY)) {
            $this->raiseError(self::CACHE_DIRECTORY .' is not writeable!');
            return false;
        }

        do {
            $dir_name = $prefix . uniqid();
            $fqpn = self::CACHE_DIRECTORY .'/'. $dir_name;

            if (file_exists($fqpn)) {
                continue;
            }

            if (!mkdir($fqpn, 0700)) {
                $this->raiseError('Failed to create directory '. $fqpn);
                return false;
            }

            $tmpdir_create = true;
            return $fqpn;

        } while (!$tmpdir_created);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
