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

class TesseractController extends \Thallium\Controllers\DefaultController
{
    protected $image;
    protected $executable = 'tesseract';
    protected $languages = [];
    protected $psm;
    protected $configs = [];

    /**
     * constructor
     *
     * @param string $image
     * @return void
     */
    public function __construct($image)
    {
        if (!isset($image) || empty($image) || !is_string($image)) {
            static::raiseError(__METHOD__ .'(), $image parameter is invalid!', true);
            return;
        }

        if (!file_exists($image) || !is_readable($image)) {
            static::raiseError(__METHOD__ ."(), {$image} does not exist or is not readable!", true);
            return;
        }

        $this->image = $image;

        parent::__construct();
        return;
    }

    /**
     * perform the OCR scan
     *
     * @params none
     * @return string
     */
    public function run()
    {
        if (!`which tesseract`) {
            static::raiseError(__METHOD__ .'(), tesseract is not available!');
            return false;
        }

        if (($run_cmd = $this->getRunCommand()) === false) {
            static::raiseError(__CLASS__ .'::getRunCommand() returned false!');
            return false;
        }

        $desc = array(
            0 => array('pipe','r'), /* STDIN */
            1 => array('pipe','w'), /* STDOUT */
            2 => array('pipe','w'), /* STDERR */
        );

        $retval = '';
        $error = '';

        if (($process = proc_open($run_cmd, $desc, $pipes)) === false) {
            static::raiseError(__METHOD__ .'(), proc_open() returned false!');
            return false;
        }

        if (!is_resource($process)) {
            static::raiseError(__METHOD__ .'(), proc_open() has not returned a ressource!');
            return false;
        }

        $stdin = $pipes[0];
        $stdout = $pipes[1];
        $stderr = $pipes[2];

        while (!feof($stdout)) {
            $retval.= trim(fgets($stdout));
        }

        while (!feof($stderr)) {
            $error.= trim(fgets($stderr));
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exit_code = proc_close($process);

        if (!empty($error)) {
            static::raiseError(__METHOD__ .'(), stderr output received! '. $error);
        }

        return $retval;
    }

    /**
     * Sets the language(s).
     *
     * @param string|array $languages
     * @return bool
     */
    public function lang()
    {
        $this->languages = func_get_args();
        return true;
    }

    /**
     * Sets the Page Segmentation Mode value.
     *
     * @param integer $psm
     * @return bool
     */
    public function psm($psm)
    {
        $this->psm = $psm;
        return true;
    }

    /**
     * Sets a tesseract configuration value.
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function config($key, $value)
    {
        $this->configs[$key] = $value;
        return true;
    }

    /**
     * Builds the tesseract command with all its options.
     * This method is 'protected' instead of 'protected' to make testing easier.
     *
     * @return string
     */
    protected function getRunCommand()
    {
        $cmd = $this->executable.' '.escapeshellarg($this->image).' stdout'
            .$this->buildLanguagesParam()
            .$this->buildPsmParam()
            .$this->buildConfigurationsParam();

        if (!isset($cmd) || empty($cmd)) {
            static::raiseError(__METHOD__ .'(), failed to built command!');
            return false;
        }

        return $cmd;
    }

    /**
     * If one (or more) languages are defined, return the correspondent command
     * line argument to the tesseract command.
     *
     * @return string
     */
    protected function buildLanguagesParam()
    {
        return $this->languages ? ' -l '.join('+', $this->languages) : '';
    }

    /**
     * If a page segmentation mode is defined, return the correspondent command
     * line argument to the tesseract command.
     *
     * @return string
     */
    protected function buildPsmParam()
    {
        return is_null($this->psm) ? '' : ' -psm '.$this->psm;
    }

    /**
     * Return tesseract command line arguments for every custom configuration.
     *
     * @return string
     */
    protected function buildConfigurationsParam()
    {
        $buildParam = function ($config, $value) {
            return ' -c '.escapeshellarg("$config=$value");
        };
        return join('', array_map(
            $buildParam,
            array_keys($this->configs),
            array_values($this->configs)
        ));
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
