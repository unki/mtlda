/**
 * This file is part of Thallium.
 *
 * Thallium, a PHP-based framework for web applications.
 * Copyright (C) <2015> <Andreas Unterkircher>
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

var ThalliumInlineEditable = function (id) {

    this.element = id;

    //
    // flag to indicate if that was modified
    //
    this._touched = false;

    //
    // flag to indicate if that has been saved
    //
    this._saved = false;

    //
    // the original element data
    //
    this._originalValue = false;

    //
    // the last entered data
    //
    this._lastUsedValue = false;

    this._contentBefore = false;
    this._contentEdit = false;

    if (!(id instanceof jQuery)) {
        throw "id is not a jQuery object!";
        return false;
    }

    if (!this.validate()) {
        throw "validate() returned false!";
        return false;
    }

    if (!this.prepare()) {
        throw "prepare() returned false!";
        return false;
    }

    return true;
};

ThalliumInlineEditable.prototype.validate = function () {

    var ref = this.element.attr('data-inline-name');

    if (typeof ref === 'undefined' || ref == '') {
        alert('no attribute "data-inline-name" found!');
        return false;
    }

    if (!this.setDomReference(ref)) {
        throw "setDomReference returned false!";
        return false;
    }

    return true;

    var type = this.element.attr('data-type');

    if (typeof type === 'undefined' || type == '') {
        alert('no attribute "data-type" found!');
        return false;
    }

    this.type = type;
};

ThalliumInlineEditable.prototype.prepare = function () {

    if (typeof (origval = this.getContentAttribute('data-orig-value')) === 'undefined') {
        throw "getContentAttribute() returned false!";
        return false;
    }

    this.setOriginalValue(origval);

    this._contentEdit = '<div name="'
        + this.getDomReference()
        + '" class="inline editable content" data-orig-value="'
        + this.getOriginalValue() + '"></div>';

    return true;
};

ThalliumInlineEditable.prototype.setOriginalValue = function (value) {

    if (typeof value === 'undefined') {
        throw "Parameter is not set!";
        return false;
    }

    this._originalValue = value;
};

ThalliumInlineEditable.prototype.getOriginalValue = function () {

    if (typeof this._originalValue === 'undefined') {
        throw "_originalValue not set!";
        return false;
    }

    return this._originalValue;
};

ThalliumInlineEditable.prototype.getContentAttribute = function (attr) {

    if (typeof (content_select = this.getContentSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (value = $(content_select).attr(attr)) === 'undefined') {
        throw "no attr '" + attr + "' found!";
        return false;
    }

    return value;
};

ThalliumInlineEditable.prototype.setDomReference = function (element) {

    if (typeof element === 'undefined') {
        throw "Parameter must reference an element name!";
        return false;
    }

    this.element = element
    return true;
};

ThalliumInlineEditable.prototype.getDomReference = function () {

    if (typeof this.element === 'undefined') {
        return false;
    }

    return this.element;
};

ThalliumInlineEditable.prototype.getNameSelector = function () {

    if (typeof (name = this.getDomReference()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    return '[name="' + name + '"]';
};

ThalliumInlineEditable.prototype.getContentSelector = function () {

    if (typeof (name = this.getNameSelector()) === 'undefined') {
        throw "getNameSelector() returned false!";
        return false;
    }

    return name + '.inline.editable.content';
}

ThalliumInlineEditable.prototype.getContentValue = function () {

    if (typeof (content_select = this.getContentSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (cur_val = $(content_select).html()) === 'undefined') {
        throw "Can not read the current value!";
        return false;
    }

    return cur_val;
}

ThalliumInlineEditable.prototype.getLastUsedValue = function () {
    return this._lastUsedValue;
}

ThalliumInlineEditable.prototype.toggle = function () {

    if (typeof (name_select = this.getNameSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if ($(name_select + '.inline.editable.edit.link').is(':hidden')) {
        this.showContent();
    } else if ($(name_select + '.inline.editable.edit.link').is(':visible')) {
        this.showForm();
    } else {
        throw "Invalid conditions found!";
        return false;
    }

    return true;
};

ThalliumInlineEditable.prototype.showForm = function () {

    if (typeof (name_select = this.getNameSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (content_select = this.getContentSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (cur_val = this.getContentValue()) === 'undefined') {
        throw "Can not read the current value!";
        return false;
    }

    this._lastUsedValue = cur_val;

    if (typeof (form_src = $(name_select + '.inline.editable.formsrc').html()) === 'undefined') {
        throw "Can not retrieve inline-editable-formsrc!";
        return false;
    }

    if (typeof (content = $(content_select)) === 'undefined') {
        throw "Can not retrieve content!";
        return false;
    }

    $(name_select + '.inline.editable.edit.link').hide();
    this._contentBefore = content.replaceWith(this._contentEdit);

    // renew content handler
    if (typeof (content = $(content_select)) === 'undefined') {
        throw "Can not retrieve content!";
        return false;
    }

    content.html(form_src);

    $(content_select + ' form input').val(cur_val);

    $(content_select).on('click', 'button.cancel', function () {
        if (!this.toggle()) {
            throw "toggle() returned false!";
            return false;
        }
        return true;
    }.bind(this));

    $(content_select).on('input', 'input, textarea', function () {
        if (!this.touch()) {
            throw "touch() returned false!";
            return false;
        }
        return true;
    }.bind(this));

    $(content_select).on('submit', 'form', function () {
        if (!this.save()) {
            throw "save() returned false!";
            return false;
        }
        return true;
    }.bind(this));
};

ThalliumInlineEditable.prototype.showContent = function () {

    if (typeof (name_select = this.getNameSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (content_select = this.getContentSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (this.isSaved()) {
        value = $(content_select + ' form input').val();
    } else {
        if (typeof (value = this.getLastUsedValue()) === 'undefined') {
            value = this.getOriginalValue();
        }
    }

    if (typeof (content = $(content_select)) === 'undefined') {
        throw "Can not retrieve content!";
        return false;
    }

    content.off('click', 'button.cancel');
    content.off('click', 'button.save');
    content.off('input', 'input, textarea');
    content.off('submit', 'form');

    content.hide();
    content.replaceWith(this._contentBefore);

    // renew content handler
    if (typeof (content = $(content_select)) === 'undefined') {
        throw "Can not retrieve content!";
        return false;
    }

    content.html(value);
    content.show();

    $(name_select + '.inline.editable.edit.link').show();
};

ThalliumInlineEditable.prototype.touch = function () {

    if (typeof (content_select = this.getContentSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (input = $(content_select + ' form input')) === 'undefined') {
        throw "Failed to locate input field!";
        return false;
    }

    if (input.val() == this.getLastUsedValue()) {
        this.untouch();
        return true;
    }

    if (typeof (savebutton = $(content_select + ' form button.save')) === 'undefined') {
        throw "can not find the save button!";
        return false;
    }

    if (!savebutton.hasClass('red shape')) {
        savebutton.addClass('red shape').transition('bounce');
    }

    this._touched = true;

    if (this.isSaved()) {
        this.setSaved(false);
    }

    return true;

};

ThalliumInlineEditable.prototype.untouch = function () {

    if (typeof (content_select = this.getContentSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (savebutton = $(content_select + ' form button.save')) === 'undefined') {
        throw "can not find the save button!";
        return false;
    }

    if (savebutton.hasClass('red shape')) {
        savebutton.transition('tada').removeClass('red shape');
    }

    this._touched = false;
    return false;
};

ThalliumInlineEditable.prototype.touched = function () {

    if (typeof this._touched === 'undefined') {
        return false
    }

    if (!this._touched) {
        return false;
    }

    return true;
};

ThalliumInlineEditable.prototype.setSaved = function (value) {

    if (typeof value === 'undefined') {
        this._saved = true;
        return true;
    }

    if (typeof(value) != "boolean") {
        throw "Parameter needs to be boolean!";
        return false;
    }

    this._saved = value;
    return true;
};

ThalliumInlineEditable.prototype.isSaved = function () {

    if (!this._saved) {
        return false;
    }

    return true;
};

ThalliumInlineEditable.prototype.save = function () {

    /* if data hasn't change, just swap views */
    if (this.getContentValue() == this.getLastUsedValue()) {
        this.toggle();
        return true;
    }

    if (typeof (content_select = this.getContentSelector()) === 'undefined') {
        throw "Can not continue without knowning the name!";
        return false;
    }

    if (typeof (input = $(content_select + ' form input')) === 'undefined') {
        throw "Failed to get input element!";
        return false;
    }

    if (typeof (action = input.attr('data-action')) === 'undefined') {
        throw "Unable to locate 'data-action' attribute!";
        return false;
    }

    if (typeof (model = input.attr('data-model')) === 'undefined') {
        throw "Unable to locate 'data-model' attribute!";
        return false;
    }

    if (typeof (key = input.attr('data-key')) === 'undefined') {
        throw "Unable to locate 'data-key' attribute!";
        return false;
    }

    if (typeof (id = input.attr('data-id')) === 'undefined') {
        throw "Unable to locate 'data-id' attribute!";
        return false;
    }

    if (typeof (value = input.val()) === 'undefined') {
        throw "Unable to locate 'value' attribute!";
        return false;
    }

    action = safe_string(action);
    model = safe_string(model);
    key = safe_string(key);
    id = safe_string(id);
    value = safe_string(value);

    if (typeof window.location.pathname !== 'undefined' &&
        window.location.pathname != '' &&
        !window.location.pathname.match(/\/$/)
    ) {
        url = window.location.pathname;
    } else {
        url = 'rpc.html';
    }

    $.ajax({
        context: this,
        global: false,
        type: 'POST',
        url: url,
        data: ({
            type : 'rpc',
            action : action,
            model  : model,
            id     : id,
            key    : key,
            value  : value
        }),
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function (data) {
            if (data != 'ok') {
                alert('Server returned: ' + data + ', length ' + data.length);
                return;
            }
            this.setSaved();
            this.untouch();
            if (action == 'add') {
                location.reload();
                return;
            } else if (action == 'update') {
                this.toggle();
            }
            return;
        }.bind(this)
    });

    return true;
};

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4: