<?php

namespace MTLDA\Controllers;

class DbController
{
    public $db;
    private $db_cfg;
    private $is_connected = false;

    public function __construct()
    {
        global $config;

        $this->is_connected = false;

        if (!isset($config['database']) || !is_array($config['database']) || empty($config['database'])) {
            print "Error - database configuration is missing or incomplete - please check configuration!";
            exit(1);
        }

        $db_param = $config['database'];

        if (!isset(
            $db_param['type'],
            $db_param['host'],
            $db_param['db_name'],
            $db_param['db_user'],
            $db_param['db_pass'])) {
            print "Error - incomplete database configuration - please check configuration!";
            exit(1);
        }

        $this->db_cfg = $db_param;
        $this->connect();

    }

    private function connect()
    {
        $options = array(
            'debug' => 2,
            'portability' => 'DB_PORTABILITY_ALL'
        );

        switch($this->db_cfg['type']) {
            default:
            case 'mysql':
                $dsn = "mysql:dbname=". $this->db_cfg['db_name'] .";host=". $this->db_cfg['host'];
                $user = $this->db_cfg['db_user'];
                $pass = $this->db_cfg['db_pass'];
                break;
            case 'sqlite':
                $dsn = "sqlite:".$this->db_cfg['host'];
                $user = null;
                $pass = null;
                break;
        }

        try {
            $this->db = new PDO($dsn, $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            print "Error - unable to connect to database: ". $e->getMessage();
            exit(1);
        }

        $this->SetConnectionStatus(true);

    }

    private function setConnectionStatus($status)
    {
        $this->is_connected = $status;
    }

    private function getConnectionStatus()
    {
        return $this->is_connected;
    }

    public function query($query = "", $mode = null)
    {
        if (!$this->getConnectionStatus()) {
            $this->connect();
        }

        /* for manipulating queries use exec instead of query. can save
         * some resource because nothing has to be allocated for results.
         */
        if (preg_match('/^(update|insert)i/', $query)) {
            $result = $this->db->exec($query);
            return $result;
        }

        $result = $this->db->query($query);
        return $result;

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
