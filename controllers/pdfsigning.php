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

use MTLDA\Models;

class PdfSigningController extends DefaultController
{
    private $pdf_cfg;

    public function __construct()
    {
        global $mtlda, $config;

        if (!$config->isPdfSigningEnabled()) {
            $mtlda->raiseError("PdfSigningController, pdf_signing not enabled in config.ini!");
            return false;
        }

        if (!($this->pdf_cfg = $config->getPdfSigningConfiguration())) {
            $mtlda->raiseError("PdfSigningController, pdf_signing enabled but no valid [pdf_signing] section found!");
            return false;
        }

        $fields = array(
            'author',
            'location',
            'reason',
            'contact',
            'certificate',
            'private_key',
            'sign_position',
        );

        foreach ($fields as $field) {

            if (!isset($this->pdf_cfg[$field]) || empty($this->pdf_cfg[$field])) {
                $mtlda->raiseError("PdfSigningController, {$field} not found in section [pdf_signing]!");
                return false;
            }

        }
    }

    public function signDocument($fqpn, &$src_item)
    {
        global $mtlda, $audit;

        if (!file_exists($fqpn)) {
            $mtlda->raiseError("{$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            $mtlda->raiseError("{$fqpn} is not readable!");
            return false;
        }

        if (!isset($src_item->document_signing_icon_position) || empty($src_item->document_signing_icon_position)) {
            $mtlda->raiseError("document_signing_icon is not set!");
            return false;
        }

        try {
            $audit->log(
                "signing request",
                "request",
                "signing",
                $src_item->document_guid
            );
        } catch (Exception $e) {
            $signing_item->delete();
            $mtlda->raiseError("AuditController::log() raised an exception!");
            return false;
        }

        $pdf = new \FPDI();
        $page_count = $pdf->setSourceFile($fqpn);

        for ($page_no = 1; $page_no <= $page_count; $page_no++) {

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

            if ($page_no == 1) {

                $signing_icon_position = $this->getSigningIconPosition(
                    $src_item->document_signing_icon_position,
                    $size['w'],
                    $size['h']
                );

                if (!$signing_icon_position) {
                    $mtlda->raiseError("getSigningIconPosition() returned false!");
                    return false;
                }

                if (
                    empty($signing_icon_position) ||
                    !is_array($signing_icon_position) ||
                    !isset($signing_icon_position['x-pos']) ||
                    empty($signing_icon_position['x-pos']) ||
                    !isset($signing_icon_position['y-pos']) ||
                    empty($signing_icon_position['y-pos'])
                ) {
                    $mtlda->raiseError("getSigningIconPosition() returned invalid posіtions!");
                    return false;
                }

                $pdf->Image(
                    MTLDA_BASE.'/public/resources/images/MTLDA_signed.png',
                    $signing_icon_position['x-pos'],
                    $signing_icon_position['y-pos'],
                    16 /* width */,
                    16 /* height */,
                    'PNG',
                    null,
                    null,
                    true /* resize */
                );
            }

        }

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($this->pdf_cfg['author']);
        $pdf->SetTitle('Test Title');
        $pdf->SetSubject('Test Subject');
        $pdf->SetKeywords('Test, Keywords');

        // set default header data
        //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 052', PDF_HEADER_STRING);

        // set header and footer fonts
        //$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        //$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        //$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        //$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        //$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        //$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        //$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // set image scale factor
        //$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        /*if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }*/

        // ---------------------------------------------------------

        // set additional information
        $info = array(
            'Name' => $this->pdf_cfg['author'],
            'Location' => $this->pdf_cfg['location'],
            'Reason' => $this->pdf_cfg['reason'],
            'ContactInfo' => $this->pdf_cfg['contact'],
        );

        // set document signature
        $pdf->setSignature(
            $this->pdf_cfg['certificate'],
            $this->pdf_cfg['private_key'],
            '',
            '',
            1,
            $info
        );

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // *** set signature appearance ***

        // create content for signature (image and/or text)
        // $pdf->Image(MTLDA_BASE.'public/resources/image/MTLDA_signed.png', 180, 60, 15, 15, 'PNG');

        // define active area for signature appearance
        $pdf->setSignatureAppearance(
            $signing_icon_position['x-pos'],
            $signing_icon_position['y-pos'],
            16,
            16,
            1 /* page number */,
            "MTLDA Document Signature"
        );

        // ---------------------------------------------------------

        //Close and output PDF document
        $pdf->Output($fqpn, 'F');
        return true;

    }

    private function getSigningIconPosition($icon_position, $page_width, $page_height)
    {
        global $mtlda;

        if (empty($icon_position)) {
            return false;
        }

        $known_positions = array(
            SIGN_TOP_LEFT,
            SIGN_TOP_CENTER,
            SIGN_TOP_RIGHT,
            SIGN_MIDDLE_LEFT,
            SIGN_MIDDLE_CENTER,
            SIGN_MIDDLE_RIGHT,
            SIGN_BOTTOM_LEFT,
            SIGN_BOTTOM_CENTER,
            SIGN_BOTTOM_RIGHT
        );

        if (!in_array($icon_position, $known_positions)) {
            return false;
        }

        switch ($icon_position) {
            case SIGN_TOP_LEFT:
                $x = 50;
                $y = 10;
                break;
            case SIGN_TOP_CENTER:
                $x = ($page_width/2)-8;
                $y = 10;
                break;
            case SIGN_TOP_RIGHT:
                $x = $page_width - 50;
                $y = 10;
                break;
            case SIGN_MIDDLE_LEFT:
                $x = 50;
                $y = ($page_height/2)-8;
                break;
            case SIGN_MIDDLE_CENTER:
                $x = ($page_width/2)-8;
                $y = ($page_height/2)-8;
                break;
            case SIGN_MIDDLE_RIGHT:
                $x = $page_width - 50;
                $y = ($page_height/2)-8;
                break;
            case SIGN_BOTTOM_LEFT:
                $x = 50;
                $y = $page_height - 50;
                break;
            case SIGN_BOTTOM_CENTER:
                $x = ($page_width/2)-8;
                $y = $page_height - 50;
                break;
            case SIGN_BOTTOM_RIGHT:
                $x = $page_width - 50;
                $y = $page_height - 50;
                break;
            default:
                $mtlda->raiseError("Unkown ѕigning icon position {$icon_position}");
                return false;
        }

        return array(
            'x-pos' => $x,
            'y-pos' => $y
        );
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
