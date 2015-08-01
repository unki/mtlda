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
    public $config_path = MTLDA_BASE ."/config";
    public $config_file = "config.ini";
    public $config_fqpn;
    private $config;

    public function __construct()
    {
        if (!file_exists($this->config_path)) {
            print "Error - configuration directory ". $this->config_path ." does not exist!";
            exit(1);
        }

        if (!is_executable($this->config_path)) {
            print "Error - unable to enter config directory ". $this->config_path ." - please check permissions!";
            exit(1);
        }

        $this->config_fqpn = $this->config_path ."/". $this->config_file;

        if (!file_exists($this->config_fqpn)) {
            print "Error - configuration file ". $this->config_fqpn ." does not exist!";
            exit(1);
        }

        if (!is_readable($this->config_fqpn)) {
            print "Error - unable to read configuration file ". $this->config_fqpn ." - please check permissions!";
            exit(1);
        }

        if (!function_exists("parse_ini_file")) {
            print "Error - this PHP installation does not provide required parse_ini_file() function!";
            exit(1);
        }

        if (($config_ary = parse_ini_file($this->config_fqpn, true)) === false) {
            print "Error - parse_ini_file() function failed on ". $this->config_fqpn ." - please check syntax!";
            exit(1);
        }

        if (empty($config_ary) || !is_array($config_ary)) {
            print "Error - invalid configuration retrieved from ". $this->config_fqpn ." - please check syntax!";
            exit(1);
        }

        if (!isset($config_ary['app']) || empty($config_ary['app']) || !array($config_ary['app'])) {
            print "Mandatory config section [app] is not configured!";
            exit(1);
        }

        // remove trailing slash from base_web_path if any
        if (
            isset($config_ary['app']['base_web_path']) &&
            !empty($config_ary['app']['base_web_path'])) {
            $config_ary['app']['base_web_path'] = rtrim($config_ary['app']['base_web_path'], '/');
        }

        $this->config = $config_ary;
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

        return $pdf_cfg['sign_position'];
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
            $this->config['app']['page_title'] &&
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
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
