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

class PdfSigningController
{
    public function __construct()
    {
        global $mtlda, $config;

        if (
            !isset($config['app']['pdf_signing']) ||
            empty($config['app']['pdf_signing']) ||
            !$config['app']['pdf_signing']
            ) {

            $mtlda->raiseError("PdfSigningController, pdf_signing not enabled in config.ini!");
            return false;
        }

        if (
            !isset($config['pdf_signing']) ||
            empty($config['pdf_signing']) ||
            !$config['app']['pdf_signing']
            ) {

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
        );

        foreach ($fields as $field) {

            if (!isset($config['pdf_signing'][$field]) || empty($config['pdf_signing'][$field])) {
                $mtlda->raiseError("PdfSigningController, {$field} not found in section [pdf_signing]!");
                return false;
            }

        }
    }

    public function signDocument()
    {
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($config['pdf_signing']['author']);
        $pdf->SetTitle('Test Title');
        $pdf->SetSubject('Test Subject');
        $pdf->SetKeywords('Test, Keywords');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 052', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------

        // set additional information
        $info = array(
                'Name' => $config['pdf_signing']['author'],
                'Location' => $config['pdf_signing']['location'],
                'Reason' => $config['pdf_signing']['reason'],
                'ContactInfo' => $config['pdf_signing']['contact'],
                );

        // set document signature
        $pdf->setSignature(
            $config['pdf_signing']['certificate'],
            $config['pdf_signing']['private_key'],
            '',
            '',
            1,
            $info
        );

        // set font
        $pdf->SetFont('helvetica', '', 12);

        // add a page
        $pdf->AddPage();

        // print a line of text
        $text = 'This is a <b color="#FF0000">digitally signed document</b> using the default';
        $pdf->writeHTML($text, true, 0, true, 0);

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // *** set signature appearance ***

        // create content for signature (image and/or text)
        $pdf->Image('/usr/share/doc/php-tcpdf/examples/images/tcpdf_signature.png', 180, 60, 15, 15, 'PNG');

        // define active area for signature appearance
        $pdf->setSignatureAppearance(180, 60, 15, 15);

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

        // *** set an empty signature appearance ***
        $pdf->addEmptySignatureAppearance(180, 80, 15, 15);

        // ---------------------------------------------------------

        //Close and output PDF document
        $pdf->Output('example_052.pdf', 'D');

        //============================================================+
        // END OF FILE
        //============================================================+

    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
