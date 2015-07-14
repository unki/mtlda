<?php

namespace MTLDA\Controllers;

use MTLDA\Views;

class ViewsController
{
    private $page_map = array(
        '/^$/' => 'MainView',
        '/^main$/' => 'MainView',
        '/^queue$/' => 'QueueView',
    );
    private $page_skeleton;

    public function __construct()
    {
        $this->page_skeleton = new Views\SkeletonView;
    }

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
        $view = 'MTLDA\\Views\\'.$view;
        $page = new $view;

        if ($content = $page->show()) {
            $this->page_skeleton->assign('page_content', $content);
        }

        print $this->page_skeleton->show();
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
