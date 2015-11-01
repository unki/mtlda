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

class ConfigController extends \Thallium\Controllers\ConfigController
{
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

        if (!$this->isEnabled($this->config['app']['image_cache'])) {
            return false;
        }

        return true;
    }

    public function getPdfSigningConfiguration()
    {
        if (!isset($this->config['pdf_signing']) ||
            empty($this->config['pdf_signing']) ||
            !is_array($this->config['pdf_signing'])
        ) {
            return false;
        }

        return $this->config['pdf_signing'];
    }

    public function getPdfIndexingConfiguration()
    {
        if (!isset($this->config['pdfindexing']) ||
            empty($this->config['pdfindexing']) ||
            !is_array($this->config['pdfindexing'])
        ) {
            return false;
        }

        return $this->config['pdfindexing'];
    }

    public function getTimestampConfiguration()
    {
        if (!isset($this->config['timestamp']) ||
            empty($this->config['timestamp']) ||
            !is_array($this->config['timestamp'])
        ) {
            return false;
        }

        return $this->config['timestamp'];
    }

    public function getMailImportConfiguration()
    {
        if (!isset($this->config['mailimport']) ||
            empty($this->config['mailimport']) ||
            !is_array($this->config['mailimport'])
        ) {
            return false;
        }

        return $this->config['mailimport'];
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

    public function isPdfSigningEnabled()
    {
        if (isset($this->config['app']['pdf_signing']) &&
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

        if (isset($this->config['pdf_signing']['auto_sign_on_import']) &&
            !empty($this->config['pdf_signing']['auto_sign_on_import']) &&
            $this->isEnabled($this->config['pdf_signing']['auto_sign_on_import'])
        ) {
            return true;
        }

        return false;

    }

    public function isPdfSigningAttachAuditLogEnabled()
    {
        if (isset($this->config['pdf_signing']['attach_audit_log']) &&
            !empty($this->config['pdf_signing']['attach_audit_log']) &&
            $this->isEnabled($this->config['pdf_signing']['attach_audit_log'])
        ) {
            return true;
        }

        return false;
    }

    public function isPdfIndexingEnabled()
    {
        if (isset($this->config['app']['pdf_indexing']) &&
            !empty($this->config['app']['pdf_indexing']) &&
            $this->isEnabled($this->config['app']['pdf_indexing'])
        ) {
            return true;
        }

        return false;
    }

    public function isMailImportEnabled()
    {
        if (!isset($this->config['app']['mail_import'])) {
            return false;
        }

        if (!$this->isEnabled($this->config['app']['mail_import'])) {
            return false;
        }

        return true;
    }

    public function isHttpUploadEnabled()
    {
        if (!isset($this->config['app']['http_upload'])) {
            return false;
        }

        if (!$this->isEnabled($this->config['app']['http_upload'])) {
            return false;
        }

        return true;
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

        if (!$this->isEnabled($this->config['app']['create_preview_on_import'])) {
            return false;
        }

        return true;
    }

    public function isEmbeddingMtldaIcon()
    {
        if (!isset($this->config['app']['embed_icon_to_pdf'])) {
            return false;
        }

        if (empty($this->config['app']['embed_icon_to_pdf'])) {
            return false;
        }

        if (!$this->config['app']['embed_icon_to_pdf']) {
            return false;
        }

        if (!$this->isEnabled($this->config['app']['embed_icon_to_pdf'])) {
            return false;
        }

        return true;
    }

    public function getMailImportMailDestinyIsDelete()
    {
        if (!$this->isMailImportEnabled()) {
            return false;
        }

        if (!isset($this->config['mailimport']['mbox_delete_mail'])) {
            return false;
        }

        if (empty($this->config['mailimport']['mbox_delete_mail'])) {
            return false;
        }

        if (!$this->config['mailimport']['mbox_delete_mail']) {
            return false;
        }

        if (!$this->isEnabled($this->config['mailimport']['mbox_delete_mail'])) {
            return false;
        }

        return true;
    }

    public function getMailImportImapMailboxExpunge()
    {
        if (!$this->isMailImportEnabled()) {
            return false;
        }

        if (!isset($this->config['mailimport']['mbox_imap_expunge'])) {
            return false;
        }

        if (empty($this->config['mailimport']['mbox_imap_expunge'])) {
            return false;
        }

        if (!$this->config['mailimport']['mbox_imap_expunge']) {
            return false;
        }

        if (!$this->isEnabled($this->config['mailimport']['mbox_imap_expunge'])) {
            return false;
        }

        return true;
    }

    public function isUseEmailBodyAsDescription()
    {
        if (!$this->isMailImportEnabled()) {
            return false;
        }

        if (!isset($this->config['mailimport']['use_email_body_as_description']) ||
            empty($this->config['mailimport']['use_email_body_as_description'])
        ) {
            return false;
        }

        if (!$this->isEnabled($this->config['mailimport']['use_email_body_as_description'])) {
            return false;
        }

        return true;
    }

    public function isDocumentNoDeleteEnabled()
    {
        if (!isset($this->config['app']['document_no_delete']) ||
            empty($this->config['app']['document_no_delete'])
        ) {
            return false;
        }

        if (!$this->isEnabled($this->config['app']['document_no_delete'])) {
            return false;
        }

        return true;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
