<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015>  <Andreas Unterkircher>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 */

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

    public function load($view, $skeleton = true)
    {
        $view = 'MTLDA\\Views\\'.$view;
        $page = new $view;

        if ($skeleton) {
            if ($content = $page->show()) {
                $this->page_skeleton->assign('page_content', $content);
            } else {
                $this->page_skeleton->assign('page_content', 'No content found to be displayed');
            }
            return $this->page_skeleton->show();
        } else {
            return $page->show();
        }
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
