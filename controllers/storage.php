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
    public function generateDirectoryName($hash)
    {
        global $mtlda;

        $dir_name = "";

        if (empty($hash)) {
            $mtlda->raiseError("hash is empty!");
            return false;
        }

        for ($i = 0; $i < strlen($hash); $i+=2) {

            $hash_part = substr($hash, $i, 2);

            if (!$hash_part) {
                $mtlda->raiseError("substr() returned false!");
                return false;
            }

            // stop if we reach nesting depth
            if (($i/2) > $this::ARCHIVE_NESTING_DEPTH) {
                break;
            }

            $dir_name.= $hash_part.'/';
        }

        if (!isset($dir_name) || empty($dir_name)) {
            return false;
        }

        // remove trailing slash
        $dir_name = rtrim($dir_name, '/');

        return $dir_name;
    }

    public function createDirectoryStructure($store_dir_name)
    {
        global $mtlda;

        if (empty($store_dir_name)) {
            return false;
        }

        $fqpn = $this::ARCHIVE_DIRECTORY .'/'. $store_dir_name;

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

    public function copyQueueItemFileToArchive($file_name, $dest_dir)
    {
        global $mtlda;

        $fqpn_src = $this::WORKING_DIRECTORY .'/'. $file_name;
        $fqpn_dst = $this::ARCHIVE_DIRECTORY .'/'. $dest_dir;

        if (!file_exists($fqpn_src)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), {$fqpn_src} does not exist!");
            return false;
        }

        if (!file_exists($fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), {$fqpn_dst} does not exist!");
            return false;
        }

        if (!is_dir($fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), {$fqpn_dst} is not a directory!");
            return false;
        }

        $fqpn_dst.= '/'. $file_name;

        if (file_exists($fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), destination file {$fqpn_dst} already exists!");
            return false;
        }

        if (!copy($fqpn_src, $fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), copy() returned false!");
            return false;
        }

        return true;
    }

    public function copyArchiveDocumentFile($src, $dst)
    {
        global $mtlda;

        $fqpn_src = $this::ARCHIVE_DIRECTORY .'/'. $src;
        $fqpn_dst = $this::ARCHIVE_DIRECTORY .'/'. $dst;

        if (!file_exists($fqpn_src)) {
            $mtlda->raiseError("copyArchiveDocumentFile(), {$fqpn_src} does not exist!");
            return false;
        }

        if (file_exists($fqpn_dst)) {
            $mtlda->raiseError("copyArchiveDocumentFile(), {$fqpn_dst} already exist!");
            return false;
        }

        if (!copy($fqpn_src, $fqpn_dst)) {
            $mtlda->raiseError("copyArchiveDocumentFile(), copy() returned false!");
            return false;
        }

        return true;
    }

    public function deleteFile($file_name, $dest_dir)
    {
        global $mtlda;

        $fqpn_dst = $this::ARCHIVE_DIRECTORY .'/'. $dest_dir;

        if (!file_exists($fqpn_dst)) {
            return true;
        }

        if (!unlink($fqpn_dst)) {
            $mtlda->raiseError(__TRAIT__ .", unlink() returned false!");
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

        if ($item->column_name == 'document') {

            if (!($dir_name = $this->generateDirectoryName($item->$guid))) {
                $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
                return false;
            }

            if (!isset($dir_name) || empty($dir_name)) {
                $mtlda->raiseError("StorageController::generateDirectoryName() returned nothing!");
                return false;
            }

            $fqpn = $this::ARCHIVE_DIRECTORY .'/'. $dir_name .'/'. $item->$file_name_field;
            $guid = $item->document_guid;
            $file_name = $item->document_file_name;

        } elseif ($item->column_name == 'queue') {

            $fqpn = $this::WORKING_DIRECTORY .'/'. $item->$file_name_field;
            $guid = $item->queue_guid;
            $file_name = $item->queue_file_name;

        } else {
            $mtlda->raiseError("Unsupported model ". $item->column_name);
            return false;
        }

        if (!file_exists($fqpn)) {
            $mtlda->raiseError("StorageController::deleteItemFile(), {$fqpn} does not exist!");
            return false;
        }

        if (!unlink($fqpn)) {
            $mtlda->raiseError("StorageController::deleteItemFile(), unlink() returned false!");
            return false;
        }

        try {
            $audit->log(
                $file_name,
                "delete",
                "storage",
                $guid
            );
        } catch (Exception $e) {
            $mtlda->raiseError("AuditController::log() returned false!");
            return false;
        }

        return true;
    }

    public function retrieveFile(&$document, $from = 'archive')
    {
        global $mtlda;


        if ($from == 'archive') {
            $src = $this::ARCHIVE_DIRECTORY;
            $guid_field = "document_guid";
            $name_field = "document_file_name";
        } else {
            $src = $this::WORKING_DIRECTORY;
            $guid_field = "queue_guid";
            $name_field = "queue_file_name";
        }

        if (!($dir_name = $this->generateDirectoryName($document->$guid_field))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($dir_name) || empty($dir_name)) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned an empty directory string");
            return false;
        }

        $src.= "/{$dir_name}/{$document->$name_field}";

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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
