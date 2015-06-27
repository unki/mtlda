<?php

class MTLDA_View_Controller {

    var $page_map = Array(
        '/^$/' => 'View_Controller_Index',
        '/^index$/' => 'View_Controller_Index',
        '/^queue$/' => 'View_Controller_Queue',
    );

    public function get_controller_name($view)
    {
        foreach(array_keys($this->page_map) as $entry) {

            if(($result = preg_match($entry, $view)) === FALSE) {
                print "Error - unable to match ${entry} in ${view}";
                exit(1);
            }

            if($result == 0)
                continue;

            return $this->page_map[$entry];

        }

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
