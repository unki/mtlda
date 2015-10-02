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

namespace MTLDA\Models ;

class MessageBusModel extends DefaultModel
{
    public $table_name = 'message_bus';
    public $column_name = 'msg';
    public $fields = array(
            'queue_idx' => 'integer',
            );
    public $avail_items = array();
    public $items = array();

    public function getMessagesForSession($session_id)
    {
        global $mtlda, $db;

        if (empty($session_id)) {
            $mtlda->raiseError(__METHOD__ .', \$session_id can not be empty!');
            return false;
        }

        $idx_field = $this->column_name ."_idx";

        $sql =
            "SELECT
                *
            FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                msg_session_id
            LIKE
                ?";

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError(__METHOD__ .', failed to prepare query!');
            return false;
        };

        if (!$db->execute($sth, array($session_id))) {
            $db->freeStatement($sth);
            $mtlda->raiseError(__METHOD__ .', failed to execute query!');
            return false;
        }

        if (($result = $sth->fetchAll()) === false) {
            $db->freeStatement($sth);
            $mtlda->raiseError(get_class($sth) .'::fetchAll() returned false!');
            return false;
        }

        $db->freeStatement($sth);
        return $result;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
