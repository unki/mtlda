<?php

namespace MTLDA\Models ;

class ArchiveItemModel extends DefaultModel
{
    public $table_name = 'archive';
    public $column_name = 'archive';
    public $fields = array(
            'archive_idx' => 'integer',
            'archive_guid' => 'string',
            'archive_file_name' => 'string',
            'archive_file_hash' => 'string',
            'archive_file_size' => 'integer',
            'archive_time' => 'integer',
            );
    public $avail_items = array();
    public $items = array();
    private $working_directory = "../data/working";

    public function __construct($id = null, $guid = null)
    {
        global $mtlda, $db;

        // are we creating a new archive-item?
        if (!isset($id) || !isset($guid) || empty($id) || empty($guid)) {
            parent::__construct(null);
            return true;
        }

        // get $id from db
        $sth = $db->prepare(
            "SELECT
                archive_idx
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                archive_idx LIKE ?
            AND
                archive_guid LIKE ?"
        );

        if (!$db->execute($sth, array($id, $guid))) {
            $mtlda->raiseError("Failed to execute query");
        }

        if (!($row = $sth->fetch())) {
            $mtlda->raiseError("Unable to find archive item with guid value {$guid}");
            return false;
        }

        if (!isset($row->archive_idx) || empty($row->archive_idx)) {
            $mtlda->raiseError("Unable to find archive item with guid value {$guid}");
            return false;
        }

        parent::__construct($row->archive_idx);

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

    public function verify()
    {
        global $mtlda;

        if (!isset($this->working_directory)) {
            $mtlda->raiseError("working_directory is not set!");
            return false;
        }

        if (!isset($this->archive_file_name)) {
            $mtlda->raiseError("archive_file_name is not set!");
            return false;
        }

        if (!isset($this->archive_file_hash)) {
            $mtlda->raiseError("archive_file_hash is not set!");
            return false;
        }

        $fqpn = $this->working_directory .'/'. $this->archive_file_name;

        if (!file_exists($fqpn)) {
            $mtlda->raiseError("File {$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $mtlda->raiseError("File {$fqpn} is not readable!");
            return false;
        }

        if (($file_hash = sha1_file($fqpn)) === false) {
            $mtlda->raiseError("Unable to calculate SHA1 hash of file {$fqpn}!");
            return false;
        }

        if (isset($hash) && $hash != $file_hash) {
            $mtlda->raiseError("Hash value of ${file} does not match!");
            return false;
        }

        return true;
    }

    public function getGuid()
    {
        if (!isset($this->archive_guid)) {
            return false;
        }

        return $this->archive_guid;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
