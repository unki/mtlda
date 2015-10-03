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

var MtldaMessageBus = function (id) {

    this.element = id;
    this.messages = new Array;
    this.subscribers = new Array;
    return true;
};

MtldaMessageBus.prototype.add = function (message) {

    if (!message) {
        throw 'No message to send provided!';
        return false;
    }

    if (typeof(message) != 'object') {
        throw 'parameter is not an object!';
        return false;
    }

    this.messages.push(message);
    return true;
}

MtldaMessageBus.prototype.getMessages = function () {
    return this.messages;
}

MtldaMessageBus.prototype.getMessagesCount = function () {
    return this.messages.length;
}

MtldaMessageBus.prototype.send = function (messages) {

    // will not send an empty message
    if (!this.getMessagesCount()) {
        return true;
    }

    try {
        var messages = JSON.stringify(this.getMessages());
    } catch (e) {
        throw 'Failed to convert messages to JSON string! '+ e;
        return false;
    }

    if (!(md = forge.md.sha1.create())) {
        throw 'Failed to initialize forge SHA1 message digest!';
        return false;
    }

    if (!md.update(messages)) {
        throw 'forge SHA1 failed on json input!';
        return false;
    }

    var json = new Object;
    json.count = this.getMessagesCount();
    json.size = messages.length;
    json.hash = md.digest().toHex();
    json.json = messages;

    try {
        var submitmsg = JSON.stringify(json);
    } catch (e) {
        throw 'Failed to convert messages to JSON string! '+ e;
        return false;
    }

    if (!submitmsg) {
        throw 'No message to send provided!';
        return false;
    }

    if (typeof(submitmsg) != 'string') {
        throw 'parameter is not a string!';
        return false;
    }

    $.ajax({
        context: this,
        global: false,
        type: 'POST',
        url: 'rpc.html',
        data: ({
            type : 'rpc',
            action : 'submit-messages',
            messages : submitmsg
        }),
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        //success: this.parseResponse
        success: function (data) {
            if (data != "ok") {
                alert('Failed to submit messages! ' + data);
                return false;
            }
            console.log(this.getSubscribers());
        }.bind(this)
    });
}

MtldaMessageBus.prototype.poll = function () {

    $.ajax({
        context: this,
        global: false,
        type: 'POST',
        url: 'rpc.html',
        data: ({
            type : 'rpc',
            action : 'retrieve-messages',
        }),
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        //success: this.parseResponse
        success: this.parseResponse.bind(this)
    });
}


MtldaMessageBus.prototype.parseResponse = function (data) {

    if (!data) {
        throw 'Requires data to be set!';
        return false;
    }

    if (!(json = JSON.parse(data))) {
        throw 'Failed to parse response!';
        return false;
    }

    if (
        json.hash == undefined ||
        json.size == undefined ||
        json.json == undefined ||
        json.count == undefined
    ) {
        throw 'Response is invalid!';
        return false;
    }

    if (json.json.length != json.size) {
        throw 'Response size does not match!';
        return false;
    }

    if (!(md = forge.md.sha1.create())) {
        throw 'Failed to initialize forge SHA1 message digest!';
        return false;
    }

    if (!md.update(json.json)) {
        throw 'forge SHA1 failed on json input!';
        return false;
    }

    if (json.hash != md.digest().toHex()) {
        throw 'Hash does not match!';
        return false;
    }

    // no messages included? then we are done.
    if (json.count == 0) {
        return true;
    }

    console.log(json.json);
    return true;
    for (var message in json.json) {

        if (!message) {
            throw 'Invalid message!';
            return false;
        }
        console.log(message);
    }

    return true;
};

MtldaMessageBus.prototype.subscribe = function (name, category, handler) {

    if (!name) {
        throw 'No name provided!';
        return false;
    }

    if (!category) {
        throw 'No category provided!';
        return false;
    }

    if (!handler) {
        throw 'No handler provided!';
        return false;
    }

    if (this.subscribers[name]) {
        throw 'A subscriber named '+ name +' has already been registered!';
        return false;
    }

    this.subscribers[name] = new Object;
    this.subscribers[name].category = category;
    this.subscribers[name].handler = handler;
    return true;
}

MtldaMessageBus.prototype.unsubscribe = function (name) {

    if (!this.subscribers[name]) {
        return true;
    }

    delete this.subscribers[name];
    return true;
}

MtldaMessageBus.prototype.getSubscribers = function (category) {

    if (!category) {
        return this.subscribers;
    }

    subscribers = new Array;
    for (var subscriber in this.subscribers) {
        if (subscriber.category != category) {
            continue;
        }
        subscribers.push(subscriber);
    }

    return subscribers;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
