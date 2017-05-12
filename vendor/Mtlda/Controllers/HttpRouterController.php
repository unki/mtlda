<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017> <Andreas Unterkircher>
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

class HttpRouterController extends \Thallium\Controllers\HttpRouterController
{
    public function __construct()
    {
        try {
            $this->addValidRpcAction('delete-document');
            $this->addValidRpcAction('archive');
            $this->addValidRpcAction('sign');
            $this->addValidRpcAction('get-keywords');
            $this->addValidRpcAction('save-keywords');
            $this->addValidRpcAction('save-description');
            $this->addValidRpcAction('get-view');
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), unable to register further RPC actions!', true);
            return false;
        }

        parent::__construct();
    }

    public function isImageCall()
    {
        if (isset($this->query->call_type) && $this->query->call_type == "preview") {
            return true;
        }

        return false;
    }

    public function isDocumentCall()
    {
        if (isset($this->query->call_type) && $this->query->call_type == "document") {
            return true;
        }

        return false;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
