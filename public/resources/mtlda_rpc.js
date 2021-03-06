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

'use strict';

function rpc_object_archive(elements, successMethod)
{
    var ids, guids, models, titles;

    if (!(elements instanceof Array)) {
        throw 'elements is not an Array!';
        return false;
    }

    ids = new Array;
    guids = new Array;
    models = new Array;
    titles = new Array;

    elements.forEach(function (element) {
        var id, guid, model, title;

        if (!(element instanceof jQuery) ) {
            throw "element is not a jQuery object!";
            return false;
        }

        if (!(id = element.attr('data-id'))) {
            alert('no attribute "data-id" found!');
            return false;
        }

        ids.push(id);

        if (!(guid = element.attr('data-guid'))) {
            alert('no attribute "data-guid" found!');
            return false;
        }

        guids[id] = guid;

        if (!(model = element.attr('data-model'))) {
            alert('no attribute "data-model" found!');
            return false;
        }

        models[id] = model;

        if (!(title = element.attr('data-action-title'))) {
            alert('no attribute "data-action-title" found!');
            return false;
        }

        titles[id] = title;
    });

    var archive_wnd = show_modal('progress', {
        header : titles.pop(),
        icon : 'wait icon',
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    });

    var progressbar = archive_wnd.find('.description .ui.indicating.progress');

    if (!progressbar) {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    ids.forEach(function (id) {
        var msg_body = new Object;
        msg_body.id = safe_string(id);
        msg_body.guid = safe_string(guids[id]);
        msg_body.model = safe_string(models[id]);

        var msg = new ThalliumMessage;
        msg.setCommand('archive-request');
        msg.setMessage(msg_body);
        if (!mbus.add(msg)) {
            throw 'ThalliumMessageBus.add() returned false!';
            return false;
        }
    });

    mbus.subscribe('archive-replies-handler', 'archive-reply', function (reply) {
        var newData, value;

        if (typeof reply === 'undefined' || !reply) {
            throw 'reply is empty!';
            return false;
        }
        if (typeof archive_wnd === 'undefined' || !archive_wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (typeof progressbar === 'undefined' || !progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        newData = new Object;

        if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
            newData.percent = value[1];
        }
        if (reply.body) {
            newData.text = {
                active : reply.body,
                success: reply.body
            };
        }
        if (!progressbar.hasClass('active')) {
            progressbar.addClass('active');
        }

        progressbar.progress(newData);
        archive_wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        archive_wnd.modal('hide');
        mbus.unsubscribe('archive-replies-handler');

        if (typeof successMethod !== 'undefined') {
            return successMethod();
        }

        location.reload();
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'ThalliumMessageBus.send() returned false!';
        return false;
    }

    return true;

}

function rpc_object_sign(element)
{
    var id, guid, title;

    if (!(element instanceof jQuery) ) {
        throw "element is not a jQuery object!";
        return false;
    }

    if (typeof (id = element.attr('data-id')) === 'undefined') {
        alert('no attribute "data-id" found!');
        return false;
    }

    if (typeof (guid = element.attr('data-guid')) === 'undefined') {
        alert('no attribute "data-guid" found!');
        return false;
    }

    if (typeof (title = element.attr('data-action-title')) === 'undefined') {
        alert('no attribute "data-action-title" found!');
        return false;
    }

    var sign_wnd = show_modal('progress', {
        header : 'MTLDA is signing your document "'+ title + '".',
        icon : 'wait icon',
        /*icon : 'icons',
        iconHtml : "<i class='huge archive icon'></i><i class='inverted corner big wait icon'></i>",*/
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    });

    var progressbar = sign_wnd.find('.description .ui.indicating.progress');

    if (typeof progressbar === 'undefined') {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    var msg_body = new Object;
    msg_body.id = safe_string(id);
    msg_body.guid = safe_string(guid);

    var msg = new ThalliumMessage;
    msg.setCommand('sign-request');
    msg.setMessage(msg_body);

    if (!mbus.add(msg)) {
        throw 'ThalliumMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('signing-replies-handler', 'sign-reply', function (reply) {
        var newData, value;

        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!sign_wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (!progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        newData = new Object;

        if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
            newData.percent = value[1];
        }

        if (reply.body) {
            newData.text = {
                active : reply.body,
                success: reply.body
            };
        }

        if (!progressbar.hasClass('active')) {
            progressbar.addClass('active');
        }

        progressbar.progress(newData);
        sign_wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        setTimeout(function () {
            sign_wnd.modal('hide');
            mbus.unsubscribe('signing-replies-handler');
            location.reload();
        }, 1000);
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'ThalliumMessageBus.send() returned false!';
        return false;
    }

    return true;
}

function rpc_object_scan(element, successMethod)
{
    var id, guid, model, title, scan_wnd, msg_body, msg, progressbar;

    if (!(element instanceof jQuery) ) {
        throw "element is not a jQuery object!";
        return false;
    }

    if (!(id = element.attr('data-id'))) {
        alert('no attribute "data-id" found!');
        return false;
    }

    if (!(guid = element.attr('data-guid'))) {
        alert('no attribute "data-guid" found!');
        return false;
    }

    if (!(model = element.attr('data-model'))) {
        alert('no attribute "data-model" found!');
        return false;
    }

    if (!(title = element.attr('data-action-title'))) {
        alert('no attribute "data-action-title" found!');
        return false;
    }

    scan_wnd = show_modal('progress', {
        header : 'MTLDA is scanning your document "'+ title + '".',
        icon : 'wait icon',
        hasActions : false,
        allowMultiple : true,
        closable: false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    });

    progressbar = scan_wnd.find('.description .ui.indicating.progress');

    if (!progressbar) {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    msg_body = new Object;
    msg_body.id = safe_string(id);
    msg_body.guid = safe_string(guid);
    msg_body.model = safe_string(model);

    msg = new ThalliumMessage;
    msg.setCommand('scan-request');
    msg.setMessage(msg_body);

    if (!mbus.add(msg)) {
        throw 'ThalliumMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('scanner-replies-handler', 'scan-reply', function (reply) {
        var newData, value;

        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!scan_wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (!progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        newData = new Object;

        if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
            newData.percent = value[1];
        }
        if (reply.body) {
            newData.text = {
                active : reply.body,
                success: reply.body
            };
        }
        if (!progressbar.hasClass('active')) {
            progressbar.addClass('active');
        }

        progressbar.progress(newData);
        scan_wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        mbus.unsubscribe('scanner-replies-handler');

        if (typeof successMethod !== 'undefined') {
            return successMethod(scan_wnd);
        }

        location.reload();
        return true;
    }.bind(this));

    if (!mbus.send()) {
        throw 'ThalliumMessageBus.send() returned false!';
        return false;
    }

    return true;
}

function rpc_fetch_jobstatus()
{
    if (!mbus.poll()) {
        throw 'MessageBus.poll() returned false!';
        return false;
    }
}

function rpc_mail_import(element)
{
    var import_wnd, progressbar;

    if (!(element instanceof jQuery) ) {
        throw "element is not a jQuery object!";
        return false;
    }

    import_wnd = show_modal('progress', {
        header : 'MTLDA is importing documents from mailbox.',
        icon : 'wait icon',
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    });

    progressbar = import_wnd.find('.description .ui.indicating.progress');

    if (!progressbar) {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    var msg = new ThalliumMessage;
    msg.setCommand('mailimport-request');
    msg.setMessage('run');

    if (!mbus.add(msg)) {
        throw 'ThalliumMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('mailimport-replies-handler', 'mailimport-reply', function (reply) {
        var newData, value;

        if (typeof reply === 'undefined' || !reply) {
            throw 'reply is empty!';
            return false;
        }
        if (typeof import_wnd === 'undefined' || !import_wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (typeof progressbar === 'undefined' || !progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        newData = new Object;

        if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
            newData.percent = value[1];
        }
        if (reply.body) {
            newData.text = {
                active : reply.body,
                success: reply.body
            };
        }
        if (!progressbar.hasClass('active')) {
            progressbar.addClass('active');
        }

        progressbar.progress(newData);
        import_wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        setTimeout(function () {
            import_wnd.modal('hide');
            mbus.unsubscribe('mailimport-replies-handler');
            location.reload();
        }, 1000);
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'ThalliumMessageBus.send() returned false!';
        return false;
    }

    return true;
}

function rpc_get_content(view, request_data)
{
    var data = {
        type    : 'rpc',
        action  : 'get-content',
        view    : view
    };

    if (typeof request_data !== 'undefined' && request_data) {
        data.data = request_data;
    }

    return $.ajax({
        type  : "POST",
        url   : "rpc.html",
        cache : false,
        data  : data,
        retries: 0,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            if (textStatus == 'timeout') {
                this.retries++;
                if (this.retries <= 3) {
                    $.ajax(this);
                    return;
                }
            }
            alert('Failed to contact server! ' + textStatus);
        },
        success: function (data) {
            if (!data) {
                window.alert("no data received from server!");
                return false;
            }
            return true;
        }
    });
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
