<?php

namespace MTLDA\Views;

use MTLDA\Models;

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

        if (!($item = $mtlda->parseId($query->params['id']))) {
            $mtlda->raiseError("Unable to parse \$query->id: ". htmlentities($query->params['id'], ENT_QUOTES));
            return false;
        }

        $queueitem = new Models\QueueItemModel($item->id, $item->guid);
        if (!isset($queueitem)) {
            $mtlda->raiseError("Unable to locate QueueItem!");
            return false;
        }

        $img_url = $config['app']['base_web_path'] .'/preview/'. $query->params['id'];
        $img_load = $config['app']['base_web_path'] .'/resources/images/load.gif';

        $this->assign('img_url', $img_url);
        $this->assign('img_load', $img_load);
        $this->assign('img_id', $query->params['id']);
        $this->assign('img_name', $queueitem->queue_file_name);

        return $this->fetch("preview.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
