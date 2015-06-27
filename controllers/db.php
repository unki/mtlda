<?php

class MTLDA_DB_Controller {

    var $db;
    var $db_cfg;
    var $is_connected = FALSE;

    public function __construct() {

        global $config;

        if(!isset($config['database']) || !is_array($config['database']) || empty($config['database'])) {
            print "Error - database configuration is missing or incomplete - please check configuration!";
            exit(1);
        }

        $db_param = $config['database'];

        if(!isset($db_param['type'], $db_param['host'], $db_param['db_name'], $db_param['db_user'], $db_param['db_pass'])) {
            print "Error - incomplete database configuration - please check configuration!";
            exit(1);
        }

        $this->db_cfg = $db_param;
        $this->db_connect();

    }

    private function db_connect()
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
                $user = NULL;
                $pass = NULL;
                break;
        }

        try {
            $this->db = new PDO($dsn, $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        }
        catch (PDOException $e) {
            print "Error - unable to connect to database: ". $e->getMessage();
            exit(1);
        }

        $this->db_set_connection_status(true);

    }

    private function db_set_connection_status($status)
    {
        $this->is_connected = $status;
    }

    private function db_get_connection_status()
    {
        return $this->is_connected;
    }

    public function db_query($query = "", $mode = null)
    {
        if(!$this->db_get_connection_status())
            $this->db_connect();

      /* for manipulating queries use exec instead of query. can save
       * some resource because nothing has to be allocated for results.
       */
      if(preg_match('/^(update|insert)i/', $query)) {
         $result = $this->db->exec($query);
      }
      else {
         $result = $this->db->query($query);
      }

      return $result;

   } // db_query()

}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
