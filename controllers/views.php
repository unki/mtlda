<?php

namespace MTLDA\Controllers;

class ViewsController
{
    private $page_map = array(
        '/^$/' => 'MainView',
        '/^index$/' => 'MainView',
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

            if (!class_exists('MTLDA\\Views\\'.$this->page_map[$entry])) {
                print "Error - view class ". $this->page_map[$entry] ." does not exist!";
                exit(1);
            }

            return $this->page_map[$entry];

        }
    }

    public function load($view)
    {
        $page = new $view;
        $view->initialize();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
