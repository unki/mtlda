<?php

/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015-2016> <Andreas Unterkircher>
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

namespace Thallium\Controllers;

/**
 * AuditController
 *
 * @package Thallium\Controllers\AuditController
 * @subpackage Controllers
 * @license AGPL3
 * @copyright 2015-2016 Andreas Unterkircher <unki@netshadow.net>
 * @author Andreas Unterkircher <unki@netshadow.net>
 */
class AuditController extends DefaultController
{
    /**
     * log an audit entry
     *
     * @param string $message
     * @param string $entry_type
     * @param string $scene
     * @param string|null $guid
     * @return bool
     * @throws \MasterShaper\Controller\ExceptionController
     */
    public function log($message, $entry_type, $scene, $guid = null)
    {
        global $thallium;

        if (!isset($message) || empty($message) || !is_string($message)) {
            static::raiseError(__METHOD__ .'(), $message parameter is invalid!');
            return false;
        }

        if (!isset($entry_type) || empty($entry_type) || !is_string($entry_type)) {
            static::raiseError(__METHOD__ .'(), $entry_type parameter is invalid!');
            return false;
        }

        if (!isset($scene) || empty($scene) || !is_string($scene)) {
            static::raiseError(__METHOD__ .'(), $scene parameter is invalid!');
            return false;
        }

        if (isset($guid) && !empty($guid) && !is_string($guid)) {
            static::raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        if (isset($guid) && !$thallium->isValidGuidSyntax($guid)) {
            static::raiseError(__METHOD__ .'(), $guid parameter is not a valid GUID!');
            return false;
        }

        try {
            $entry = new \Thallium\Models\AuditEntryModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load AuditEntryModel', false, $e);
            return false;
        }

        if (!$entry->setMessage($message)) {
            static::raiseError(get_class($entry) .'::setMessage() returned false!');
            return false;
        }

        if (isset($guid) && !empty($guid) && !$entry->setEntryGuid($guid)) {
            static::raiseError(get_class($entry) .'::setEntryGuid() returned false!');
            return false;
        }

        if (!$entry->setEntryType($entry_type)) {
            static::raiseError(get_class($entry) .'::setEntryType() returned false!');
            return false;
        }

        if (!$entry->setScene($scene)) {
            static::raiseError(get_class($entry) .'::setScene() returned false!');
            return false;
        }

        if (!$entry->save()) {
            static::raiseError(get_class($entry) .'::save() returned false!');
            return false;
        }

        return true;
    }

    /**
     * returns the audit log entries for a specific GUID
     *
     * @param string $guid
     * @return string|bool
     * @throws \MasterShaper\Controller\ExceptionController
     */
    public function retrieveAuditLog($guid)
    {
        global $thallium;

        if (!isset($guid) || empty($guid) || !is_string($guid)) {
            static::raiseError(__METHOD__ .'(), $guid parameter is invalid!');
            return false;
        }

        if (!$thallium->isValidGuidSyntax($guid)) {
            static::raiseError(__METHOD__ .'(), $guid parameter does not contain a valid GUID!');
            return false;
        }

        try {
            $log = new \Thallium\Models\AuditLogModel(array(
                FIELD_GUID => $guid
            ));
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load AuditLogModel!', false, $e);
            return false;
        }

        if (!$log->hasItems()) {
            return 'No audit log entries available!';
        }

        if (($entries = $log->getItems()) === false) {
            static::raiseError(get_class($log) .'::getItemsData() returned false!');
            return false;
        }

        if (!is_array($entries)) {
            static::raiseError(get_class($log) .'::getItemsData() returned invalid data!');
            return false;
        }

        if (empty($entries)) {
            return 'No audit log entries available!';
        }

        $txt_ary = array();

        foreach ($entries as $entry) {
            if (!$entry->hasMessage()) {
                continue;
            }
            if (($message = $entry->getMessage()) === false) {
                static::raiseError(get_class($entry) .'::getMessage() returned false!');
                return false;
            }
            $txt_ary[] = $message;
        }

        if (empty($txt_ary)) {
            return 'No audit log entries available!';
        }

        $txt_log = implode('\n', $txt_ary);
        return $txt_log;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
