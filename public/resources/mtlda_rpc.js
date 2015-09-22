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

function rpc_object_delete(element, del_id)
{
    if(del_id == undefined || del_id == '') {
        alert('invalid "del_id" parameter found!');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'rpc.html',
        data: ({
            type : 'rpc',
            action : 'delete',
            id : del_id
        }),
        beforeSend: function() {
            // change row color to red
            element.parent().parent().animate({backgroundColor: '#fbc7c7' }, 'fast');
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(data){
            if(data == 'ok') {
                // on flushing, reload the page
                if(del_id.match(/-flush$/)) {
                    location.reload();
                    return;
                }
                element.parent().parent().animate({ opacity: 'hide' }, 'fast');
                return;
            }
            // change row color back to white
            element.parent().parent().animate({backgroundColor: '#ffffff' }, 'fast');
            alert('Server returned: ' + data + ', length ' + data.length);
            return;
        }
    });

    return true;

} // rpc_object_delete()

function rpc_object_archive(element, obj_id, state)
{
    if(obj_id == undefined || obj_id == '') {
        alert('parameter "obj_id" is invalid!');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'rpc.html',
        data: ({
            type : 'rpc',
            action : 'archive',
            id : obj_id
        }),
        beforeSend: function() {
            // change row color to red
            if(!obj_id.match(/-all$/)) {
                element.parent().parent().animate({backgroundColor: '#fbc7c7' }, 'fast');
            } else {
                $('tr.queueitem').animate({backgroundColor: '#fbc7c7' }, 'fast');
            }
            return;
            if(state) {
                state.text('Processing');
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
            if(state) {
                state.text('Failure');
            }
        },
        success: function(data){
            if(data == 'ok') {
                if(state) {
                    state.text('Done');
                }
                if(!obj_id.match(/-all$/)) {
                    element.parent().parent().animate({ opacity: 'hide' }, 'fast');
                } else {
                    $('tr.queueitem').animate({ opacity: 'hide' }, 'fast');
                }
                return;
            }
            // change row color back to white
            if(!obj_id.match(/-all$/)) {
                element.parent().parent().animate({backgroundColor: '#ffffff' }, 'fast');
            } else {
                $('tr.queueitem').animate({backgroundColor: '#ffffff' }, 'fast');
            }
            alert('Server returned: ' + data + ', length ' + data.length);
            return;
        }
    });

    return true;

} // rpc_archive_object()

function rpc_object_update(element)
{
    var target = element.attr('data-target');

    if(target == undefined || target == '') {
        alert('no attribute "data-target" found!');
        return false;
    }

    var type = element.attr('data-type');

    if(type == undefined || type == '') {
        alert('no attribute "data-type" found!');
        return false;
    }

    if(!(parts = target.match(/^(.+)\[([a-zA-Z0-9]+)\]$/))) {
        alert('dont know what to do!');
        return false;
    }

    if(!Array.isArray(parts) || parts.length != 3) {
        alert('invalid stuff found!');
        return false;
    }

    key = parts[1];
    id = parts[2];

    if(!(input_field = $('input[name="' + target + '"]'))) {
        alert('unable to find input field: '+ target);
        return false;
    }

    if(!(value = input_field.val())) {
        alert('unable to find value in input field: '+ target);
        return false;
    }
    value = safe_string(value);

    if(!(action = input_field.attr('data-action'))) {
        alert('unable to find "data-action" in input field: '+ target);
        return false;
    }
    action = safe_string(action);

    if(
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
            type : 'rpc',
            action : action,
            id     : id,
            key    : key,
            value  : value
        }),
        beforeSend: function() {
            // change row color to red
            //element.parent().parent().animate({backgroundColor: '#fbc7c7' }, 'fast');
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(data){
            if(data == 'ok') {
                if(action == 'add') {
                    location.reload();
                    return;
                } else if(action == 'update') {
                    $('#'+ type + '_label_' + id).html(value.replace(/\\/mg, ''));
                    $('#' + type + '_show_' + id).toggle();
                    $('#' + type + '_edit_' + id).toggle();
                }
                return;
            }
            alert('Server returned: ' + data + ', length ' + data.length);
            return;
        }
    });

    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
