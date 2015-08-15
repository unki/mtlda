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

use \PDO;

class DefaultModel
{
    public $table_name;
    public $column_name;
    public $child_names;
    public $ignore_child_on_clone;
    public $fields;
    public $id;

    public function __construct($id = null)
    {
        global $mtlda;

        if (!isset($this->table_name)) {
            $mtlda->raiseError(__TRAIT__ .', missing key table_name', true);
            return false;
        }

        if (!isset($this->column_name)) {
            $mtlda->raiseError(__TRAIT__ .', missing key column_name', true);
            return false;
        }

        if (!isset($this->fields)) {
            $mtlda->raiseError(__TRAIT__ .', missing key fields', true);
            return false;
        }

        if (!isset($id) || empty($id)) {
            $this->initFields();
            return true;
        }

        $this->id = $id;

        if (!$this->load()) {
            $mtlda->raiseError(__CLASS__ ."::load() returned false!", true);
            return false;
        }

        return true;

    } // __construct()

    /**
     * load
     *
     */
    private function load()
    {
        global $mtlda, $db;

        if (!isset($this->fields) || empty($this->fields)) {
            $mtlda->raiseError(__TRAIT__ .", fields array not set for class ". get_class($this));
        }

        if (method_exists($this, 'preLoad')) {

            if (!$this->preLoad()) {
                $mtlda->raiseError(__CLASS__ ."::preLoad() method returned false!");
                return false;
            }
        }

        $sql = "SELECT ";

        $time_field = $this->column_name .'_time';

        foreach (array_keys($this->fields) as $key) {

            if ($key == $time_field) {
                $sql.= "UNIX_TIMESTAMP({$key}) as {$key}, ";
            } else {
                $sql.= "${key}, ";
            }
        }
        $sql = substr($sql, 0, strlen($sql)-2) .' ';

        $sql.= "FROM
            TABLEPREFIX{$this->table_name}
            WHERE
            {$this->column_name}_idx LIKE ?";

        $sth = $db->prepare($sql, array('integer'));

        if (!$sth) {
            $mtlda->raiseError(__TRAIT__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($this->id))) {
            $mtlda->raiseError(__TRAIT__ .", unable to execute query");
            return false;
        }

        if ($sth->rowCount() <= 0) {
            $db->freeStatement($sth);
            $mtlda->raiseError(__TRAIT__ .", No object with id {$this->id}");
        }

        if (!$row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $db->freeStatement($sth);
            $mtlda->raiseError(__TRAIT__ .", unable to fetch SQL result for object id ". $this->id);
        }

        $db->freeStatement($sth);

        foreach ($row as $key => $value) {
            $this->$key = $value;
        }

        if (method_exists($this, 'postLoad')) {
            if (!$this->postLoad()) {
                $mtlda->raiseError(__CLASS__ ."::postLoad() method returned false!");
                return false;
            }
        }

        return true;

    } // load();

    /**
     * update object variables via array
     *
     * @param mixed $data
     * @return bool
     */
    public function update($data)
    {
        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;

    } // update()

    /**
     * delete
     */
    public function delete()
    {
        global $mtlda, $db;

        if (!isset($this->id)) {
            return false;
        }
        if (!is_numeric($this->id)) {
            return false;
        }
        if (!isset($this->table_name)) {
            return false;
        }
        if (!isset($this->column_name)) {
            return false;
        }

        if (method_exists($this, 'preDelete')) {
            if (!$this->preDelete()) {
                $mtlda->raiseError(__CLASS__ ."::preDelete() method returned false!");
                return false;
            }
        }

        /* generic delete */
        $sth = $db->prepare(
            "DELETE FROM
                TABLEPREFIX{$this->table_name}
            WHERE
                {$this->column_name}_idx LIKE ?"
        );

        if (!$sth) {
            $mtlda->raiseError(__TRAIT__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($this->id))) {
            $mtlda->raiseError(__TRAIT__ .", unable to execute query");
            return false;
        }

        $db->freeStatement($sth);

        if (method_exists($this, 'postDelete')) {
            if (!$this->postDelete()) {
                $mtlda->raiseError(__CLASS__ .", postDelete() method returned false!");
                return false;
            }
        }

        return true;

    } // delete()

