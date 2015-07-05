<?php

namespace MTLDA\Controllers;

class ExceptionController extends \Exception
{
    // custom string representation of object
    public function __toString()
    {
        return "Backtrace:<br />\n". str_replace("\n", "<br />\n", parent::getTraceAsString());
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
