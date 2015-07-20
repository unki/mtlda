<?php

namespace MTLDA\Controllers;

use MTLDA\Models;

class StorageController
{
    private $data_path = MTLDA_BASE."/data/archive";

    public function archive(&$queueitem)
    {
        global $mtlda;

        if (!$queueitem->verify()) {
            $mtlda->raiseError("QueueItemModel::verify() returned false!");
            return false;
        }

        if (!($guid = $queueitem->getGuid())) {
            $mtlda->raiseError("QueueItemModel::getGuid() returned false!");
            return false;
        }

        if (!$this->createDirectoryName($guid)) {
            $mtlda->raiseError("StorageController::createDirectoryName() returned false!");
            return false;
        }

        return true;
    }

    public function createDirectoryName($guid)
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

            $dir_name.= $hash_part.'/';
        }

        if (!isset($dir_name) || empty($dir_name)) {
            return false;
        }

        return $dir_name;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
