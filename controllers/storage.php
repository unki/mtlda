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

namespace MTLDA\Controllers;

class StorageController extends DefaultController
{
    public function createDirectoryStructure($fqpn)
    {
        global $mtlda;

        if (empty($fqpn)) {
            return false;
        }

        if (file_exists($fqpn) && is_dir($fqpn)) {
            return true;
        }

        if (file_exists($fqpn) && !is_dir($fqpn)) {
            $mtlda->raiseError("StorageController::createDirectoryStructure(), {$fqpn} exists, but is not a directory");
            return false;
        }

        if (!mkdir($fqpn, 0700, true)) {
            $mtlda->raiseError("mkdir() returned false!");
            return false;
        }

        return true;
    }

    public function copyFile($fqpn_src, $fqpn_dst)
    {
        global $mtlda;

        if (!file_exists($fqpn_src)) {
            $mtlda->raiseError(__METHOD__ .", {$fqpn_src} does not exist!");
            return false;
        }

        if (!is_dir(dirname($fqpn_dst))) {
            $mtlda->raiseError(__METHOD__ .", {$fqpn_dst} is not a directory!");
            return false;
        }

        if (file_exists($fqpn_dst)) {
            $mtlda->raiseError(__METHOD__ .", destination file {$fqpn_dst} already exists!");
            return false;
        }

        if (!copy($fqpn_src, $fqpn_dst)) {
            $mtlda->raiseError(__METHOD__ .", copy() returned false!");
            return false;
        }

        return true;
    }

    public function deleteItemFile(&$item)
    {
        global $mtlda, $audit;

        if (!isset($item) || empty($item)) {
            $mtlda->raiseError("\$item is not set!");
            return false;
        }

        if (!isset($item->column_name) || empty($item->column_name)) {
            $mtlda->raiseError("\$item->column_name is not set!");
            return false;
        }

        $file_name_field = $item->column_name .'_file_name';
        $guid = $item->column_name .'_guid';

        if (!isset($item->$file_name_field) || empty($item->$file_name_field)) {
            $mtlda->raiseError("\$item->{$file_name_field} is not set!");
            return false;
        }

        if (!isset($item->$guid) || empty($item->$guid)) {
            $mtlda->raiseError("\$item->{$guid} is not set!");
            return false;
        }

        if (
            $item->column_name != 'document' &&
            $item->column_name != 'queue'
        ) {
            $mtlda->raiseError("Unsupported model ". $item->column_name);
            return false;
        }

        if (!method_exists($item, 'getFilePath')) {
            $mtlda->raiseError('Class '. get_class($item) .' does not provide getFilePath() method!');
            return false;
        }

        if (!($fqfn = $item->getFilePath())) {
            $mtlda->raiseError(get_class($item) ."::getFilePath() returned false!");
            return false;
        }

        if (!$this->deleteFile($fqfn)) {
            $mtlda->raiseError(__CLASS__ .'::deleteFile() returned false');
            return false;
        }


        try {
            $audit->log(
                $item->$file_name_field,
                "delete",
                "storage",
                $item->$guid
            );
        } catch (Exception $e) {
            $mtlda->raiseError("AuditController::log() returned false!");
            return false;
        }

        if (!$this->cleanDirectoryHierarchy(dirname($fqfn))) {
            $mtlda->raiseError(__CLASS__ .'::cleanDirectoryHierarchy() returned false!');
            return false;
        }

        return true;
    }

    public function retrieveFile(&$document)
    {
        global $mtlda;

        if (!is_object($document)) {
            $mtlda->raiseError(__METHOD__ .', first parameter should be an object!');
            return false;
        }

        if (is_a($document, 'MTLDA\Models\DocumentModel')) {
            $guid_field = "document_guid";
            $name_field = "document_file_name";
        } elseif (is_a($document, 'MTLDA\Models\QueueItemModel')) {
            $guid_field = "queue_guid";
            $name_field = "queue_file_name";
        } else {
            $mtlda->raiseError("Unsupported model: ". get_class($document) .'!');
            return false;
        }

        if (
            !method_exists($document, 'getFilePath') ||
            !is_callable(array($document, 'getFilePath'))
        ) {
            $mtlda->raiseError('Class '. get_class($document) .' does not provide getFilePath() method!');
            return false;
        }

        if (!($src = $document->getFilePath())) {
            $mtlda->raiseError(get_class($document) ."::getFilePath() returned false!");
            return false;
        }

        if (!file_exists($src)) {
            $mtlda->raiseError("Source does not exist!");
            return false;
        }

        if (!is_readable($src)) {
            $mtlda->raiseError("Source is not readable!");
            return false;
        }

        if (!($content = file_get_contents($src))) {
            $mtlda->raiseError("file_get_contents() returned false!");
            return false;
        }

        if (!is_string($content) || strlen($content) <= 0) {
            $mtlda->raiseError("file_get_contents() returned an invalid file!");
            return false;
        }

        if (!($hash = sha1($content))) {
            $mtlda->raiseError("sha1() returned false!");
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
            $mtlda->raiseError(__METHOD__ .", {$fqfn} does not exist!");
            return false;
        }

        if (!is_file($fqfn)) {
            $mtlda->raiseError(__METHOD__ .", {$fqfn} is not a regular file!");
            return false;
        }

        if (!$this->isBelowDataDirectory(dirname($fqfn))) {
            $mtlda->raiseError(__METHOD__ .", will only handle requested within ". $this::DATA_DIRECTORY ."!");
            return false;
        }

        if (!unlink($fqfn)) {
            $mtlda->raiseError(__METHOD__ .", unlink({$fqfn}) returned false!");
            return false;
        }

        return true;
    }

