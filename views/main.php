<?php

namespace MTLDA\Views;

class MainView extends Templates
{
    public $class_name = 'main';

    public function __construct()
    {
        parent::__construct();

        print $this->fetch('main.tpl');
    }

    public function mainList()
    {

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
