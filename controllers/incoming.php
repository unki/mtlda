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

use MTLDA\Models;

class IncomingController
{
    private $incoming_directory = "/home/unki/git/mtlda/data/incoming";
    private $working_directory = "/home/unki/git/mtlda/data/working";

    public function __construct()
    {

    } // __construct()

    public function __destruct()
    {

    } // _destruct()

    public function cleanup()
    {
        global $db;
        global $sth;

        try {
            if ($sth) {
                $sth->closeCursor();
            }
        } catch (Exception $e) {
            $sth = null;
        }

        $db = null;

    } // cleanup()

    public function handleQueue()
    {
        global $db;

        $sth = $db->prepare("
                INSERT INTO mtlda_queue (
                    queue_guid,
                    queue_file_name,
                    queue_file_size,
                    queue_file_hash,
                    queue_state,
                    queue_time
                    ) VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                        )
                ");

        if (( $incoming = opendir($this->incoming_directory)) === false) {
            print "Error!: failed to access ". $this->incoming_directory;
            die();
        }

        while ($file = readdir($incoming)) {

            $in_file = $this->incoming_directory ."/". $file;
            $work_file = $this->working_directory ."/". $file;

            if (!file_exists($in_file)) {
                continue;
            }

            if (!is_file($in_file)) {
                continue;
            }

            if (!is_readable($in_file)) {
                continue;
            }

            if (($hash = sha1_file($in_file)) === false) {
                continue;
            }

            if (($size = filesize($in_file)) === false) {
                continue;
            }

            if (rename($in_file, $work_file) === false) {
                print "rename() returned false!";
                exit(1);
            }

            if (function_exists("openssl_random_pseudo_bytes")) {

                if (($guid = openssl_random_pseudo_bytes("32")) === false) {
                    print "openssl_random_pseudo_bytes() returned false!";
                    exit(1);
                }

                $guid = bin2hex($guid);
            } else {
                $guid = uniqid(rand(0, 32766), true);
            }

            $sth->execute(array(
                        $guid,
                        $file,
                        $size,
                        $hash,
                        'new',
                        time()
                        ));

        }

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