    /**
     * clone
     */
    public function createClone(&$srcobj)
    {
        global $mtlda, $db;

        if (!isset($srcobj->id)) {
            return false;
        }
        if (!is_numeric($srcobj->id)) {
            return false;
        }
        if (!isset($srcobj->fields)) {
            return false;
        }

        if (method_exists($this, 'preClone')) {
            if (!$this->preClone()) {
                $mtlda->raiseError(__CLASS__ ."::preClone() method returned false!");
                return false;
            }
        }

        foreach (array_keys($srcobj->fields) as $field) {

            // check for a matching key in clone's fields array
            if (!in_array($field, array_keys($this->fields))) {
                continue;
            }

            $this->$field = $srcobj->$field;
        }

        $idx_field = $this->column_name.'_idx';
        $guid_field = $this->column_name.'_guid';
        $pguid = $this->column_name.'_derivation_guid';

        $this->id = null;
        if (isset($this->$idx_field)) {
            $this->$idx_field = null;
        }
        if (isset($this->$guid_field)) {
            $this->$guid_field = $mtlda->createGuid();
        }

        // record the parent objects GUID
        if (isset($srcobj->$guid_field) && !empty($srcobj->$guid_field)) {
            $this->$pguid = $srcobj->$guid_field;
        }

        $this->save();

        // if saving was successful, our new object should have an ID now
        if (!isset($this->id) || empty($this->id)) {
            $mtlda->raiseError(__TRAIT__ .", error on saving clone. no ID was returned from database!");
            return false;
        }

        $this->$idx_field = $this->id;

        // now check for assigned childrens and duplicate those links too
        if (isset($this->child_names) && !isset($this->ignore_child_on_clone)) {

            // loop through all (known) childrens
            foreach (array_keys($this->child_names) as $child) {

                $prefix = $this->child_names[$child];

                // initate an empty child object
                if (!($child_obj = $mtlda->load_class($child))) {
                    $mtlda->raiseError(__TRAIT__ .", unable to locate class for {$child_obj}");
                    return false;
                }

                $sth = $db->prepare(
                    "SELECT
                        *
                    FROM
                        TABLEPREFIXassign_{$child_obj->table_name}_to_{$this->table_name}
                    WHERE
                        {$prefix}_{$this->column_name}_idx LIKE ?"
                );

                if (!$sth) {
                    $mtlda->raiseError(__TRAIT__ .", unable to prepare query");
                    return false;
                }

                if (!$db->execute($sth, array($srcobj->id))) {
                    $mtlda->raiseError(__TRAIT__ .", unable to execute query");
                    return false;
                }

                while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {

                    $query = "INSERT INTO TABLEPREFIXassign_
                        {$child_obj->table_name}_to_{$this->table_name} (";

                    $values = "";

                    foreach (array_keys($row) as $key) {

                        $query.= $key .",";
                        $values.= "?,";
                    }

                    $query = substr($query, 0, strlen($query)-1);
                    $values = substr($values, 0, strlen($values)-1);

                    $query = $query ."
                        ) VALUES (
                            $values
                            )
                        ";

                    $row[$this->child_names[$child] .'_idx'] = 'NULL';
                    $row[$this->child_names[$child] .'_'.$this->column_name.'_idx'] = $this->id;
                    if (isset($row[$this->child_names[$child] .'_guid'])) {
                        $row[$this->child_names[$child] .'_guid'] = $mtlda->createGuid();
                    }

                    //print_r($query);
                    //print_r($row);
                    if (!isset($child_sth)) {
                        $child_sth = $db->prepare($query);
                    }

                    $db->execute($child_sth, array_values($row));
                }

                if (isset($child_sth)) {
                    $db->freeStatement($child_sth);
                }
                $db->freeStatement($sth);

            }
        }

        if (method_exists($this, 'postClone')) {
            if (!$this->postClone()) {
                $mtlda->raiseError(__CLASS__ ."::postClone() method returned false!");
                return false;
            }
        }

        return true;

    } // createClone()

    /**
     * init fields
     */
    public function initFields($override = null)
    {
        global $mtlda, $db;

        if (!isset($this->fields) || !is_array($this->fields)) {
            return;
        }

        foreach (array_keys($this->fields) as $field) {

            // check for a matching key in clone's fields array
            if (
                isset($override) &&
                !empty($override) &&
                is_array($override) &&
                in_array($field, array_keys($override))
            ) {
                $this->$field = $override[$field];
                continue;
            }

            $this->$field = null;
        }

    } // initFields()

    /* overloading PHP's __set() function */
    public function __set($name, $value)
    {
        global $mtlda;

        if (!isset($this->fields) || empty($this->fields)) {
            $mtlda->raiseError(__TRAIT__ .", fields array not set for class ". get_class($this));
        }

        if (!array_key_exists($name, $this->fields) && $name != 'id') {
            $mtlda->raiseError(__TRAIT__ .", unknown key in ". get_class($this) ."::__set(): {$name}");
        }

        $this->$name = $value;

    } // __set()

