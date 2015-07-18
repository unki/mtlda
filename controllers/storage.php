<?php

namespace MTLDA\Controllers;

use MTLDA\Models;

class StorageController
{
    private $data_path = "data/archive";

    public function store($file, $hash = null)
    {
        global $mtlda;

        if (!file_exists($file)) {
            $mtlda->raiseError("File ${file} does not exist!");
            return false;
        }

        if (!is_readable($file)) {
            $mtlda->raiseError("File ${file} is not readable!");
            return false;
        }

        if (($file_hash = sha1_file($file)) === false) {
            $mtlda->raiseError("Unable to calculate SHA1 hash of file ${file}!");
            return false;
        }

        if (isset($hash) && $hash != $file_hash) {
            $mtlda->raiseError("Hash value of ${file} does not match!");
            return false;
        }
            
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
