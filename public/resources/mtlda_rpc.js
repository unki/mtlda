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
    if (!(element instanceof jQuery) ){
        throw "element is not a jQuery object!";
        return false;
    }

    var target = element.attr('data-target');

    if(target == undefined || target == '') {
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
            type   : 'rpc',
            action : action,
            model  : model,
            id     : id,
            key    : key,
            value  : value
        }),
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function (data) {
            if(data != 'ok') {
                alert('Server returned: ' + data + ', length ' + data.length);
                return;
            }
            if(action == 'add') {
                location.reload();
                return;
            }
            return;
        }
    });

    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
