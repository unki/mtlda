<?php

namespace MTLDA\Models ;

use \PDO;

class DefaultModel
{
    public $table_name;
    public $column_name;
    public $child_names;
    public $ignore_child_on_clone;
    public $fields;

    public function __construct($id)
    {
        global $mtlda;

        if (!isset($this->table_name)) {
            $mtlda->raiseError('missing key table_name');
        }

        if (!isset($this->column_name)) {
            $mtlda->raiseError('missing key column_name');
        }

        if (!isset($this->fields)) {
            $mtlda->raiseError('missing key fields');
        }

        if (!isset($id)) {
            $id = null;
        }

        if (isset($id)) {
            $this->id = $id;
            $this->load();
            return;
        }

    } // __construct()

    /**
     * load
     *
     */
    private function load()
    {
        global $mtlda, $db;

        $sth = $db->prepare(
            "SELECT
            *
            FROM
            TABLEPREFIX{$this->table_name}
            WHERE
            ". $this->column_name ."_idx LIKE ?
            ",
            array('integer')
        );

        $db->execute($sth, array(
                    $this->id,
                    ));

        if ($sth->rowCount() <= 0) {
            $db->freeStatement($sth);
            $mtlda->raiseError("No object with id ". $this->id);
        }

        if (!$row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $db->freeStatement($sth);
            $mtlda->raiseError("Unable to fetch SQL result for object id ". $this->id);
        }

        $db->freeStatement($sth);

        foreach ($row as $key => $value) {
            $this->$key = $value;
        }

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

        if (method_exists($this, 'pre_delete')) {
            $this->pre_delete();
        }

        /* generic delete */
        $sth = $db->prepare("
                DELETE FROM
                TABLEPREFIX". $this->table_name ."
                WHERE
                ". $this->column_name ."_idx LIKE ?
                ");

        $db->execute($sth, array(
                    $this->id
                    ));

        $db->freeStatement($sth);

        if (method_exists($this, 'post_delete')) {
            $this->post_delete();
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

        if (method_exists($this, 'pre_clone')) {
            $this->pre_clone();
        }

        foreach (array_keys($srcobj->fields) as $field) {

            // check for a matching key in clone's fields array
            if (!in_array($field, array_keys($this->fields))) {
                continue;
            }

            $this->$field = $srcobj->$field;
        }

        $idx = $this->column_name.'_idx';
        $guid = $this->column_name.'_guid';
        $name = $this->column_name.'_name';

        $this->id = null;
        if (isset($this->$idx)) {
            $this->$idx = null;
        }
        if (isset($this->$guid)) {
            $this->$guid = $mtlda->create_guid();
        }
        if (isset($this->$name)) {
            $this->$name = "Copy of ". $this->$name;
        }

        $this->save();

        // if saving was successful, our new object should have an ID now
        if (!isset($this->id) || empty($this->id)) {
            $mtlda->raiseError("error on saving clone. no ID was returned");
        }

        // now check for assigned childrens and duplicate those links too
        if (isset($this->child_names) && !isset($this->ignore_child_on_clone)) {

            // loop through all (known) childrens
            foreach (array_keys($this->child_names) as $child) {

                $prefix = $this->child_names[$child];

                // initate an empty child object
                if (!($child_obj = $mtlda->load_class($child))) {
                    $mtlda->raiseError("unable to locate class for ". $child_obj);
                    return false;
                }

                // sadly an ugly hardcoded hack is required here as
                // the target-idx field in assign_targets_to_targets
                // is atg_group_idx not atg_target_idx.
                if ($this->table_name == "targets") {
                    $this->column_name = "group";
                }

                $sth = $db->prepare("
                        SELECT
                        *
                        FROM
                        TABLEPREFIXassign_". $child_obj->table_name ."_to_". $this->table_name ."
                        WHERE
                        ". $prefix ."_". $this->column_name ."_idx LIKE ?
                        ");

                $db->execute($sth, array(
                            $srcobj->id,
                            ));

                while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {

                    $query = "INSERT INTO TABLEPREFIXassign_".
                        $child_obj->table_name
                        ."_to_".
                        $this->table_name ." (";

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
                        $row[$this->child_names[$child] .'_guid'] = $mtlda->create_guid();
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

        if (method_exists($this, 'post_clone')) {
            $this->post_clone();
        }

        return true;

    } // createClone()

    /**
     * init fields
     */
    public function initFields($override)
    {
        global $mtlda, $db;

        if (!isset($this->fields) || !is_array($this->fields)) {
            return;
        }

        foreach (array_keys($this->fields) as $field) {

            // check for a matching key in clone's fields array
            if (in_array($field, array_keys($override))) {
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
            $mtlda->raiseError("Fields array not set for class ". get_class($this));
        }

        if (!array_key_exists($name, $this->fields) && $name != 'id') {
            $mtlda->raiseError("Unknown key in ". get_class($this) ."::__set(): ". $name);
        }

        $this->$name = $value;

    } // __set()

    public function save()
    {
        global $mtlda, $db;

        if (!isset($this->fields) || empty($this->fields)) {
            $mtlda->raiseError("Fields array not set for class ". get_class($this));
        }

        if (method_exists($this, 'pre_save')) {
            $this->pre_save();
        }

        /* new object */
        if (!isset($this->id)) {
            $sql = 'INSERT INTO ';
            /* existing object */
        } else {
            $sql = 'UPDATE ';
        }

        $sql.= "TABLEPREFIX". $this->table_name .' SET ';

        $arr_values = array();

        foreach (array_keys($this->fields) as $key) {
            $sql.= $key ." = ?, ";
            $arr_values[] = $this->$key;
        }
        $sql = substr($sql, 0, strlen($sql)-2) .' ';

        if (!isset($this->id)) {
            $idx_name = $this->column_name .'_idx';
            $this->$idx_name = 'NULL';
        } else {
            $sql.= 'WHERE '. $this->column_name .'_idx LIKE ?';
            $arr_values[] = $this->id;
        }

        $sth = $db->prepare($sql, array_values($this->fields));

        $db->execute($sth, $arr_values);

        if (!isset($this->id) || empty($this->id)) {
            $this->id = $db->db_getid();
        }

        $db->freeStatement($sth);

        if (method_exists($this, 'post_save')) {
            $this->post_save();
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

        $sth = $db->prepare("
                UPDATE
                TABLEPREFIX". $this->table_name ."
                SET
                ". $this->column_name ."_active = ?
                WHERE
                ". $this->column_name ."_idx LIKE ?
                ");

        $db->execute($sth, array(
                    $new_status,
                    $this->id
                    ));

        $db->freeStatement($sth);
        return true;

    } // toggleStatus()

    public function toggleChildStatus($to, $child_obj, $child_id)
    {
        global $db, $mtlda;

        if (!isset($this->child_names)) {
            $mtlda->raiseError("This object has no childs at all!");
            return false;
        }
        if (!isset($this->child_names[$child_obj])) {
            $mtlda->raiseError("Requested child is not known to this object!");
            return false;
        }

        $prefix = $this->child_names[$child_obj];

        if (!($child_obj = $mtlda->load_class($child_obj, $child_id))) {
            $mtlda->raiseError("unable to locate class for ". $child_obj);
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

        $sth = $db->prepare("
                UPDATE
                TABLEPREFIXassign_". $child_obj->table_name ."_to_". $this->table_name ."
                SET
                ". $prefix ."_". $child_obj->column_name ."_active = ?
                WHERE
                ". $prefix ."_". $this->column_name ."_idx LIKE ?
                AND
                ". $prefix ."_". $child_obj->column_name ."_idx LIKE ?
                ");

        $db->execute($sth, array(
                    $new_status,
                    $this->id,
                    $child_id
                    ));

        $db->freeStatement($sth);
        return true;

    } // toggleChildStatus()

    public function prev()
    {
        global $mtlda, $db;

        $id = $this->column_name ."_idx";
        $guid = $this->column_name ."_guid";

        $result = $db->fetchSingleRow(
            "
                SELECT
                    {$id},
                    {$guid}
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
            $mtlda->raiseError("Unable to locate previous record!");
            return false;
        }

        if (!isset($result->$id) || !isset($result->$guid)) {
            $mtlda->raiseError("No previous record available!");
            return false;
        }

        if (!is_numeric($result->$id) || !$mtlda->isValidGuidSyntax($result->$guid)) {
            $mtlda->raiseError("Invalid previous record found: ". htmlentities($result->$id, ENT_QUOTES));
            return false;
        }

        return $result->$id ."-". $result->$guid;
    }

    public function next()
    {
        global $mtlda, $db;

        $id = $this->column_name ."_idx";
        $guid = $this->column_name ."_guid";

        $result = $db->fetchSingleRow(
            "
                SELECT
                    {$id},
                    {$guid}
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
            $mtlda->raiseError("Unable to locate next record!");
            return false;
        }

        if (!isset($result->$id) || !isset($result->$guid)) {
            $mtlda->raiseError("No next record available!");
            return false;
        }

        if (!is_numeric($result->$id) || !$mtlda->isValidGuidSyntax($result->$guid)) {
            $mtlda->raiseError("Invalid next record found: ". htmlentities($result->$id, ENT_QUOTES));
            return false;
        }

        return $result->$id ."-". $result->$guid;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
