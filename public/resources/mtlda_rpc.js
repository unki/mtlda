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

function rpc_object_delete(element, target, idx)
{
    var del_id = element.attr("id");

    if(del_id == undefined || del_id == "") {
        alert('no attribute "id" found!');
        return;
    }

    if(!confirm("Are you sure you want to delete this object? There is NO undo!")) {
        return false;
    }

    $.ajax({
        type: "POST",
        url: "rpc.html",
        data: ({
            type : 'rpc',
            action : 'delete',
            id : del_id
        }),
        beforeSend: function() {
            // change row color to red
            element.parent().parent().animate({backgroundColor: "#fbc7c7" }, "fast");
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(data){
            if(data == "ok") {
                element.parent().parent().animate({ opacity: "hide" }, "fast");
                return;
            }
            // change row color back to white
            element.parent().parent().animate({backgroundColor: "#ffffff" }, "fast");
            alert('Server returned: ' + data + ', length ' + data.length);
            return;
        }
    });

    return true;

} // rpc_object_delete()

function rpc_object_archive(element, target, idx)
{
    var obj_id = element.attr("id");

    if(obj_id == undefined || obj_id == "") {
        alert('no attribute "id" found!');
        return;
    }

    obj_id = obj_id.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\\\$&");

    var state = $("#"+obj_id+".state");
    if(!state) {
        alert('no state field for '+ id +' found');
        return;
    }

    if(state.text() == 'new') {
        state.text('Processing');
    }

    $.ajax({
        type: "POST",
        url: "rpc.html",
        data: ({
            type : 'rpc',
            action : 'archive',
            id : obj_id
        }),
        beforeSend: function() {
            // change row color to red
            element.parent().parent().animate({backgroundColor: "#fbc7c7" }, "fast");
            state.text('Processing');
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
            state.text('Failure');
        },
        success: function(data){
            if(data == "ok") {
                state.text('Done');
                element.parent().parent().animate({ opacity: "hide" }, "fast");
                return;
            }
            // change row color back to white
            element.parent().parent().animate({backgroundColor: "#ffffff" }, "fast");
            alert('Server returned: ' + data + ', length ' + data.length);
            return;
        }
    });

    return true;

} // rpc_archive_object()

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
