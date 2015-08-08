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

namespace MTLDA\Controllers;

class ConfigController
{
    private $config_path = MTLDA_BASE ."/config";
    private $config_file_local = "config.ini";
    private $config_file_dist = "config.ini.dist";
    private $config;

    public function __construct()
    {
        global $mtlda;

        if (!file_exists($this->config_path)) {
            $mtlda->raiseError(
                "Error - configuration directory {$this->config_path} does not exist!"
            );
            return false;
        }

        if (!is_executable($this->config_path)) {
            $mtlda->raiseError(
                "Error - unable to enter config directory {$this->config_path} - please check permissions!"
            );
            return false;
        }

        if (!function_exists("parse_ini_file")) {
            $mtlda->raiseError(
                "Error - this PHP installation does not provide required parse_ini_file() function!"
            );
            return false;
        }

        foreach (array('dist', 'local') as $config) {

            $config_file = "config_file_{$config}";
            $config_fqpn = $this->config_path ."/". $this->$config_file;

            // missing config.ini is ok
            if ($config == 'local' && !file_exists($config_fqpn)) {
                continue;
            }

            if (!file_exists($config_fqpn)) {
                $mtlda->raiseError("Error - configuration file {$config_fqpn} does not exist!");
                return false;
            }

            if (!is_readable($config_fqpn)) {
                $mtlda->raiseError(
                    "Error - unable to read configuration file {$config_fqpn} - please check permissions!"
                );
                return false;
            }

            if (($config_ary = parse_ini_file($config_fqpn, true)) === false) {
                $mtlda->raiseError(
                    "Error - parse_ini_file() function failed on {$config_fqpn} - please check syntax!"
                );
                return false;
            }

            if (empty($config_ary) || !is_array($config_ary)) {
                $mtlda->raiseError(
                    "Error - invalid configuration retrieved from {$config_fqpn} - please check syntax!"
                );
                exit(1);
            }

            if (!isset($config_ary['app']) || empty($config_ary['app']) || !array($config_ary['app'])) {
                $mtlda->raiseError("Mandatory config section [app] is not configured!");
                exit(1);
            }

            // remove trailing slash from base_web_path if any
            if (
                isset($config_ary['app']['base_web_path']) &&
                !empty($config_ary['app']['base_web_path'])) {

                $config_ary['app']['base_web_path'] = rtrim($config_ary['app']['base_web_path'], '/');

            }

            if (!isset($this->config)) {
                $this->config = $config_ary;
            } else {
                if (!($this->config = array_replace_recursive($this->config, $config_ary))) {
                    $mtlda->raiseError("Failed to merge {$this->config_file_local} with {$this->config_file_dist}.");
                    return false;
                }
            }
        }

        return true;
    }

    public function isImageCachingEnabled()
    {
        if (!isset($this->config['app']['image_cache'])) {
            return false;
        }

        if (empty($this->config['app']['image_cache'])) {
            return false;
        }

        if (!$this->config['app']['image_cache']) {
            return false;
        }

        if (!in_array($this->config['app']['image_cache'], array('yes','y','true','on','1'))) {
            return false;
        }

        return true;
    }

    public function getDatabaseConfiguration()
    {
        if (
            !isset($this->config['database']) ||
            empty($this->config['database']) ||
            !is_array($this->config['database'])
        ) {

            return false;

        }

        return $this->config['database'];
    }

    public function getPdfSigningConfiguration()
    {
        if (
            !isset($this->config['pdf_signing']) ||
            empty($this->config['pdf_signing']) ||
            !is_array($this->config['pdf_signing'])
        ) {

            return false;

        }

        return $this->config['pdf_signing'];
    }

    public function getPdfSigningIconPosition()
    {
        $default_pos = SIGN_TOP_RIGHT;

        if (!($pdf_cfg = $this->getPdfSigningConfiguration())) {
            return $default_pos;
        }

        if (!isset($pdf_cfg['sign_position']) || empty($pdf_cfg['sign_position'])) {
            return $default_pos;
        }

        switch ($pdf_cfg['sign_position']) {

            case SIGN_TOP_LEFT:
            case 'top-left':
                return SIGN_TOP_LEFT;
                break;
            case SIGN_TOP_CENTER:
            case 'top-center':
                return SIGN_TOP_CENTER;
                break;
            case SIGN_TOP_RIGHT:
            case 'top-right':
                return SIGN_TOP_RIGHT;
                break;

            case SIGN_MIDDLE_LEFT:
            case 'middle-left':
                return SIGN_MIDDLE_LEFT;
                break;
            case SIGN_MIDDLE_CENTER:
            case 'middle-center':
                return SIGN_MIDDLE_CENTER;
                break;
            case SIGN_MIDDLE_RIGHT:
            case 'middle-right':
                return SIGN_MIDDLE_RIGHT;
                break;

            case SIGN_BOTTOM_LEFT:
            case 'bottom-left':
                return SIGN_BOTTOM_LEFT;
                break;
            case SIGN_BOTTOM_CENTER:
            case 'bottom-center':
                return SIGN_BOTTOM_CENTER;
                break;
            case SIGN_BOTTOM_RIGHT:
            case 'bottom-right':
                return SIGN_BOTTOM_RIGHT;
                break;
        }

        return false;
    }

    public function getDatabaseType()
    {
        if ($dbconfig = $this->getDatabaseConfiguration()) {

            if (isset($dbconfig['type']) && !empty($dbconfig['type']) && is_string($dbconfig['type'])) {
                return $dbconfig['type'];
            }

        }

        return false;
    }

    public function getWebPath()
    {
        if (
            isset($this->config['app']['base_web_path']) &&
            !empty($this->config['app']['base_web_path']) &&
            is_string($this->config['app']['base_web_path'])
        ) {
            return $this->config['app']['base_web_path'];
        }

        return false;
    }

    public function getPageTitle()
    {
        if (
            isset($this->config['app']['page_title']) &&
            !empty($this->config['app']['page_title']) &&
            is_string($this->config['app']['page_title'])
        ) {

            return $this->config['app']['page_title'];

        }

        return false;
    }

    public function isEnabled($value)
    {

        if (!in_array($value, array('yes','y','true','on','1'))) {
            return false;
        }

        return true;
    }

    public function isDisabled($value)
    {

        if (!in_array($value, array('no','n','false','off','0'))) {
            return false;
        }

        return true;
    }

    public function isPdfSigningEnabled()
    {
        if (
            isset($this->config['app']['pdf_signing']) &&
            !empty($this->config['app']['pdf_signing']) &&
            $this->isEnabled($this->config['app']['pdf_signing'])
        ) {
            return true;
        }

        return false;
    }

    public function isPdfAutoPdfSignOnImport()
    {
        if (!$this->isPdfSigningEnabled()) {
            return false;
        }

        if (
            isset($this->config['pdf_signing']['auto_sign_on_import']) &&
            !empty($this->config['pdf_signing']['auto_sign_on_import']) &&
            $this->isEnabled($this->config['pdf_signing']['auto_sign_on_import'])
        ) {
            return true;
        }

        return false;

    }

    public function isCreatePreviewImageOnImport()
    {
        if (!$this->isImageCachingEnabled()) {
            return false;
        }

        if (!isset($this->config['app']['create_preview_on_import'])) {
            return false;
        }

        if (empty($this->config['app']['create_preview_on_import'])) {
            return false;
        }

        if (!$this->config['app']['create_preview_on_import']) {
            return false;
        }

        if (!in_array($this->config['app']['create_preview_on_import'], array('yes','y','true','on','1'))) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
