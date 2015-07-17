<?php

namespace MTLDA\Controllers;

use MTLDA\Models;

class IncomingController
{
    public function __construct()
    {

    } // __construct()

    public function __destruct()
    {

    } // _destruct()

    private function cleanup()
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
        $options = array(
                'debug' => 2,
                'portability' => 'DB_PORTABILITY_ALL'
                );

        global $mysql_db;
        global $mysql_host;
        global $mysql_user;
        global $mysql_pass;

        $dsn = "mysql:dbname=". $mysql_db .";host=". $mysql_host;

        try {
            $db = new PDO($dsn, $mysql_user, $mysql_pass, array(
                        PDO::MYSQL_ATTR_LOCAL_INFILE => true,
                        ));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

        $incoming_directory = "/home/unki/git/mtlda/data/incoming";
        $working_directory = "/home/unki/git/mtlda/data/working";

        $sth = $db->prepare("
                INSERT INTO mtlda_queue (
                    queue_guid,
                    queue_file_name,
                    queue_file_hash,
                    queue_state,
                    queue_time
                    ) VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                        )
                ");

        if (( $incoming = opendir($incoming_directory)) === false) {
            print "Error!: failed to access ". $incoming_directory;
            die();
        }

        while ($file = readdir($incoming)) {

            $in_file = $incoming_directory ."/". $file;
            $work_file = $working_directory ."/". $file;

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
                        $hash,
                        'new',
                        time()
                        ));

        }

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4 autoindent smartindent:
