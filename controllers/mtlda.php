<?php

require_once BASE_PATH."/controllers/config.php";
require_once BASE_PATH."/controllers/db.php";


class MTLDA_Controller {

    public function __construct()
    {
        global $config_ctrl, $db;

        $config_ctrl = new MTLDA_Config_Controller;

        $db = new MTLDA_DB_Controller;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
