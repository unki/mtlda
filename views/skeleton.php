<?php

namespace MTLDA\Views;

class SkeletonView extends Templates
{
    public $class_name = 'skeleton';

    /**
     * overwrite parent show() method as we do not have a lot
     * to do here.
     */
    public function show()
    {
        return $this->fetch('skeleton.tpl');
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
