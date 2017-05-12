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

class PdfSigningController extends DefaultController
{
    protected $pdf_cfg;
    protected $tsp_digest_algorithm;
    protected $tsp_cfg;

    public function __construct()
    {
        global $mtlda, $config;

        if (!$config->isPdfSigningEnabled()) {
            static::raiseError("PdfSigningController, pdf_signing not enabled in config.ini!");
            return false;
        }

        if (!($this->pdf_cfg = $config->getPdfSigningConfiguration())) {
            static::raiseError("PdfSigningController, pdf_signing enabled but no valid [pdf_signing] section found!");
            return false;
        }

        if (!($this->tsp_cfg = $config->getTimestampConfiguration())) {
            $this->tsp_cfg = array(
                'tsp_algorithm' => 'SHA256'
            );
        }

        if (!isset($this->pdf_cfg['signature_algorithm']) || empty($this->pdf_cfg['signature_algorithm'])) {
            $this->pdf_cfg['signature_algorithm'] = 'SHA256';
        }

        // check if tsp algorithm is supported by the local OpenSSL installation
        if (!preg_match('/^SHA(1|256)$/', $this->tsp_cfg['tsp_algorithm'])) {
            static::raiseError("TSP algorithm {$this->tsp_cfg['tsp_algorithm']} is not supported!");
            return false;
        }

        $supported_alg = openssl_get_md_methods(true);

        if (empty($supported_alg) || !is_array($supported_alg)) {
            static::raiseError("Unable to retrive supported digest algorithms via openssl_get_md_methods()!");
            return false;
        }

        $this->tsp_digest_algorithm = strtolower($this->tsp_cfg['tsp_algorithm']) .'WithRSAEncryption';
        if (!in_array($this->tsp_digest_algorithm, $supported_alg)) {
            static::raiseError("OpenSSL installation does not support {$this->tsp_digest_algorithm} digest algorithm!");
            return false;
        }

        $fields = array(
            'author',
            'location',
            'reason',
            'contact',
            'certificate',
            'private_key'
        );

        foreach ($fields as $field) {
            if (!isset($this->pdf_cfg[$field]) || empty($this->pdf_cfg[$field])) {
                static::raiseError("PdfSigningController, {$field} not found in section [pdf_signing]!");
                return false;
            }
        }
    }

