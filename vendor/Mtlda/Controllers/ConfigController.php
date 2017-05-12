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

class ConfigController extends \Thallium\Controllers\ConfigController
{
    public function isImageCachingEnabled()
    {
        if (!isset($this->config['app']['image_cache']) ||
            empty($this->config['app']['image_cache'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['image_cache'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['image_cache'])) {
            return true;
        }

        static::raiseError(__METHOD__ .'(), "image_cache" configuration option in [app] section is invalid!', true);
        return false;
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
        if (!isset($this->config['app']['pdf_signing']) ||
            empty($this->config['app']['pdf_signing'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['pdf_signing'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['pdf_signing'])) {
            return true;
        }

        static::raiseError(__METHOD__ .'(), "pdf_signing" configuration option in [app] section is invalid!', true);
        return false;
    }

    public function isPdfAutoPdfSignOnImport()
    {
        if (!$this->isPdfSigningEnabled()) {
            return false;
        }

        if (!isset($this->config['pdf_signing']['auto_sign_on_import']) ||
            empty($this->config['pdf_signing']['auto_sign_on_import'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['pdf_signing']['auto_sign_on_import'])) {
            return false;
        }

        if ($this->isEnabled($this->config['pdf_signing']['auto_sign_on_import'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "auto_sign_on_import" configuration option in [pdf_signing] section is invalid!',
            true
        );
        return false;
    }

    public function isPdfSigningAttachAuditLogEnabled()
    {
        if (!isset($this->config['pdf_signing']['attach_audit_log']) ||
            empty($this->config['pdf_signing']['attach_audit_log'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['pdf_signing']['attach_audit_log'])) {
            return false;
        }

        if ($this->isEnabled($this->config['pdf_signing']['attach_audit_log'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "attach_audit_log" configuration option in [pdf_signing] section is invalid!',
            true
        );
        return false;
    }

    public function isPdfSignatureVerificationEnabled()
    {
        if (!isset($this->config['app']['pdf_verify_signature']) ||
            empty($this->config['app']['pdf_verify_signature'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['pdf_verify_signature'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['pdf_verify_signature'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "pdf_verify_signature" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function isPdfIndexingEnabled()
    {
        if (!isset($this->config['app']['pdf_indexing']) ||
            empty($this->config['app']['pdf_indexing'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['pdf_indexing'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['pdf_indexing'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "pdf_indexing" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function isMailImportEnabled()
    {
        if (!isset($this->config['app']['mail_import']) ||
            empty($this->config['app']['mail_import'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['mail_import'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['mail_import'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "mail_import" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function isHttpUploadEnabled()
    {
        if (!isset($this->config['app']['http_upload']) ||
            empty($this->config['app']['http_upload'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['http_upload'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['http_upload'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "http_upload" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function isCreatePreviewImageOnImport()
    {
        if (!$this->isImageCachingEnabled()) {
            return false;
        }

        if (!isset($this->config['app']['create_preview_on_import']) ||
            empty($this->config['app']['create_preview_on_import'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['create_preview_on_import'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['create_preview_on_import'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "create_preview_on_import" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function isEmbeddingMtldaIcon()
    {
        if (!isset($this->config['app']['embed_icon_to_pdf']) ||
            empty($this->config['app']['embed_icon_to_pdf'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['embed_icon_to_pdf'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['embed_icon_to_pdf'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "embed_icon_to_pdf" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function getMailImportMailDestinyIsDelete()
    {
        if (!$this->isMailImportEnabled()) {
            return false;
        }

        if (!isset($this->config['mailimport']['mbox_delete_mail']) ||
            empty($this->config['mailimport']['mbox_delete_mail'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['mailimport']['mbox_delete_mail'])) {
            return false;
        }

        if ($this->isEnabled($this->config['mailimport']['mbox_delete_mail'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "mbox_delete_mail" configuration option in [mailimport] section is invalid!',
            true
        );
        return false;
    }

    public function getMailImportImapMailboxExpunge()
    {
        if (!$this->isMailImportEnabled()) {
            return false;
        }

        if (!isset($this->config['mailimport']['mbox_imap_expunge']) ||
            empty($this->config['mailimport']['mbox_imap_expunge'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['mailimport']['mbox_imap_expunge'])) {
            return false;
        }

        if ($this->isEnabled($this->config['mailimport']['mbox_imap_expunge'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "mbox_imap_expunge" configuration option in [mailimport] section is invalid!',
            true
        );
        return false;
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

        if ($this->isDisabled($this->config['mailimport']['use_email_body_as_description'])) {
            return false;
        }

        if ($this->isEnabled($this->config['mailimport']['use_email_body_as_description'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "use_email_body_as_description" configuration option in [mailimport] section is invalid!',
            true
        );
        return false;
    }

    public function isDocumentNoDeleteEnabled()
    {
        if (!isset($this->config['app']['document_no_delete']) ||
            empty($this->config['app']['document_no_delete'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['document_no_delete'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['document_no_delete'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "document_no_delete" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function isUserTriggersImportEnabled()
    {
        if (!isset($this->config['app']['user_triggers_import']) ||
            empty($this->config['app']['user_triggers_import'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['user_triggers_import'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['user_triggers_import'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "user_triggers_import" configuration option in [app] section is invalid!',
            true
        );
        return false;
    }

    public function isPdfOcrEnabled()
    {
        if (!$this->isPdfIndexingEnabled()) {
            return false;
        }

        if (!isset($this->config['pdfindexing']['use_ocr_for_embedded_images']) ||
            empty($this->config['pdfindexing']['use_ocr_for_embedded_images'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['pdfindexing']['use_ocr_for_embedded_images'])) {
            return false;
        }

        if ($this->isEnabled($this->config['pdfindexing']['use_ocr_for_embedded_images'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "use_ocr_for_embedded_images" configuration option in [pdfindexing] section is invalid!',
            true
        );
        return false;
    }

    public function isResetDataPermitted()
    {
        if (!isset($this->config['app']['permit_reset_data']) ||
            empty($this->config['app']['permit_reset_data'])
        ) {
            return false;
        }

        if ($this->isDisabled($this->config['app']['permit_reset_data'])) {
            return false;
        }

        if ($this->isEnabled($this->config['app']['permit_reset_data'])) {
            return true;
        }

        static::raiseError(
            __METHOD__ .'(), "permit_reset_data" configuration option in [app] section is invalid!',
            true
        );

        return false;
    }

    public function getDefaultOcrLanguage()
    {
        if (!isset($this->config['pdfindexing']['default_ocr_language']) ||
            empty($this->config['pdfindexing']['default_ocr_language'])
        ) {
            return 'eng';
        }

        return $this->config['pdfindexing']['default_ocr_language'];
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
