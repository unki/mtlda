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

namespace Mtlda\Controllers;

class SearchController extends DefaultController
{
    protected $result = array();

    public function search($objectofdesire)
    {
        if (!$this->validateInput($objectofdesire)) {
            $this->raiseError(__CLASS__ .'::validateInput() returned false!');
            return false;
        }

        if (!$this->query($objectofdesire)) {
            $this->raiseError(__CLASS__ .'::query() returned false!');
            return false;
        }

        return true;
    }

    protected function validateInput($input)
    {
        if (!isset($input) || !is_string($input)) {
            return false;
        }

        return true;
    }

    protected function query($query)
    {
        $query_ary = array(
            'data' => $query,
            'type' => 'string'
        );

        if (($time = $this->isDateString($query)) !== false) {
            $query_ary = array(
                'data' => $time,
                'type' => 'timestamp'
            );
        }

        if (preg_match('/^(archive|keyword|keyword):(.+)$/', $query, $matches)) {
            $query_ary['filter'] = $matches[1];
            $query_ary['data'] = $matches[2];
        }

        if (!$this->queryArchive($query_ary)) {
            $this->raiseError(__CLASS__ .'::queryArchive() returned false!');
            return false;
        }

        if (!$this->queryQueue($query_ary)) {
            $this->raiseError(__CLASS__ .'::queryQueue() returned false!');
            return false;
        }

        if (!$this->queryKeywords($query_ary)) {
            $this->raiseError(__CLASS__ .'::queryKeywords() returned false');
            return false;
        }

        return true;
    }

    protected function queryArchive($query)
    {
        global $db;

        if (!$this->isValidSearchQuery($query)) {
            $this->raiseError(__CLASS__ .'::isValidSearchQuery() returned false!');
            return false;
        }

        if (isset($query['filter']) && $query['filter'] != 'archive') {
            return true;
        }

        $sql =
            "SELECT
                document_idx,
                document_guid
            FROM
                TABLEPREFIXarchive a";

        if ($query['type'] == 'string') {
            $sql.=
                " LEFT JOIN
                     TABLEPREFIXdocument_indices di
                ON (
                    a.document_file_hash=di.di_file_hash
                )
                LEFT JOIN
                    TABLEPREFIXdocument_properties dp
                ON (
                    a.document_file_hash=dp.dp_file_hash
                )
                WHERE (
                    a.document_title LIKE :searchwild
                OR
                    a.document_file_name LIKE :searchwild
                OR
                    dp.dp_value LIKE :searchwild
                OR
                    MATCH (document_description) AGAINST (:search)
                OR
                    MATCH (di.di_text) AGAINST (:search)
                )";

        } elseif ($query['type'] == 'timestamp') {
            $sql.=
                " document_time LIKE :search";
        }

        $sql.=
            " AND (
                    document_deleted <> 'Y'
                OR
                    document_deleted IS NULL
            )";

        if (!($sth = $db->prepare($sql))) {
            $this->raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!($sth->bindValue(':searchwild', '%'. $db->quote($query['data']) .'%'))) {
            $this->raiseError(get_class($sth) .'::bindParam() returned false!');
            return false;
        }

        if (!($sth->bindValue(':search', $db->quote($query['data'])))) {
            $this->raiseError(get_class($sth) .'::bindParam() returned false!');
            return false;
        }

        if (!($db->execute($sth))) {
            $this->raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        if (!$sth->rowCount()) {
            return true;
        }

        while ($row = $sth->fetch()) {
            try {
                $document = new \Mtlda\Models\DocumentModel(
                    $row->document_idx,
                    $row->document_guid
                );
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ .'(), failed to load DocumentModel!');
                return false;
            }
            array_push($this->result, $document);
        }