    public function signDocument(&$src_document)
    {
        global $mtlda, $audit;

        if (!is_a($src_document, 'Mtlda\Models\DocumentModel')) {
            static::raiseError(__METHOD__ .' only supports DocumentModels!');
            return false;
        }

        $this->sendMessage('sign-reply', 'Retrieving document copy from archive.', '40%');

        if (!$fqpn = $src_document->getFilePath()) {
            static::raiseError(get_class($src_document) .'::getFilePath() returned false!');
            return false;
        }

        if (!file_exists($fqpn)) {
            static::raiseError("{$fqpn} does not exist!");
            return false;
        }

        if (!is_readable($fqpn)) {
            static::raiseError("{$fqpn} is not readable!");
            return false;
        }

        if (!$src_document->getSigningIconPosition()) {
            static::raiseError("document_signing_icon is not set!");
            return false;
        }

        try {
            $audit->log(
                "signing request",
                "request",
                "signing",
                $src_document->getGuid()
            );
        } catch (\Exception $e) {
            $signing_item->delete();
            static::raiseError("AuditController::log() raised an exception!");
            return false;
        }

        if (($public_key = file_get_contents($this->pdf_cfg['certificate'])) === false) {
            static::raiseError("reading {$this->pdf_cfg['certificate']} failed!");
            return false;
        }

        if (!$public_key = preg_replace('/(\s*)-----(\s*)(BEGIN|END) CERTIFICATE(\s*)-----(\s*)/', '', $public_key)) {
            static::raiseError("failed to strip RSA headers!");
            return false;
        }
        if (!$public_key = str_replace("\n", '', $public_key)) {
            static::raiseError("failed to strip whitespaces from public key!");
            return false;
        }

        $this->sendMessage(
            'sign-reply',
            'Sending SOAP request to signing server '. $this->pdf_cfg['dss_url'] .'.',
            '50%'
        );

        try {
            $dss = new \SoapClient(
                $this->pdf_cfg['dss_url'] .'/wservice/signatureService?wsdl',
                array(
                    'soap_version' => SOAP_1_1,
                    'trace' => 1,
                    'exceptions' => true,
                    'cache_wsdl' => WSDL_CACHE_NONE,
                )
            );
        } catch (\DSSException $d) {
            static::raiseError($d);
            return false;
        } catch (\SOAPFault $f) {
            static::raiseError($f->faultcode .' - '. $f->faultstring);
            return false;
        } catch (\Exception $e) {
            static::raiseError("Failed to load SoapClient!");
            return false;
        }

        if (!is_callable(array($dss, "getDataToSign"))) {
            static::raiseError("Remote side does not provide getDataToSign() method!");
            return false;
        }

        if (!is_callable(array($dss, "signDocument"))) {
            static::raiseError("Remote side does not provide signDocument() method!");
            return false;
        }

        $parameters = new \stdClass;
        $document = new \stdClass();

        $parameters->asicZipComment = false;
        $parameters->chainCertificateList = array();
        $parameters->chainCertificateList[] = array(
            'signedAttribute' => 'true',
            'x509Certificate' => base64_decode($public_key)
        );

        if (isset($this->tsp_cfg['tsp_ca_certificate']) && !empty($this->tsp_cfg['tsp_ca_certificate'])) {
            $parameters->chainCertificateList[] = array(
                'signedAttribute' => 'true',
                'x509Certificate' => base64_decode($this->tsp_cfg['tsp_ca_certificate'])
            );
        }
        $parameters->deterministicId = $src_document->getGuid();
        $parameters->signatureLevel = 'PAdES_BASELINE_LTA';
        $parameters->signaturePackaging = 'ENVELOPED';
        $parameters->digestAlgorithm = $this->pdf_cfg['signature_algorithm'];
        $parameters->encryptionAlgorithm = 'RSA';
        $parameters->timestampDigestAlgorithm = $this->tsp_cfg['tsp_algorithm'];
        $parameters->signWithExpiredCertificate = false;
        $parameters->signingCertificateBytes = base64_decode($public_key);
        $parameters->signerLocation = array(
            'city' => 'Obersdorf',
            'country' => 'Austria',
            'postalAddress' => 'Schloßpark 5/12/5',
            'postalCode' => '2120',
            'stateOrProvince' => 'Lower Austria'
        );
        $parameters->signingDate = time();

        // set document information
        /*$pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($this->pdf_cfg['author']);
        $pdf->SetTitle('Test Title');
        $pdf->SetSubject('Test Subject');
        $pdf->SetKeywords('Test, Keywords');

        // set additional information
        $info = array(
            'Name' => $this->pdf_cfg['author'],
            'Location' => $this->pdf_cfg['location'],
            'Reason' => $this->pdf_cfg['reason'],
            'ContactInfo' => $this->pdf_cfg['contact'],
        ); */

        if (!($document->bytes = file_get_contents($fqpn))) {
            static::raiseError("Failed to read {$fqpn}.");
            return false;
        }
        $document->name = basename($fqpn);
        $document->mimeType = new \stdClass();
        $document->mimeType->mimeTypeString = 'application/pdf';
        $document->absolutePath = $fqpn;

        $this->sendMessage(
            'sign-reply',
            'Submitting document to signing server '. $this->pdf_cfg['dss_url'],
            '60%'
        );

        try {
            $result = $dss->getDataToSign(array(
                'document' => $document,
                'wsParameters' => $parameters
            ));
        } catch (\SoapFault $f) {
            static::raiseError(
                $f->faultcode .' - '. $f->faultstring .'<br />'. htmlspecialchars($dss->__getLastRequest())
            );
            return false;
        } catch (\Exception $e) {
            static::raiseError("SOA getDataToSign() method returned unexpected!");
            return false;
        }

        if (!isset($result) ||
            empty($result) ||
            !isset($result->response) ||
            empty($result->response)
        ) {
            static::raiseError("Invalid response on SOAP request 'getDataToSign'!");
            return false;
        }

        if (!isset($this->pdf_cfg['password']) || empty($this->pdf_cfg['password'])) {
            $this->pdf_cfg['password'] = false;
        }

        if (!$key = openssl_pkey_get_private($this->pdf_cfg['private_key'], $this->pdf_cfg['password'])) {
            static::raiseError("Failed to read private key!");
            return false;
        }

        if (!openssl_sign($result->response, $signature, $key, $this->tsp_digest_algorithm)) {
            openssl_free_key($key);
            static::raiseError("openssl_sign() returned false!");
            return false;
        }

        unset($result);
        openssl_free_key($key);

        if (!isset($signature) || empty($signature)) {
            static::raiseError("openssl_sign() returned invalid signature!");
            return false;
        }

        $this->sendMessage('sign-reply', 'Now signing the signing servers response digest.', '70%');

        try {
            $result = $dss->signDocument(array(
                'document' => $document,
                'wsParameters' => $parameters,
                'signatureValue' => $signature
            ));
        } catch (\SoapFault $f) {
            static::raiseError(
                $f->faultcode .' - '. $f->faultstring .'<br />'. htmlspecialchars($dss->__getLastRequest())
            );
            return false;
        } catch (\Exception $e) {
            static::raiseError("SOA signDocument() method returned unexpected!");
            return false;
        }

        if (!isset($result) || empty($result) || !isset($result->response) || empty($result->response)) {
            static::raiseError("Invalid response on SOAP request 'signDocument'!");
            return false;
        }

        if (!isset($result->response->bytes) ||
            empty($result->response->bytes) ||
            strlen($result->response->bytes) == 0
        ) {
            static::raiseError("No document received up on SOAP request 'signDocument'!");
            return false;
        }

        $this->sendMessage('sign-reply', 'Transfering the signed document into archive.', '80%');

        if (file_put_contents($fqpn, $result->response->bytes) === false) {
            static::raiseError("Failed to write signed document into {$fqpn}!");
            return false;
        }

        unset($result);
        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
