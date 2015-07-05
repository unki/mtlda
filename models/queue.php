<?php

namespace MTLDA\Models ;

class QueueModel extends DefaultModel
{
    public $table_name = 'queue';
    public $column_name = 'queue';
    public $fields = array(
            'queue_idx' => 'integer',
            );
    public $avail_items = array();
    public $items = array();

    public function __construct($id = null)
    {
        parent::__construct($id);
        /*        parent::__construct($id, array(
                  'table_name' => 'queue',
                  'col_name' => 'queue',
                  'fields' => array(
                  'queue_idx' => 'integer',
                  ),
                  ));*/

        $this->load();

        return true;
    }

    public function load()
    {
        global $db;

        $idx_field = $this->column_name ."_idx";

        $result = $db->query("
                SELECT
                *
                FROM
                TABLEPREFIX". $this->table_name);

        while ($row = $result->fetch()) {
            array_push($this->avail_items, $row->$idx_field);
            $this->items[$row->$idx_field] = $row;
        }

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
