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

function rpc_object_archive(elements)
{
    if (!(elements instanceof Array)) {
        throw 'elements is not an Array!';
        return false;
    }

    ids = new Array;
    guids = new Array;
    models = new Array;
    titles = new Array;

    elements.forEach(function (element) {
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

        if (!(title = element.attr('title'))) {
            alert('no attribute "title" found!');
            return false;
        }

        title[id] = title;
    });

    wnd = show_modal({
        blurring : true,
        closeable : false,
        header : title,
        icon : 'wait icon',
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    }, function () {}, '.ui.archive.modal');

    progressbar = $('.ui.modal .image.content .description #progressbar');

    if (!progressbar) {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    ids.forEach(function (id) {
        msg_body = new Object;
        msg_body.id = safe_string(id);
        msg_body.guid = safe_string(guids[id]);
        msg_body.model = safe_string(models[id]);

        var msg = new MtldaMessage;
        msg.setCommand('archive-request');
        msg.setMessage(msg_body);
        if (!mbus.add(msg)) {
            throw 'MtldaMessageBus.add() returned false!';
            return false;
        }
    });

    mbus.subscribe('archive-replies-handler', 'archive-reply', function (reply) {
        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (!progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        var newData = new Object;

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
        wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        setTimeout(function () {
            wnd.modal('hide');
            mbus.unsubscribe('archive-replies-handler');
            location.reload();
        }, 1000);
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'MtldaMessageBus.send() returned false!';
        return false;
    }

    return true;

} // rpc_archive_object()

function rpc_object_update(element, successMethod)
{
    if (!(element instanceof jQuery) ) {
        throw "element is not a jQuery object!";
        return false;
    }

    var target = element.attr('data-target');

    if (target == undefined || target == '') {
        alert('no attribute "data-target" found!');
        return false;
    }


    if (!(input = element.find('input[name="'+target+'"]'))) {
        throw "Failed to get input element!";
        return false;
    }

    if (!(action = input.attr('data-action'))) {
        throw "Unable to locate 'data-action' attribute!";
        return false;
    }

    if (!(model = input.attr('data-model'))) {
        throw "Unable to locate 'data-model' attribute!";
        return false;
    }

    if (!(key = input.attr('data-key'))) {
        throw "Unable to locate 'data-key' attribute!";
        return false;
    }

    if (!(id = input.attr('data-id'))) {
        throw "Unable to locate 'data-id' attribute!";
        return false;
    }

    if (!(value = input.val())) {
        return false;
    }

    action = safe_string(action);
    model = safe_string(model);
    key = safe_string(key);
    id = safe_string(id);
    value = safe_string(value);

    if (
        window.location.pathname != undefined &&
        window.location.pathname != '' &&
        !window.location.pathname.match(/\/$/)
    ) {
        url = window.location.pathname;
    } else {
        url = 'rpc.html';
    }

    $.ajax({
        type: 'POST',
        url: url,
        data: ({
            type   : 'rpc',
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
            if (action == 'add') {
                location.reload();
                return;
            }
            if (!successMethod) {
                return;
            }
            successMethod(data);
            return;
        }
    });

    return true;
}

function rpc_object_sign(element)
{
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

    if (!(title = element.attr('data-title'))) {
        alert('no attribute "data-title" found!');
        return false;
    }

    wnd = show_modal({
        blurring : true,
        closeable : false,
        header : 'MTLDA is signing your document "'+ title + '".',
        icon : 'wait icon',
        /*icon : 'icons',
        iconHtml : "<i class='huge archive icon'></i><i class='inverted corner big wait icon'></i>",*/
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    }, function () {}, '.ui.signer.modal');

    progressbar = $('.ui.modal .image.content .description #progressbar');

    if (!progressbar) {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    var msg_body = new Object;
    msg_body.id = safe_string(id);
    msg_body.guid = safe_string(guid);

    var msg = new MtldaMessage;
    msg.setCommand('sign-request');
    msg.setMessage(msg_body);

    if (!mbus.add(msg)) {
        throw 'MtldaMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('signing-replies-handler', 'sign-reply', function (reply) {
        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (!progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        var newData = new Object;

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
        wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        setTimeout(function () {
            wnd.modal('hide');
            mbus.unsubscribe('signing-replies-handler');
            location.reload();
        }, 1000);
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'MtldaMessageBus.send() returned false!';
        return false;
    }

    return true;
}

function rpc_object_scan(element)
{
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

    if (!(title = element.attr('data-title'))) {
        alert('no attribute "data-title" found!');
        return false;
    }

    wnd = show_modal({
        blurring : true,
        closeable : false,
        header : 'MTLDA is scanning your document "'+ title + '".',
        icon : 'wait icon',
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    }, function () {}, '.ui.scanner.modal');

    progressbar = $('.ui.modal .image.content .description #progressbar');

    if (!progressbar) {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    var msg_body = new Object;
    msg_body.id = safe_string(id);
    msg_body.guid = safe_string(guid);

    var msg = new MtldaMessage;
    msg.setCommand('scan-request');
    msg.setMessage(msg_body);

    if (!mbus.add(msg)) {
        throw 'MtldaMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('scanner-replies-handler', 'scan-reply', function (reply) {
        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (!progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        var newData = new Object;

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
        wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        setTimeout(function () {
            wnd.modal('hide');
            mbus.unsubscribe('scanner-replies-handler');
            location.reload();
        }, 1000);
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'MtldaMessageBus.send() returned false!';
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

function rpc_object_delete(element)
{
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

    id = safe_string(id);
    guid = safe_string(guid);
    model = safe_string(model);

    if (
        window.location.pathname != undefined &&
        window.location.pathname != '' &&
        !window.location.pathname.match(/\/$/)
    ) {
        url = window.location.pathname;
    } else {
        url = 'rpc.html';
    }

    $.ajax({
        type: 'POST',
        url: url,
        data: ({
            type   : 'rpc',
            action : 'delete',
            id     : id,
            guid   : guid,
            model  : model
        }),
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function (data) {
            if (data != 'ok') {
                alert('Server returned: ' + data + ', length ' + data.length);
                return;
            }
            location.reload();
            return;
        }
    });

    return true;
}

function rpc_mail_import(element)
{
    if (!(element instanceof jQuery) ) {
        throw "element is not a jQuery object!";
        return false;
    }

    wnd = show_modal({
        blurring : true,
        closeable : false,
        header : 'MTLDA is importing documents from mailbox.',
        icon : 'wait icon',
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
    }, function () {}, '.ui.import.modal');

    progressbar = $('.ui.import.modal .image.content .description #progressbar');

    if (!progressbar) {
        throw 'Can not find the progress bar in the modal window!';
        return false;
    }

    var msg = new MtldaMessage;
    msg.setCommand('mailimport-request');

    if (!mbus.add(msg)) {
        throw 'MtldaMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('mailimport-replies-handler', 'mailimport-reply', function (reply) {
        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }
        if (!progressbar) {
            throw 'Have no reference to the progressbar!';
            return false;
        }

        var newData = new Object;

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
        wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        setTimeout(function () {
            wnd.modal('hide');
            mbus.unsubscribe('mailimport-replies-handler');
            location.reload();
        }, 1000);
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'MtldaMessageBus.send() returned false!';
        return false;
    }

    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
