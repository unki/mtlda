<?php

namespace MTLDA\Controllers;

use MTLDA\Models;

class StorageController
{
    private $data_path = MTLDA_BASE."/data/archive";
    private $working_path = MTLDA_BASE."/data/working";
    private $nesting_depth = 5;

    public function archive(&$queue_item)
    {
        global $mtlda;

        // verify QueueItemModel is ok()
        if (!$queue_item->verify()) {
            $mtlda->raiseError("QueueItemModel::verify() returned false!");
            return false;
        }

        // retrieve QueueItemModel GUID
        if (!($guid = $queue_item->getGuid())) {
            $mtlda->raiseError("QueueItemModel::getGuid() returned false!");
            return false;
        }

        // generate a hash-value based directory name
        if (!($store_dir_name = $this->generateDirectoryName($guid))) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned false!");
            return false;
        }

        if (!isset($store_dir_name) || empty($store_dir_name)) {
            $mtlda->raiseError("StorageController::generateDirectoryName() returned an empty directory string");
            return false;
        }

        // create the target directory structure
        if (!$this->createDirectoryStructure($store_dir_name)) {
            $mtlda->raiseError("StorageController::createDirectoryStructure() returned false!");
            return false;
        }

        $archive_item = new Models\ArchiveItemModel;

        if (!isset($queue_item->fields) || empty($queue_item->fields)) {
            $mtlda->raiseError("\$queue_item->fields not set!");
            return false;
        }

        // copy fields from QueueItemModel to ArchiveItemModel
        foreach (array_keys($queue_item->fields) as $queue_field) {

            if (in_array($queue_field, array("queue_state", "queue_guid"))) {
                continue;
            }

            $archive_field = str_replace("queue_", "archive_", $queue_field);
            $archive_item->$archive_field = $queue_item->$queue_field;
        }

        // copy file from queue to data directory
        if (!$this->copyQueueItemFileToArchive($queue_item->queue_file_name, $store_dir_name)) {
            $mtlda->raiseError("StorageController::copyQueueItemFileToArchive() returned false!");
            return false;
        }

        // safe ArchiveItemModel to database, if that fails revert
        if (!$archive_item->save()) {
            $this->deleteArchiveItemFile($queue_item->queue_file_name, $store_dir_name);
            $mtlda->raiseError("ArchiveItemModel::save() returned false!");
            return false;
        }

        // delete file from queue directory, if that fails revert
        // and delete archived file
        if (!$this->deleteQueueItemFile($queue_item->queue_file_name)) {
            $archive_item->delete();
            $this->deleteArchiveItemFile($queue_item->queue_file_name, $store_dir_name);
            $mtlda->raiseError("ArchiveItemModel::deleteQueueItemFile() returned false!");
            return false;
        }

        // delete QueueItemModel from database, if that fails revert
        if (!$queue_item->delete()) {
            $archive_item->delete();
            $this->deleteArchiveItemFile($queue_item->queue_file_name, $store_dir_name);
            $mtlda->raiseError("ArchiveItemModel::delete() returned false!");
            return false;
        }

        return true;
    }

    public function generateDirectoryName($guid)
    {
        global $mtlda;

        $dir_name = "";

        if (empty($guid)) {
            $mtlda->raiseError("guid is empty!");
            return false;
        }

        for ($i = 0; $i < strlen($guid); $i+=2) {

            $hash_part = substr($guid, $i, 2);

            if (!$hash_part) {
                $mtlda->raiseError("substr() returned false!");
                return false;
            }

            // stop if we reach nesting depth
            if (($i/2) > $this->nesting_depth) {
                break;
            }

            $dir_name.= $hash_part.'/';
        }

        if (!isset($dir_name) || empty($dir_name)) {
            return false;
        }

        return $dir_name;
    }

    private function createDirectoryStructure($store_dir_name)
    {
        global $mtlda;

        if (empty($store_dir_name)) {
            return false;
        }

        $fqpn = $this->data_path .'/'. $store_dir_name;

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

    private function copyQueueItemFileToArchive($file_name, $dest_dir)
    {
        global $mtlda;

        $fqpn_src = $this->working_path .'/'. $file_name;
        $fqpn_dst = $this->data_path .'/'. $dest_dir;

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

        if (!rename($fqpn_src, $fqpn_dst)) {
            $mtlda->raiseError("copyQueueItemFileToArchive(), rename() returned false!");
            return false;
        }

        return true;
    }

    private function deleteQueueItemFile($file_name)
    {
        global $mtlda;

        $fqpn_src = $this->working_path .'/'. $file_name;

        if (!file_exists($fqpn_src)) {
            return true;
        }

        if (!unlink($fqpn_src)) {
            $mtlda->raiseError("deleteQueueItemFile(), unlink() returned false!");
            return false;
        }

        return true;
    }

    private function deleteArchiveItemFile($file_name, $dest_dir)
    {
        global $mtlda;

        $fqpn_dst = $this->data_path .'/'. $dest_dir;

        if (!file_exists($fqpn_dst)) {
            return true;
        }

        if (!unlink($fqpn_dst)) {
            $mtlda->raiseError("deleteArchiveItemFile(), unlink() returned false!");
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
