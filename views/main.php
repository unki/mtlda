<?php

namespace MTLDA\Views;

class MainView extends Templates
{
    public function __construct()
    {
        parent::__construct();

        print $this->fetch('main.tpl');
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
