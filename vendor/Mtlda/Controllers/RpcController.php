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

class RpcController extends \Thallium\Controllers\RpcController
{
    protected static $valid_content = array(
        'internaltestview',
        'queue',
        'split',
        'preview',
    );

    public function performApplicationSpecifc()
    {
        global $router;

        if (!$router->hasQueryParam('action')) {
            return true;
        }

        switch ($router->getQueryParam('action')) {
            case 'delete-expired-documents':
                return $this->rpcDeleteExpiredDocuments();
                break;
        }

        return false;
    }

    protected function rpcDeleteExpiredDocuments()
    {
        try {
            $archive = new \Mtlda\Controllers\ArchiveController;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .', failed to load ArchiveController!');
            return false;
        }

        if (!($archive->deleteExpiredDocuments())) {
            static::raiseError(get_class($archive) .'::deleteExpiredDocuments() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