        return true;
    }

    protected function queryQueue($query)
    {
        global $db;

        if (!$this->isValidSearchQuery($query)) {
            $this->raiseError(__CLASS__ .'::isValidSearchQuery() returned false!');
            return false;
        }

        if (isset($query['filter']) && $query['filter'] != 'queue') {
            return true;
        }

        $sql =
            "SELECT
                queue_idx,
                queue_guid
            FROM
                TABLEPREFIXqueue";
        if ($query['type'] == 'string') {
            $sql.=
                " WHERE
                    queue_file_name LIKE :searchwild
                OR
                    MATCH (queue_description) AGAINST (:search)";

        } elseif ($query['type'] == 'timestamp') {
            $sql.=
                " queue_time LIKE :search";
        }

        if (!($sth = $db->prepare($sql))) {
            $this->raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!($sth->bindValue(':searchwild', '%'. $db->quote($query['data']) .'%'))) {
            $this->raiseError(get_class($sth) .'::bindParam() returned false!');
            return false;
        }

        if (!($sth->bindValue(':search', $db->quote($query['data'])))) {
            $this->raiseError(get_class($sth) .'::bindParam() returned false!');
            return false;
        }

        if (!($db->execute($sth))) {
            $this->raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        if (!$sth->rowCount()) {
            return true;
        }

        while ($row = $sth->fetch()) {
            try {
                $queueitem = new \Mtlda\Models\QueueItemModel(
                    $row->queue_idx,
                    $row->queue_guid
                );
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ .'(), failed to load QueueItemModel!');
                return false;
            }
            array_push($this->result, $queueitem);
        }

        return true;
    }

    protected function queryKeywords($query)
    {
        global $db;

        if (!$this->isValidSearchQuery($query)) {
            $this->raiseError(__CLASS__ .'::isValidSearchQuery() returned false!');
            return false;
        }

        if (isset($query['filter']) && $query['filter'] != 'keyword') {
            return true;
        }

        if ($query['type'] == 'timestamp') {
            return true;
        }

        $sql =
            "SELECT
                keyword_idx,
                keyword_guid
            FROM
                TABLEPREFIXkeywords
            WHERE
                keyword_name LIKE :searchwild";

        if (!($sth = $db->prepare($sql))) {
            $this->raiseError(get_class($db) .'::prepare() returned false!');
            return false;
        }

        if (!($sth->bindValue(':searchwild', '%'. $db->quote($query['data']) .'%'))) {
            $this->raiseError(get_class($sth) .'::bindParam() returned false!');
            return false;
        }

        /*if (!($sth->bindValue(':search', $db->quote($query['data'])))) {
            $this->raiseError(get_class($sth) .'::bindParam() returned false!');
            return false;
        }*/

        if (!($db->execute($sth))) {
            $this->raiseError(get_class($db) .'::execute() returned false!');
            return false;
        }

        if (!$sth->rowCount()) {
            return true;
        }

        while ($row = $sth->fetch()) {
            try {
                $keyword = new \Mtlda\Models\KeywordModel(
                    $row->keyword_idx,
                    $row->keyword_guid
                );
            } catch (\Exception $e) {
                $this->raiseError(__METHOD__ .'(), failed to load KeywordModel!');
                return false;
            }
            array_push($this->result, $keyword);
        }

        return true;
    }

    public function getResults()
    {
        if (!isset($this->result)) {
            $this->raiseError(__METHOD__ .'(), no result available!');
            return false;
        }

        return $this->result;
    }

    protected function isDateString($string)
    {
        if (($time = strtotime($string)) === false) {
            return false;
        }

        if (!is_integer($time)) {
            return false;
        }

        return $time;
    }

    protected function isValidSearchQuery($query)
    {
        if (!isset($query) ||
            empty($query) ||
            !is_array($query)
        ) {
            return false;
        }

        if (!isset($query['data']) ||
            empty($query['data']) ||
            !isset($query['type']) ||
            empty($query['type'])
        ) {
            return false;
        }

        if ($query['type'] != 'string' &&
            $query['type'] != 'timestamp'
        ) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
