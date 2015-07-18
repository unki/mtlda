<?php

namespace MTLDA\Views;

use MTLDA\Models;

class QueueView extends Templates
{
    public $class_name = 'queue';
    public $item_name = 'QueueItem';
    public $queue;

    public function __construct()
    {
        $this->queue = new Models\QueueModel;

        parent::__construct();
    }

    public function showEdit($id, $hash)
    {
        $item = new Models\QueueItemModel($id, $hash);

        parent::showEdit($item);
    }

    public function queueList($params, $content, &$smarty, &$repeat)
    {
        global $mtlda;

        $index = $smarty->getTemplateVars('smarty.IB.item_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if ($index >= count($this->queue->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->queue->avail_items[$index];
        $item =  $this->queue->items[$item_idx];

        $smarty->assign("item", $item);
        $smarty->assign("item_safe_link", $item->queue_idx ."-". $item->queue_guid);

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