    public function flushArchive()
    {
        global $mtlda;

        if (!file_exists($this::ARCHIVE_DIRECTORY)) {
            $mtlda->raiseError($this::ARCHIVE_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_dir($this::ARCHIVE_DIRECTORY)) {
            $mtlda->raiseError($this::ARCHIVE_DIRECTORY ." is not a directory!");
            return false;
        }

        if (!$this->unlinkDirectory($this::ARCHIVE_DIRECTORY)) {
            $mtlda->raiseError("StorageController::unlinkDirectory() returned false!");
            return false;
        }

        return true;
    }

    public function flushQueue()
    {
        global $mtlda;

        if (!file_exists($this::WORKING_DIRECTORY)) {
            $mtlda->raiseError($this::WORKING_DIRECTORY ." does not exist!");
            return false;
        }

        if (!is_dir($this::WORKING_DIRECTORY)) {
            $mtlda->raiseError($this::WORKING_DIRECTORY ." is not a directory!");
            return false;
        }

        if (!$this->unlinkDirectory($this::WORKING_DIRECTORY)) {
            $mtlda->raiseError("StorageController::unlinkDirectory() returned false!");
            return false;
        }

        return true;
    }

    private function unlinkDirectory($dir)
    {
        global $mtlda;

        if (($files = scandir($dir)) === false) {
            $mtlda->raiseError("scandir on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {

            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                $mtlda->raiseError("realpath() on ". $dir .'/'. $file ." returned false!");
                return false;
            }

            if (!$this->isBelowDataDirectory(dirname($fqfn))) {
                $mtlda->raiseError("will only handle requested within ". $this::DATA_DIRECTORY ."!");
                return false;
            }

            if (is_dir($fqfn)) {
                if (!$this->unlinkDirectory($fqfn)) {
                    return false;
                }
            } else {
                if (!unlink($fqfn)) {
                    $mtlda->raiseError("unlink() on {$fqfn} returned false!");
                    return false;
                }
            }
        }

        if (!rmdir($dir)) {
            $mtlda->raiseError("rmdir() on {$dir} returned false!");
            return false;
        }

        return true;
    }

    private function isBelowDataDirectory($dir)
    {
        global $mtlda;

        if (empty($dir)) {
            $mtlda->raiseError("\$dir can not be empty!");
            return false;
        }

        $dir = strtolower(realpath($dir));
        $dir_top = strtolower(realpath($this::DATA_DIRECTORY));

        $dir_top_reg = preg_quote($dir_top, '/');

        // check if $dir is within $dir_top
        if (!preg_match('/^'. preg_quote($dir_top, '/') .'/', $dir)) {
            return false;
        }

        if ($dir == $dir_top) {
            return true;
        }

        $cnt_dir = count(explode('/', $dir));
        $cnt_dir_top = count(explode('/', $dir_top));

        if ($cnt_dir > $cnt_dir_top) {
            return true;
        }

        return false;
    }

    public function checkIfDirectoryEmpty($path)
    {
        global $mtlda;

        if (empty($path) && !is_string($path)) {
            $mtlda->raiseError(__METHOD__ .', first parameter needs to be a path!');
            return false;
        }

        if (!file_exists($path)) {
            $mtlda->raiseError("{$path} does not exist!");
            return false;
        }

        if (!is_dir($path)) {
            $mtlda->raiseError("{$path} is not a directory!");
            return false;
        }

        if ($path != realpath($path)) {
            $mtlda->raiseError("Are you trying to fooling me?");
            return false;
        }

        if (count(glob($path .'/*', GLOB_NOSORT)) === 0) {
            return true;
        }

        return false;
    }

    public function cleanDirectoryHierarchy($path)
    {
        global $mtlda;

        // nothing strange in the path?
        if ($path != realpath($path)) {
            $mtlda->raiseError(__METHOD__ .", are you trying to fooling me?");
            return false;
        }

        // if directory isn't empty, we are done
        if (!$this->checkIfDirectoryEmpty($path)) {
            return true;
        }

        if (!rmdir($path)) {
            $mtlda->raiseError(__METHOD__ .", rmdir({$path}) returned false!");
            return false;
        }

        $next_path = dirname($path);

        if (!$this->isBelowDataDirectory($next_path)) {
            return true;
        }

        if (!$this->cleanDirectoryHierarchy($next_path)) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