    public function save()
    {
        global $mtlda, $db;

        if (!isset($this->fields) || empty($this->fields)) {
            $mtlda->raiseError(__TRAIT__ .", fields array not set for class ". get_class($this));
        }

        if (method_exists($this, 'preSave')) {
            if (!$this->preSave()) {
                $mtlda->raiseError(__CLASS__ ."preSave() method returned false!");
                return false;
            }
        }

        $guid_field = $this->column_name .'_guid';
        $idx_field = $this->column_name .'_idx';
        $time_field = $this->column_name .'_time';

        if (!isset($this->$guid_field) || empty($this->$guid_field)) {
            $this->$guid_field = $mtlda->createGuid();
        }

        /* new object */
        if (!isset($this->id) || empty($this->id)) {
            $sql = 'INSERT INTO ';
        /* existing object */
        } else {
            $sql = 'UPDATE ';
        }

        $sql.= "TABLEPREFIX{$this->table_name} SET ";

        $arr_values = array();

        foreach (array_keys($this->fields) as $key) {

            if (!isset($this->$key)) {
                continue;
            }

            if ($key == $time_field) {
                $sql.= $key ." = FROM_UNIXTIME(?), ";
            } else {
                $sql.= $key ." = ?, ";
            }
            $arr_values[] = $this->$key;
        }
        $sql = substr($sql, 0, strlen($sql)-2) .' ';

        if (!isset($this->id)) {
            $this->$idx_field = 'NULL';
        } else {
            $sql.= "WHERE {$this->column_name}_idx LIKE ?";
            $arr_values[] = $this->id;
        }

        if (!($sth = $db->prepare($sql))) {
            $mtlda->raiseError(__TRAIT__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, $arr_values)) {
            $mtlda->raiseError(__TRAIT__ .", unable to execute query");
            return false;
        }

        if (!isset($this->id) || empty($this->id)) {
            $this->id = $db->getid();
        }

        if (!isset($this->$idx_field) || empty($this->$idx_field) || $this->$idx_field == 'NULL') {
            $this->$idx_field = $this->id;
        }

        $db->freeStatement($sth);

        if (method_exists($this, 'postSave')) {
            if (!$this->postSave()) {
                $mtlda->raiseError(__CLASS__ ."::postSave() method returned false!");
                return false;
            }
        }

        return true;

    } // save()

    public function toggleStatus($to)
    {
        global $db;

        if (!isset($this->id)) {
            return false;
        }
        if (!is_numeric($this->id)) {
            return false;
        }
        if (!isset($this->table_name)) {
            return false;
        }
        if (!isset($this->column_name)) {
            return false;
        }
        if (!in_array($to, array('off', 'on'))) {
            return false;
        }

        if ($to == "on") {
            $new_status = 'Y';
        } elseif ($to == "off") {
            $new_status = 'N';
        }

        $sth = $db->prepare(
            "UPDATE
                TABLEPREFIX{$this->table_name}
                SET
                {$this->column_name}_active = ?
                WHERE
                {$this->column_name}_idx LIKE ?"
        );

        if (!$sth) {
            $mtlda->raiseError(__TRAIT__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($new_status, $this->id))) {
            $mtlda->raiseError(__TRAIT__ .", unable to execute query");
            return false;
        }

        $db->freeStatement($sth);
        return true;

    } // toggleStatus()

    public function toggleChildStatus($to, $child_obj, $child_id)
    {
        global $db, $mtlda;

        if (!isset($this->child_names)) {
            $mtlda->raiseError(__TRAIT__ .", this object has no childs at all!");
            return false;
        }
        if (!isset($this->child_names[$child_obj])) {
            $mtlda->raiseError(__TRAIT__ .", requested child is not known to this object!");
            return false;
        }

        $prefix = $this->child_names[$child_obj];

        if (!($child_obj = $mtlda->load_class($child_obj, $child_id))) {
            $mtlda->raiseError(__TRAIT__ .", unable to locate class for {$child_obj}");
            return false;
        }

        if (!isset($this->id)) {
            return false;
        }
        if (!is_numeric($this->id)) {
            return false;
        }
        if (!isset($this->table_name)) {
            return false;
        }
        if (!isset($this->column_name)) {
            return false;
        }
        if (!in_array($to, array('off', 'on'))) {
            return false;
        }

        if ($to == "on") {
            $new_status = 'Y';
        } elseif ($to == "off") {
            $new_status = 'N';
        }

        $sth = $db->prepare(
            "UPDATE
                TABLEPREFIXassign_{$child_obj->table_name}_to_{$this->table_name}
                SET
                {$prefix}_{$child_obj->column_name}_active = ?
                WHERE
                {$prefix}_{$this->column_name}_idx LIKE ?
                AND
                {$prefix}_{$child_obj->column_name}_idx LIKE ?"
        );

        if (!$sth) {
            $mtlda->raiseError(__TRAIT__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array(
            $new_status,
            $this->id,
            $child_id
        ))) {
            $mtlda->raiseError(__TRAIT__ .", unable to execute query");
            return false;
        }

        $db->freeStatement($sth);
        return true;

    } // toggleChildStatus()

