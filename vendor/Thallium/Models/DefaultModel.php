<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015> <Andreas Unterkircher>
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

namespace Thallium\Models ;

use \PDO;

abstract class DefaultModel
{
    public $table_name;
    public $column_name;
    public $child_names;
    public $ignore_child_on_clone;
    public $fields;
    public $id;
    public $init_values;
    protected $permit_rpc_updates = false;
    protected $rpc_allowed_fields = array();
    protected $rpc_allowed_actions = array();

    public function __construct($id = null)
    {
        global $thallium;

        if (!isset($this->table_name)) {
            $thallium->raiseError(__METHOD__ .', missing key table_name', true);
            return false;
        }

        if (!isset($this->column_name)) {
            $thallium->raiseError(__METHOD__ .', missing key column_name', true);
            return false;
        }

        if (!isset($this->fields)) {
            $thallium->raiseError(__METHOD__ .', missing key fields', true);
            return false;
        }

        if (!isset($id) || empty($id)) {
            $this->initFields();
            return true;
        }

        $this->init_values = array();
        $this->id = $id;

        if (!$this->load()) {
            $thallium->raiseError(__CLASS__ ."::load() returned false!", true);
            return false;
        }

        return true;

    } // __construct()

    /**
     * load
     *
     */
    protected function load()
    {
        global $thallium, $db;

        if (!isset($this->fields) || empty($this->fields)) {
            $thallium->raiseError(__METHOD__ .", fields array not set for class ". get_class($this));
        }

        if (method_exists($this, 'preLoad')) {
            if (!$this->preLoad()) {
                $thallium->raiseError(get_called_class() ."::preLoad() method returned false!");
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
            $thallium->raiseError(__METHOD__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($this->id))) {
            $thallium->raiseError(__METHOD__ .", unable to execute query");
            return false;
        }

        if ($sth->rowCount() <= 0) {
            $db->freeStatement($sth);
            $thallium->raiseError(__METHOD__ .", No object with id {$this->id}");
        }

        if (!$row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $db->freeStatement($sth);
            $thallium->raiseError(__METHOD__ .", unable to fetch SQL result for object id ". $this->id);
        }

        $db->freeStatement($sth);

        foreach ($row as $key => $value) {
            $this->init_values[$key] = $value;
            $this->$key = $value;
        }

        if (method_exists($this, 'postLoad')) {
            if (!$this->postLoad()) {
                $thallium->raiseError(get_called_class() ."::postLoad() method returned false!");
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
    final public function update($data)
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
        global $thallium, $db;

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
                $thallium->raiseError(get_called_class() ."::preDelete() method returned false!");
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
            $thallium->raiseError(__METHOD__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($this->id))) {
            $thallium->raiseError(__METHOD__ .", unable to execute query");
            return false;
        }

        $db->freeStatement($sth);

        if (method_exists($this, 'postDelete')) {
            if (!$this->postDelete()) {
                $thallium->raiseError(get_called_class() .", postDelete() method returned false!");
                return false;
            }
        }

        return true;

    } // delete()

    /**
     * clone
     */
    final public function createClone(&$srcobj)
    {
        global $thallium, $db;

        if (!isset($srcobj->id)) {
            return false;
        }
        if (!is_numeric($srcobj->id)) {
            return false;
        }
        if (!isset($srcobj->fields)) {
            return false;
        }

        foreach (array_keys($srcobj->fields) as $field) {
            // check for a matching key in clone's fields array
            if (!in_array($field, array_keys($this->fields))) {
                continue;
            }

            $this->$field = $srcobj->$field;
        }

        if (method_exists($this, 'preClone')) {
            if (!$this->preClone($srcobj)) {
                $thallium->raiseError(get_called_class() ."::preClone() method returned false!");
                return false;
            }
        }

        $idx_field = $this->column_name.'_idx';
        $guid_field = $this->column_name.'_guid';
        $pguid = $this->column_name.'_derivation_guid';

        $this->id = null;
        if (isset($this->$idx_field)) {
            $this->$idx_field = null;
        }
        if (isset($this->$guid_field)) {
            $this->$guid_field = $thallium->createGuid();
        }

        // record the parent objects GUID
        if (isset($srcobj->$guid_field) && !empty($srcobj->$guid_field)) {
            $this->$pguid = $srcobj->$guid_field;
        }

        $this->save();

        // if saving was successful, our new object should have an ID now
        if (!isset($this->id) || empty($this->id)) {
            $thallium->raiseError(__METHOD__ .", error on saving clone. no ID was returned from database!");
            return false;
        }

        $this->$idx_field = $this->id;

        // now check for assigned childrens and duplicate those links too
        if (isset($this->child_names) && !isset($this->ignore_child_on_clone)) {
            // loop through all (known) childrens
            foreach (array_keys($this->child_names) as $child) {
                $prefix = $this->child_names[$child];

                // initate an empty child object
                if (!($child_obj = $thallium->load_class($child))) {
                    $thallium->raiseError(__METHOD__ .", unable to locate class for {$child_obj}");
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
                    $thallium->raiseError(__METHOD__ .", unable to prepare query");
                    return false;
                }

                if (!$db->execute($sth, array($srcobj->id))) {
                    $thallium->raiseError(__METHOD__ .", unable to execute query");
                    return false;
                }

                while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
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
                        $row[$this->child_names[$child] .'_guid'] = $thallium->createGuid();
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
            if (!$this->postClone($srcobj)) {
                $thallium->raiseError(get_called_class() ."::postClone() method returned false!");
                return false;
            }
        }

        return true;

    } // createClone()

    /**
     * init fields
     */
    final protected function initFields($override = null)
    {
        global $thallium, $db;

        if (!isset($this->fields) || !is_array($this->fields)) {
            return;
        }

        foreach (array_keys($this->fields) as $field) {
            // check for a matching key in clone's fields array
            if (isset($override) &&
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
    final public function __set($name, $value)
    {
        global $thallium;

        if (!isset($this->fields) || empty($this->fields)) {
            $thallium->raiseError(__METHOD__ .", fields array not set for class ". get_class($this));
        }

        if (!array_key_exists($name, $this->fields) && $name != 'id') {
            $thallium->raiseError(__METHOD__ .", unknown key in ". __CLASS__ ."::__set(): {$name}");
        }

        $this->$name = $value;

    } // __set()

    final public function save()
    {
        global $thallium, $db;

        if (!isset($this->fields) || empty($this->fields)) {
            $thallium->raiseError(__METHOD__ .", fields array not set for class ". get_class($this));
        }

        if (method_exists($this, 'preSave')) {
            if (!$this->preSave()) {
                $thallium->raiseError(get_called_class() ."::preSave() method returned false!");
                return false;
            }
        }

        $guid_field = $this->column_name .'_guid';
        $idx_field = $this->column_name .'_idx';
        $time_field = $this->column_name .'_time';

        if (!isset($this->$guid_field) || empty($this->$guid_field)) {
            $this->$guid_field = $thallium->createGuid();
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
            $thallium->raiseError(__METHOD__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, $arr_values)) {
            $thallium->raiseError(__METHOD__ .", unable to execute query");
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
                $thallium->raiseError(get_called_class() ."::postSave() method returned false!");
                return false;
            }
        }

        // now we need to update the init_values array.

        $this->init_values = array();

        foreach (array_keys($this->fields) as $field) {
            if (!isset($this->$field)) {
                continue;
            }

            $this->init_values[$field] = $this->$field;
        }

        return true;

    } // save()

    final public function toggleStatus($to)
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
            $thallium->raiseError(__METHOD__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array($new_status, $this->id))) {
            $thallium->raiseError(__METHOD__ .", unable to execute query");
            return false;
        }

        $db->freeStatement($sth);
        return true;

    } // toggleStatus()

    final public function toggleChildStatus($to, $child_obj, $child_id)
    {
        global $db, $thallium;

        if (!isset($this->child_names)) {
            $thallium->raiseError(__METHOD__ .", this object has no childs at all!");
            return false;
        }
        if (!isset($this->child_names[$child_obj])) {
            $thallium->raiseError(__METHOD__ .", requested child is not known to this object!");
            return false;
        }

        $prefix = $this->child_names[$child_obj];

        if (!($child_obj = $thallium->load_class($child_obj, $child_id))) {
            $thallium->raiseError(__METHOD__ .", unable to locate class for {$child_obj}");
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
            $thallium->raiseError(__METHOD__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, array(
            $new_status,
            $this->id,
            $child_id
        ))) {
            $thallium->raiseError(__METHOD__ .", unable to execute query");
            return false;
        }

        $db->freeStatement($sth);
        return true;

    } // toggleChildStatus()

    final public function prev()
    {
        global $thallium, $db;

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
            $thallium->raiseError(__METHOD__ .", unable to locate previous record!");
            return false;
        }

        if (!isset($result->$idx_field) || !isset($result->$guid_field)) {
            $thallium->raiseError(__METHOD__ .", no previous record available!");
            return false;
        }

        if (!is_numeric($result->$idx_field) || !$thallium->isValidGuidSyntax($result->$guid_field)) {
            $thallium->raiseError(
                __METHOD__ .", Invalid previous record found: ". htmlentities($result->$id, ENT_QUOTES)
            );
            return false;
        }

        return $result->$id ."-". $result->$guid_field;
    }

    final public function next()
    {
        global $thallium, $db;

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
            $thallium->raiseError(__METHOD__ .", unable to locate next record!");
            return false;
        }

        if (!isset($result->$idx_field) || !isset($result->$guid_field)) {
            $thallium->raiseError(__METHOD__ .", no next record available!");
            return false;
        }

        if (!is_numeric($result->$idx_field) || !$thallium->isValidGuidSyntax($result->$guid_field)) {
            $thallium->raiseError(__METHOD__ .", invalid next record found: ". htmlentities($result->$id, ENT_QUOTES));
            return false;
        }

        return $result->$id ."-". $result->$guid_field;
    }

    final protected function isDuplicate()
    {
        global $thallium, $db;

        // no need to check yet if $id isn't set
        if (empty($this->id)) {
            return false;
        }

        $idx_field = $this->column_name.'_idx';
        $guid_field = $this->column_name.'_guid';

        if ((!isset($this->$idx_field) || empty($this->$idx_field)) &&
            (!isset($this->$guid_field) || empty($this->$guid_field))
        ) {
            $thallium->raiseError(
                __METHOD__ ." can't check for duplicates if neither \$idx_field or \$guid_field is set!"
            );
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

        if (!isset($where_sql) ||
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
            $thallium->raiseError(__METHOD__ .", unable to prepare query");
            return false;
        }

        if (!$db->execute($sth, $arr_values)) {
            $thallium->raiseError(__METHOD__ .", unable to execute query");
            return false;
        }

        if ($sth->rowCount() <= 0) {
            $db->freeStatement($sth);
            return false;
        }

        $db->freeStatement($sth);
        return true;
    }

    final protected function column($suffix)
    {
        global $thallium;

        if (!isset($this->column_name) || empty($this->column_name)) {
            return $suffix;
        }

        return $this->column_name .'_'. $suffix;
    }

    final protected function permitRpcUpdates($state)
    {
        global $thallium;

        if (!is_bool($state)) {
            $thallium->raiseError(__METHOD__ .', parameter must be a boolean value');
            return false;
        }

        $this->permit_rpc_updates = $state;
        return true;
    }

    final public function permitsRpcUpdates()
    {
        if (!isset($this->permit_rpc_updates) ||
            !$this->permit_rpc_updates
        ) {
            return false;
        }

        return true;
    }

    final protected function addRpcEnabledField($field)
    {
        global $thallium;

        if (!is_array($this->rpc_allowed_fields)) {
            $thallium->raiseError("\$rpc_allowed_fields is not an array!");
            return false;
        }

        if (!is_string($field)) {
            $thallium->raiseError(__METHOD__ .' parameter must be a string');
            return false;
        }

        if (in_array($field, $this->rpc_allowed_fields)) {
            return true;
        }

        array_push($this->rpc_allowed_fields, $field);
        return true;
    }

    final protected function addRpcAction($action)
    {
        global $thallium;

        if (!is_array($this->rpc_allowed_actions)) {
            $thallium->raiseError("\$rpc_allowed_actions is not an array!");
            return false;
        }

        if (!is_string($action)) {
            $thallium->raiseError(__METHOD__ .', parameter must be a string!');
            return false;
        }

        if (in_array($action, $this->rpc_allowed_actions)) {
            return true;
        }

        array_push($this->rpc_allowed_actions, $action);
        return true;
    }

    final public function permitsRpcUpdateToField($field)
    {
        global $thallium;

        if (!is_array($this->rpc_allowed_fields)) {
            $thallium->raiseError("\$rpc_allowed_fields is not an array!");
            return false;
        }

        if (!is_string($field)) {
            $thallium->raiseError(__METHOD__ .' parameter must be a string');
            return false;
        }

        if (empty($this->rpc_allowed_fields)) {
            return false;
        }

        if (!in_array($field, $this->rpc_allowed_fields)) {
            return false;
        }

        return true;
    }

    final public function permitsRpcActions($action)
    {
        global $thallium;

        if (!is_array($this->rpc_allowed_actions)) {
            $thallium->raiseError("\$rpc_allowed_actions is not an array!");
            return false;
        }

        if (!is_string($action)) {
            $thallium->raiseError(__METHOD__ .' parameter must be a string');
            return false;
        }

        if (empty($this->rpc_allowed_actions)) {
            return false;
        }

        if (!in_array($action, $this->rpc_allowed_actions)) {
            return false;
        }

        return true;
    }

    final public function getId()
    {
        global $thallium;

        if (!isset($this->fields[$this->column_name .'_idx'])) {
            $thallium->raiseError(__CLASS__ .'has no idx field!');
            return false;
        }

        $guid_field = $this->column_name .'_idx';

        if (!isset($this->$guid_field)) {
            return false;
        }

        return $this->$guid_field;
    }

    final public function getGuid()
    {
        global $thallium;

        if (!isset($this->fields[$this->column_name .'_guid'])) {
            $thallium->raiseError(__CLASS__ .'has no guid field!');
            return false;
        }

        $guid_field = $this->column_name .'_guid';

        if (!isset($this->$guid_field)) {
            return false;
        }

        return $this->$guid_field;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4: