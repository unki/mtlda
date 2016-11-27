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

namespace Mtlda\Controllers;

class PdfSplittingController extends \Thallium\Controllers\JobsController
{
    protected $tempItem;

    public function splitDocument(&$srcitem, $pages)
    {
        if (!isset($srcitem) || empty($srcitem)) {
            static::raiseError(__METHOD__ .'(), $srcitem parameter is invalid!');
            return false;
        }

        if (!is_a($srcitem, 'Mtlda\Models\QueueItemModel')) {
            static::raiseError(__METHOD__ .'(), can only operate with QueueItemModels!');
            return false;
        }

        if (!isset($pages) || (!is_array($pages) and !is_string($pages))) {
            static::raiseError(__METHOD__ .'(), $pages parameter is invalid!');
            return false;
        }

        if (empty($pages)) {
            return true;
        }

        if (is_string($pages)) {
            if (($pages = explode(',', $pages)) === false) {
                static::raiseError(__METHOD__ .'(), failed to explode() pages string!');
                return false;
            }
        }

        try {
            $this->tempItem = new \Mtlda\Models\QueueItemModel;
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to initialize QueueItemModels!');
            return false;
        }

        if (!$this->cloneItem($srcitem, $pages)) {
            static::raiseError(__CLASS__ .'::cloneItem() returned false!');
            return false;
        }

        if (!$this->splitPages($pages)) {
            static::raiseError(__CLASS__ .'::splitPages() returned false!');
            return false;
        }

        return $this->tempItem;
    }

    protected function cloneItem(&$srcitem, $pages)
    {
        try {
            $this->tempItem->createClone($srcitem);
        } catch (\Exception $e) {
            static::raiseError(get_class($this->tempItem) .'::createClone() returned false!');
            return false;
        }

        if ($this->tempItem->hasTitle()) {
            if (($title = $this->tempItem->getTitle()) === false) {
                static::raiseError(get_class($this->tempItem) .'::getTitle() returned false!');
                return false;
            }
            $new_title = "{$title} Page(s) ". implode(', ', $pages) ." ". date("%c");
            if (!$this->tempItem->setTitle($new_title)) {
                static::raiseError(get_class($this->tempItem) .'::setTitle() returned false!');
                return false;
            }
        }

        if (($base = $this->tempItem->getFileNameBase()) === false) {
            static::raiseError(get_class($this->tempItem) .'::getFileNameBase() returned false!');
            return false;
        }

        if (($extension = $this->tempItem->getFileNameExtension()) === false) {
            static::raiseError(get_class($this->tempItem) .'::getFileNameExtension() returned false!');
            return false;
        }

        $new_filename = "{$base}_pages_". implode('-', $pages) .".{$extension}";

        if (!$this->tempItem->setFileName($new_filename)) {
            static::raiseError(get_class($this->tempItem) .'::setFileName() returned false!');
            return false;
        }

        if (!$this->tempItem->save()) {
            static::raiseError(get_class($this->tempItem) .'::save() returned false!');
            return false;
        }

        return true;
    }

    protected function splitPages($pages)
    {
        if (!isset($pages) || empty($pages) || !is_array($pages)) {
            static::raiseError(__METHOD__ .'(), $pages parameter is invalid!');
            return false;
        }

        try {
            $pdf = new \FPDI();
        } catch (\Exception $e) {
            static::raiseError(__METHOD__ .'(), failed to load FPDI!');
            return false;
        }

        if (($fqfn = $this->tempItem->getFilePath()) === false) {
            static::raiseError(get_class($this->tempItem) .'::getFilePath() returned false!');
            return false;
        }

        try {
            $page_count = $pdf->setSourceFile($fqfn);
        } catch (\Exception $e) {
            static::raiseError(getClass($pdf) .'::setSourceFile() has thrown an exception! '. $e->getMessage());
            return false;
        }

        if ($page_count == count($pages)) {
            try {
                @$pdf->cleanUp();
            } catch (\Exception $e) {
                static::raiseError(get_class($pdf) .'::cleanUp() has thrown an exception! '. $e->getMessage());
                return false;
            }
            return true;
        }

        for ($page_no = 1; $page_no <= $page_count; $page_no++) {
            if (!in_array($page_no, $pages)) {
                continue;
            }

            // import a page
            $templateId = $pdf->importPage($page_no);
            // get the size of the imported page
            $size = $pdf->getTemplateSize($templateId);

            // create a page (landscape or portrait depending on the imported page size)
            if ($size['w'] > $size['h']) {
                $pdf->AddPage('L', array($size['w'], $size['h']));
            } else {
                $pdf->AddPage('P', array($size['w'], $size['h']));
            }

            // use the imported page
            $pdf->useTemplate($templateId);
        }

        try {
            $pdf->Output($fqfn, 'F');
        } catch (\Exception $e) {
            static::raiseError(get_class($pdf) .'::Output() returned false!');
                return false;
        }

        if (!$this->tempItem->refresh()) {
            static::raiseError(get_class($this->tempItem) .'::refresh() returned false!');
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
