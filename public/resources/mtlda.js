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

function show_preview(element)
{
    var obj_id = element.attr("id");

    if(obj_id == undefined || obj_id == "") {
        alert('no attribute "id" found!');
        return;
    }

    open_preview_dialog(obj_id);
}

function open_preview_dialog(obj_id)
{
    $.ajax({
        type: "POST",
        url: "rpc.html",
        data: ({
            type      : 'rpc',
            action    : 'get-content',
            content   : 'preview',
            model     : 'queueitem',
            id        : obj_id
        }),
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(data) {
            $('#dialog').html(data);
            $('#dialog').dialog({
                title:      'Preview',
                modal:      true,
                autoOpen:   false,
                draggable:  false,
                resizeable: false,
                width:      900,
                height:     450,
                position:   {
                    my:      "center top+5%",
                    at:      "center top+5%",
                    of:      "#content"
                },
                buttons:    {
                    Ok: function() {
                        $(this).dialog("close");
                    }
                },
                open: function() {
                    var previewimg = $("#dialog img[name=previewimg]").attr("load");
                    if(previewimg == undefined || previewimg == '') {
                        alert('found no image to load:' + previewimg);
                        return;
                    }
                    var preview = new Image;
                    preview.src = previewimg;
                    if(preview.complete) {
                        $("#dialog img[name=previewimg]").attr('src', previewimg);
                        preview.onload=function(){};
                    } else{
                        preview.onload = function() {
                            $("#dialog img[name=previewimg]").attr('src', this.src);
                            //    clear onLoad, IE behaves irratically with animated gifs otherwise
                            preview.onload=function(){};
                        }
                    }
                }
            });
            if(!$('#dialog').dialog('isOpen')) {
                $('#dialog').dialog('open');
            }
        }
    });
}

$(document).ready(function() {
   $("table tr td a.delete").click(function(){
      rpc_object_delete($(this));
   });
   $("table tr td a.archive").click(function(){
      rpc_object_archive($(this));
   });
   $("table tr td a.preview").click(function(){
      show_preview($(this));
   });
   /*$("table td a.clone").click(function(){
      obj_clone($(this));
   });
   $("table td div a.toggle-off, table td div a.toggle-on").click(function(){
      obj_toggle_status($(this));
   });
   */
   $('img.change_to').hover(
      function() {
         $(this).css('cursor','pointer');
      },
      function() {
         $(this).css('cursor','auto');
      }
   );
   //load_menu();
});

function change_preview(direction)
{
    var dialog = $('#dialog');

    if(!dialog.dialog('isOpen')) {
        return false;
    }

    var img = $('#dialog img[name=previewimg]');

    if(img == undefined || img == "") {
        return false;
    }

    var imgid = img.attr('imgid');

    if(!imgid) {
        return false;
    }

    $.ajax({
        type: "POST",
        url: "rpc.html",
        data: ({
            type      : 'rpc',
            action    : 'find-prev-next',
            model     : 'queueitem',
            id        : imgid,
            direction : direction
        }),
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(obj_id) {
            open_preview_dialog(obj_id);
            var previewimg = $("#dialog img[name=previewimg]").attr("load");
            if(previewimg == undefined || previewimg == '') {
                alert('found no image to load:' + previewimg);
                return;
            }
            var preview = new Image;
            preview.src = previewimg;
            if(preview.complete) {
                $("#dialog img[name=previewimg]").attr('src', previewimg);
                preview.onload=function(){};
            } else{
                preview.onload = function() {
                    $("#dialog img[name=previewimg]").attr('src', this.src);
                    //    clear onLoad, IE behaves irratically with animated gifs otherwise
                    preview.onload=function(){};
                }
            }
        }
    });

    return true;
}

Dropzone.options.upload = {
    paramName: 'mtlda_upload',
    addRemoveLinks: true,
    acceptedFiles: 'application/pdf,.pdf',
    uploadMultiple: true,
    dictDefaultMessage: 'drag\'n\'drop files here<br />or click to select',
    autoProcessQueue: false,
    init: function() {
        var dropzone = this;
        $('#submitbtn').click(function() {
            dropzone.processQueue();
        });
    },
    processing: function(file) {
        $('#progress').show();
    },
    queuecomplete: function(file) {
        $('#progress').html('');
        $('#progress').hide();
    },
    error: function(file, errorMessage) {
        if (errorMessage == '') {
            window.alert('An unknown error occured!');
            return;
        }
        window.alert('Server reported: ' + errorMessage);
    },
    success: function(file, successMessage) {
        if (successMessage == 'ok') {
            $('#transferlist').append(file.name +'<br />');
            this.removeFile(file);
            return;
        } else if (successMessage == '') {
            window.alert('An unknown error occured!');
            return;
        }
        window.alert(successMessage);
    },
    totaluploadprogress: function(progress, transfered) {
        $('#progress').html('Progress:&nbsp;'+Math.round(progress) + '% ' + Math.round(transfered/1024) + ' bytes');
    },
};

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
