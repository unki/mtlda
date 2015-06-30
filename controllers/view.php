<?php

namespace MTLDA\Controllers;

use MTLDA\Views;

class ViewsController
{
    private $page_map = array(
        '/^$/' => 'IndexView',
        '/^index$/' => 'IndexView',
        '/^queue$/' => 'QueueView',
    );

    public function getViewName($view)
    {
        foreach (array_keys($this->page_map) as $entry) {

            if (($result = preg_match($entry, $view)) === false) {
                print "Error - unable to match ${entry} in ${view}";
                exit(1);
            }

            if ($result == 0) {
                continue;
            }

            if (!class_exists($this->page_map[$entry])) {
                print "Error - view class ". $this->page_map[$entry] ." does not exist!";
                exit(1);
            }

            return $this->page_map[$entry];

        }
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
