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

class ImportController extends DefaultController
{
    public function __construct()
    {
    } // __construct()

    public function __destruct()
    {

    } // _destruct()

    public function handleQueue()
    {
        global $mtlda, $config, $audit, $mbus;

        if ($config->isCreatePreviewImageOnImport()) {
            try {
                $imagectrl = new \Mtlda\Controllers\ImageController;
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), unable to load ImageController!');
                return false;
            }
        }

        if ($config->isPdfSigningEnabled() &&
            (($sign_pos = $config->getPdfSigningIconPosition()) === false)
        ) {
            static::raiseError(__METHOD__ .'(), PDF-Signing is enabled but no signing-icon-position is defined!');
            return false;
        }

        try {
            $storage = new StorageController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .', failed to load StorageController!');
            return false;
        }

        $files = array();

        if ($this->scanDirectory(self::INCOMING_DIRECTORY, $files) === false) {
            static::raiseError(__CLASS__ .'::scanDirectory() returned false!');
            return false;
        }

        if (!isset($files) || empty($files)) {
            return true;
        }

        $start = 10;
        $end = 90;
        $cnt = 0;
        $total = count($files);
        $steps = ($end-$start)/$total;

        foreach ($files as $file) {
            $cnt+=1;
            $lockfile = "${file['fqpn']}.lock";

            $mbus->sendMessageToClient(
                'import-reply',
                "Importing file {$cnt} of {$total}.",
                floor($start+($cnt*$steps)) .'%'
            );

            // if file has vanished in the meantime.
            if (!file_exists($file['fqpn'])) {
                continue;
            }

            if (file_exists($lockfile)) {
                if (($timestamp = file_get_contents($lockfile)) === false) {
                    static::raiseError(__METHOD__ ."(), failed to read {$lockfile}!");
                    return false;
                }
                if (!isset($timestamp) || empty($timestamp) || !is_numeric($timestamp)) {
                    static::raiseError(__METHOD__ ."(), {$lockfile} does not contain a timestamp!");
                    return false;
                }

                if (time() < ($timestamp+(5*60))) {
                    continue;
                }
            }

            if (file_put_contents($lockfile, time()) === false) {
                static::raiseError(__METHOD__ ."(), failed to write timestamp into {$lockfile}!");
                return false;
            }

            if (($guid = $mtlda->createGuid()) === false) {
                static::raiseError(get_class($mtlda) .'::createGuid() returned false!');
                return false;
            }

            try {
                $queueitem = new \Mtlda\Models\QueueItemModel;
            } catch (\Exception $e) {
                static::raiseError(__METHOD__ .'(), failed to load QueueItemModel!');
                return false;
            }

            try {
                $queueitem->setGuid($guid);
                $queueitem->setFileName($file['filename']);
                $queueitem->setFileSize($file['size']);
                $queueitem->setFileHash($file['hash']);
                $queueitem->setState('new');
                $queueitem->setTime(microtime(true));
                $queueitem->setProcessingFlag(false);
                if ($config->isPdfSigningEnabled()) {
                    $queueitem->setSigningIconPosition($sign_pos);
                }
            } catch (\Exception $e) {
                static::raiseError('Failed to prepare QueueItemModel! ', false, $e);
                return false;
            }

            $in_file = $file['fqpn'];
            $in_dir = dirname($in_file);

            if (!file_exists($in_file)) {
                static::raiseError(__METHOD__ ."(), file {$in_file} does not exist!");
                return false;
            }

            if (($dsc_file = preg_replace('/\.pdf$/i', '.dsc', $in_file)) === false) {
                static::raiseError(__METHOD__ .'(), preg_replace() returned false!');
                return false;
            }

            if (($work_file = $queueitem->getFilePath()) === false) {
                static::raiseError(get_class($queueitem) .'::getFilePath() returned false!');
                return false;
            }

            if (isset($dsc_file) && !empty($dsc_file) && file_exists($dsc_file)) {
                if (($description = file_get_contents($dsc_file)) === false) {
                    static::raiseError(__METHOD__ ."(), file_get_contents({$dsc_file}) returned false!");
                    return false;
                }
                if (!$queueitem->setDescription($description)) {
                    static::raiseError(get_class($queueitem) .'::setDescription() returned false!');
                    return false;
                }
            }

            // create the target directory structure
            if (!$storage->createDirectoryStructure(dirname($work_file))) {
                static::raiseError(get_class($storage) .'::createDirectoryStructure() returned false!');
                return false;
            }

            if (copy($in_file, $work_file) === false) {
                static::raiseError(__METHOD__ ."(), copy({$in_file}, {$work_file}) returned false!");
                return false;
            }

            if (!$queueitem->save()) {
                $queueitem->delete();
                static::raiseError(get_class($queueitem) .'::save() returned false!');
                return false;
            }

            if (!unlink($in_file)) {
                static::raiseError(__METHOD__ ."(), unlink({$in_file}) failed!");
                return false;
            }

            if (isset($dsc_file) && !empty($dsc_file) && file_exists($dsc_file)) {
                if (!unlink($dsc_file)) {
                    static::raiseError(__METHOD__ ."(), unlink({$dsc_file}) failed!");
                    return false;
                }
            }

            if ($in_dir != self::INCOMING_DIRECTORY) {
                if (!$this->unlinkDirectory($in_dir)) {
                    static::raiseError(__CLASS__ .'::unlinkDirectory() returned false!');
                    return false;
                }
            }

            $json_str = json_encode(
                array(
                    'file_name' => $file['filename'],
                    'file_size' => $file['size'],
                    'file_hash' => $file['hash'],
                    'state' => 'new'
                )
            );

            if (!$json_str) {
                $queueitem->delete();
                static::raiseError(__METHOD__ .'(), json_encode() returned false!');
                return false;
            }

            try {
                $audit->log(
                    $json_str,
                    "import",
                    "queue",
                    $guid
                );
            } catch (\Exception $e) {
                $queueitem->delete();
                static::raiseError(get_class($audit) .'::log() returned false!');
                return false;
            }

            if ($config->isCreatePreviewImageOnImport()) {
                if (!$imagectrl->createPreviewImage($queueitem, false)) {
                    static::raiseError(get_class($imagectrl) .'::savePreviewImage() returned false!');
                    return false;
                }
            }

            if (file_exists($lockfile) && !unlink($lockfile)) {
                static::raiseError(__METHOD__ ."(), unlink({$lockfile}) failed!");
                return false;
            }
        }

        return true;
    }

    public function scanDirectory($path, &$files)
    {
        global $mtlda, $audit;

        if (($dir = opendir($path)) === false) {
            static::raiseError(__METHOD__ .'(), failed to access '. self::INCOMING_DIRECTORY);
            return false;
        }

        while ($item = readdir($dir)) {
            // ignore special files and if $file is empty
            if (empty($item) || $item == '.' || $item == '..') {
                continue;
            }

            // ignore file/directories starting with . (aka hidden)
            if (preg_match("/^\..*/", $item)) {
                continue;
            }

            if (is_dir($path .'/'. $item)) {
                if ($this->scanDirectory($path .'/'. $item, $files) === false) {
                    static::raiseError(__CLASS__ .'::scanDirectory() returned false!');
                    return false;
                }
                continue;
            }

            $file = array();
            $file['filename'] = basename($item);
            $file['fqpn'] = $path .'/'. $file['filename'];

            // right now we only care about PDF files.
            if (!preg_match('/\.pdf$/i', $file['filename'])) {
                continue;
            }

            if (!file_exists($file['fqpn'])) {
                static::raiseError(__METHOD__ ."(), file {$file['fqpn']} does not exist!");
                return false;
            }

            if (!is_file($file['fqpn'])) {
                static::raiseError(__METHOD__ ."(), {$file['fqpn']} is not a file!");
                return false;
            }

            if (!is_readable($file['fqpn'])) {
                static::raiseError(__METHOD__ ."(), {$file['fqpn']} is not readable!");
                return false;
            }

            try {
                $audit->log(
                    "{$file['filename']}",
                    "new",
                    "queue"
                );
            } catch (\Exception $e) {
                static::raiseError(get_class($audit) .'::log() raised an exception!');
                return false;
            }

            if (($file['hash'] = sha1_file($file['fqpn'])) === false) {
                static::raiseError(__METHOD__ ."(), SHA1 value of {$file['fqpn']} can not be calculated!");
                return false;
            }

            clearstatcache(true, $file['fqpn']);

            if (($file['size'] = filesize($file['fqpn'])) === false) {
                static::raiseError(__METHOD__ ."(), filesize of {$file['fqpn']} is not available!");
                return false;
            }

            array_push($files, $file);
        }

        return true;
    }

    private function unlinkDirectory($dir)
    {
        global $mtlda;

        if (!file_exists($dir)) {
            static::raiseError(__METHOD__ ."(), directory {$dir} does not exist!");
            return false;
        }

        if (($files = scandir($dir)) === false) {
            static::raiseError(__METHOD__ ."(), scandir() on {$dir} returned false!");
            return false;
        }

        // filter our '.' and '..'
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {
            if (($fqfn = realpath($dir .'/'. $file)) === false) {
                static::raiseError(__METHOD__ ."(), realpath() on {$dir}/{$file} returned false!");
                return false;
            }

            if (!$this->isBelowIncomingDirectory(dirname($fqfn))) {
                static::raiseError(__METHOD__ .'(), will only handle requested within '. self::INCOMING_DIRECTORY .'!');
                return false;
            }

            if (is_dir($fqfn)) {
                if (!$this->unlinkDirectory($fqfn)) {
                    return false;
                }
            }

            // if file has vanished in the meantime.
            if (!file_exists($fqfn)) {
                continue;
            }

            if (!unlink($fqfn)) {
                static::raiseError(__METHOD__ ."(), unlink({$fqfn}) returned false!");
                return false;
            }
        }

        if (!rmdir($dir)) {
            static::raiseError(__METHOD__ ."(), rmdir({$dir}) returned false!");
            return false;
        }

        return true;
    }

    private function isBelowIncomingDirectory($dir)
    {
        global $mtlda;

        if (!isset($dir) || empty($dir) || !is_string($dir)) {
            static::raiseError(__METHOD__ .'(), $dir parameter needs to be set!');
            return false;
        }

        $dir = strtolower(realpath($dir));
        $dir_top = strtolower(realpath(self::INCOMING_DIRECTORY));

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

    public function pendingItems()
    {
        $files = array();
        if ($this->scanDirectory(self::INCOMING_DIRECTORY, $files) === false) {
            static::raiseError(__METHOD__ .'(), scandir() returned false!');
            return false;
        }

        if (!isset($files) || empty($files) || !is_array($files)) {
            return 0;
        }

        array_filter($files, function ($value) {
            if (in_array($value, array('.', '..'))) {
                return false;
            }
            return true;
        });

        return count($files);
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
