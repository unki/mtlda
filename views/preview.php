<?php

namespace MTLDA\Views;

class PreviewView extends Templates
{
    public $class_name = 'preview';
    public $default_mode = 'show';

    public function show()
    {
        global $mtlda, $query, $config;

        if (!isset($query) || !isset($query->params) || empty($query->params)) {
            $mtlda->raiseError("\$query->params not set!");
            return false;
        }

        if (!isset($query->params['id']) || empty($query->params['id'])) {
            $mtlda->raiseError("\$query->id not set or empty!");
            return false;
        }

        if (!$mtlda->isValidId($query->params['id'])) {
            $mtlda->raiseError("\$query->id has an invalid syntax!");
            return false;
        }

        $img_url = $config['app']['base_web_path'] .'/preview/'. $query->params['id'];

        $this->assign('img_url', $img_url);

        return $this->fetch("preview.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