    public function prev()
    {
        global $mtlda, $db;

        $idx_field = $this->column_name ."_idx";
        $guid_field = $this->column_name ."_guid";

        $result = $db->fetchSingleRow(
            "
                SELECT
                    {$id},
                    {$guid_field}
                FROM
                    TABLEPREFIX{$this->table_name}
                WHERE
                    {$id} = (
                        SELECT
                            MAX({$id})
                        FROM
                            TABLEPREFIX{$this->table_name}
                        WHERE
                            {$id} < {$this->id}
                    )"
        );

        if (!isset($result)) {
            $mtlda->raiseError(__TRAIT__ .", unable to locate previous record!");
            return false;
        }

        if (!isset($result->$idx_field) || !isset($result->$guid_field)) {
            $mtlda->raiseError(__TRAIT__ .", no previous record available!");
            return false;
        }

        if (!is_numeric($result->$idx_field) || !$mtlda->isValidGuidSyntax($result->$guid_field)) {
            $mtlda->raiseError(__TRAIT__ .", Invalid previous record found: ". htmlentities($result->$id, ENT_QUOTES));
            return false;
        }

        return $result->$id ."-". $result->$guid_field;
    }

    public function next()
    {
        global $mtlda, $db;

        $idx_field = $this->column_name ."_idx";
        $guid_field = $this->column_name ."_guid";

        $result = $db->fetchSingleRow(
            "
                SELECT
                    {$id},
                    {$guid_field}
                FROM
                    TABLEPREFIX{$this->table_name}
                WHERE
                    {$id} = (
                        SELECT
                            MIN({$id})
                        FROM
                            TABLEPREFIX{$this->table_name}
                        WHERE
                            {$id} > {$this->id}
                    )"
        );

        if (!isset($result)) {
            $mtlda->raiseError(__TRAIT__ .", unable to locate next record!");
            return false;
        }

        if (!isset($result->$idx_field) || !isset($result->$guid_field)) {
            $mtlda->raiseError(__TRAIT__ .", no next record available!");
            return false;
        }

        if (!is_numeric($result->$idx_field) || !$mtlda->isValidGuidSyntax($result->$guid_field)) {
            $mtlda->raiseError(__TRAIT__ .", invalid next record found: ". htmlentities($result->$id, ENT_QUOTES));
            return false;
        }

        return $result->$id ."-". $result->$guid_field;
    }

    public function isDuplicate()
    {
        global $mtlda, $db;

        // no need to check yet if $id isn't set
        if (empty($this->id)) {
            return false;
        }

        $idx_field = $this->column_name.'_idx';
        $guid_field = $this->column_name.'_guid';

        if (
            (
                !isset($this->$idx_field) || empty($this->$idx_field)
            ) && (
                !isset($this->$guid_field) || empty($this->$guid_field)
            )
        ) {

            $mtlda->raiseError(__TRAIT__ ." can't check for duplicates if neither \$idx_field or \$guid_field is set!");
            return false;
        }

        $arr_values = array();
        $where_sql = '';
        if (isset($this->$idx_field) && !empty($this->$idx_field)) {
            $where_sql.= "
                {$idx_field} LIKE ?
            ";
            $arr_values[] = $this->$idx_field;
        }
        if (isset($this->$guid_field) && !empty($this->$guid_field)) {
            if (!empty($where_sql)) {
                $where_sql.= "
                    AND
                ";
            }
            $where_sql.= "
                {$guid_field} LIKE ?
            ";
            $arr_values[] = $this->$guid_field;
        }

        if (
            !isset($where_sql) ||
            empty($where_sql) ||
            !is_string($where_sql)
        ) {
            return false;
        }

        $sql = "SELECT
            {$idx_field}
            FROM
            TABLEPREFIX{$this->table_name}
            WHERE
                {$idx_field} <> {$this->id}
            AND
            {$where_sql}
        ";

        $sth = $db->prepare($sql);

        if (!$sth) {
            $mtlda->raiseError(__TRAIT__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, $arr_values)) {
            $mtlda->raiseError(__TRAIT__ .", unable to execute query");
            return false;
        }

        if ($sth->rowCount() <= 0) {
            $db->freeStatement($sth);
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
