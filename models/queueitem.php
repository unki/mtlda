<?php

namespace MTLDA\Models ;

class QueueItemModel extends DefaultModel
{
    public $table_name = 'queue';
    public $column_name = 'queue';
    public $fields = array(
            'queue_idx' => 'integer',
            'queue_file_name' => 'string',
            'queue_file_hash' => 'string',
            'queue_file_size' => 'integer',
            'queue_state' => 'string',
            'queue_time' => 'integer',
            );
    public $avail_items = array();
    public $items = array();

    public function __construct($id, $hash)
    {
        global $mtlda, $db;

        // get $id from hash
        $sth = $db->prepare(
            "SELECT
                queue_idx
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                queue_idx LIKE ?
            AND
                queue_file_hash LIKE ?"
        );
            
        if (!$db->execute($sth, array($id, $hash))) {
            $mtlda->raiseError("Failed to execute query");
        }

        if (!($row = $sth->fetch())) {
            $mtlda->raiseError("Unable to find queue item with hash value {$hash}");
            return false;
        }

        if (!isset($row->queue_idx) || empty($row->queue_idx)) {
            $mtlda->raiseError("Unable to find queue item with hash value {$hash}");
            return false;
        }

        parent::__construct($row->queue_idx);


        $db->freeStatement($sth);

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
