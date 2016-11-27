<?php

/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
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

namespace Mtlda\Views;

class KeywordsView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'keywords';

    public function __construct()
    {
        try {
            $keywords = new \Mtlda\Models\KeywordsModel;
        } catch (\Exception $e) {
            static::raiseError(__CLASS_ .', failed to load KeywordsModel', true);
            return;
        }

        if (!$this->setViewData($keywords)) {
            static::raiseError(__CLASS__ .'::setViewData() returned false!', true);
            return;
        }

        parent::__construct();
        return;
    }

    public function showEdit($id = null, $hash = null)
    {
        global $mtlda, $tmpl;

        if (!isset($id) &&
            !empty($id) &&
            isset($guid) &&
            !empty($guid) &&
            $mtlda->isValidGuidSyntax($guid)
        ) {
            $item = new \Mtlda\Models\KeywordModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } else {
            $item = new \Mtlda\Models\KeywordModel;
        }

        if (!isset($item) || empty($item)) {
            static::raiseError("Failed to load KeywordModel!");
            return false;
        }

        $tmpl->assign('item', $item);
        $tmpl->assign("item_safe_link", "keyword-". $item->getIdx() ."-". $item->getGuid());
        return $tmpl->fetch("keywords_edit.tpl");
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
