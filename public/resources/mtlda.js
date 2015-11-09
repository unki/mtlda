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

    if (obj_id == undefined || obj_id == "") {
        alert('no attribute "id" found!');
        return;
    }

    open_preview_window(obj_id);
}

function open_preview_window(obj_id)
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
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function (data) {
            if (!data) {
                window.alert("no data received from server!");
                return false;
            }
            if (document.getElementsByClassName('ui modal').length) {
                $('.ui.modal').replaceWith(data);
            } else {
                $('body').append(data);
            }
            $(".ui.modal .ui.loader").removeClass('disabled');
            $('.ui.modal').modal('setting', {
                observeChanges : true
            }).modal('show');

            var preview_src = $(".ui.modal img[name=preview_image]").attr("data-src");
            if (preview_src == undefined || preview_src == '') {
                alert('found no image source to load:' + preview_src);
                return;
            }
            var preview = new Image;
            preview.src = preview_src;
            if (preview.complete) {
                $(".ui.modal img[name=preview_image]").attr('src', preview_src);
                preview.onload = function () {
                    $(".ui.modal .ui.loader").addClass('disabled');
                };
            } else {
                preview.onload = function () {
                    $(".ui.modal img[name=preview_image]").attr('src', this.src);
                    $(".ui.modal .ui.loader").addClass('disabled');
                    $(".ui.segment .ui.active.dimmer").removeClass('active').addClass('disabled');
                    $('.ui.modal').modal('show');
                    // for IE
                    preview.onload=function () {};
                };
            }
            return true;
        }
    });
}

$(document).ready(function () {

    try {
        mbus = new MtldaMessageBus;
    } catch (e) {
        throw 'Failed to load MtldaMessageBus! '+ e;
        return false;
    }

    /* RPC handlers */
    $("a.delete.item").click(function () {
        delete_object($(this));
    });
    $("a.archive.item").click(function () {
        archive_object($(this));
    });
    $("table tr td a.preview").click(function () {
        show_preview($(this));
    });
    $("form.ui.form.add").on('submit', function () {
        rpc_object_update($(this));
    });
    $("a.sign.document").click(function () {
        rpc_object_sign($(this));
    });
    $("a.scan.document").click(function () {
        rpc_object_scan($(this));
    });
    $('.inline.editable.edit.link').click(function () {
        inlineobj = new MtldaInlineEditable($(this));
        inlineobj.toggle();
    });
   /*$("table td a.clone").click(function () {
      obj_clone($(this));
   });
   $("table td div a.toggle-off, table td div a.toggle-on").click(function () {
      obj_toggle_status($(this));
   });
   */
    $('img.change_to').hover(
        function () {
            $(this).css('cursor','pointer');
        },
        function () {
            $(this).css('cursor','auto');
        }
    );
});

function change_preview(direction)
{
    var img = $('.ui.modal img[name=previewimg]');

    if (img == undefined || img == "") {
        return false;
    }

    var imgid = img.attr('data-image-id');

    if (!imgid) {
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
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function (obj_id) {

            /*var previewimg = $(".ui.modal img[name=previewimg]").attr("load");
            if (previewimg == undefined || previewimg == '') {
                alert('found no image to load:' + previewimg);
                return;
            }
            var preview = new Image;
            preview.src = previewimg;
            if (preview.complete) {
                $("#dialog img[name=previewimg]").attr('src', previewimg);
                preview.onload=function () {};
            } else{
                preview.onload = function () {
                    $("#dialog img[name=previewimg]").attr('src', this.src);
                    //    clear onLoad, IE behaves irratically with animated gifs otherwise
                    preview.onload=function () {};
                }
            }*/
        }
    });

    return true;
}

function init_upload_progressbar(dropzone)
{
    $('#uploadprogress').progress({
        autoSuccess: true,
        label: 'ratio',
        text: {
            label  : '{total} files queued for upload',
            ratio  : 'File {value} of {total}'
        },
    });
}

function init_dropzone()
{
    Dropzone.options.upload = {
        paramName: 'mtlda_upload',
        addRemoveLinks: true,
        acceptedFiles: 'application/pdf,.pdf',
        uploadMultiple: true,
        dictDefaultMessage: 'drag\'n\'drop files here<br />or<br />click to select',
        autoProcessQueue: true,
        createImageThumbnails: false,
        previewTemplate: document.querySelector('#dropzone-preview-template').innerHTML,
        init: function () {
            var dropzone = this;
            $('#uploadbtn').click(function () {
                dropzone.processQueue();
            });
            init_upload_progressbar(dropzone)
            this.on('addedfile', function () {
                nofiles = this.getQueuedFiles().length;
                $('#uploadprogress').progress('reset');
                $('#uploadprogress').progress({
                    total: nofiles+1
                });
            });
            this.on('removedfile', function (file) {
                nofiles = this.getQueuedFiles().length;
                $('#uploadprogress').progress('reset');
                $('#uploadprogress').progress({
                    total: nofiles+1
                });
            });
            this.on('canceled', function (file) {
                nofiles = this.getQueuedFiles().length;
                $('#uploadprogress').progress('reset');
                $('#uploadprogress').progress({
                    total: nofiles+1
                });
            });
            this.on('complete', function (file) {
                $('#uploadprogress').progress('increment');
            });
            this.on('queuecomplete', function (file) {
                $('#uploadprogress').progress('complete');
            });
            this.on('error', function (file, errorMessage) {
                if (errorMessage == '') {
                    window.alert('An unknown error occured!');
                    return;
                }
                window.alert('Server reported: ' + errorMessage);
                this.removeFile(file);
            });
            this.on('success', function (file, successMessage) {
                if (successMessage == 'ok') {
                    $('#transferlist').append(file.name +'<br />');
                    this.removeFile(file);
                    return;
                } else if (successMessage == '') {
                    window.alert('An unknown error occured!');
                    this.removeFile(file);
                    return;
                }
                window.alert(successMessage);
                this.removeFile(file);
            });
        }
    };
}

function show_modal(settings, do_function, modalclass)
{
    if (!modalclass) {
        modalclass = '.ui.basic.modal';
    }

    var modal_settings = {};

    if (settings.header) {
        $(modalclass + ' .header').html(settings.header);
    }

    if (settings.icon) {
        $(modalclass + ' .image.content i.icon').removeClass().addClass(settings.icon);
    } else {
        settings.icon = 'icon';
    }

    if (settings.iconHtml) {
        $(modalclass + ' .image.content i.' + settings.icon).html(settings.iconHtml);
    } else {
        $(modalclass + ' .image.content i.' + settings.icon).html('');
    }

    if (settings.content) {
        $(modalclass + ' .image.content .description p').html(settings.content);
    }

    if (settings.closeable == undefined) {
        settings.closeable = true;
    }

    if (!settings.closeable) {
        $(modalclass + ' i.close.icon').detach();
    } else {
        $(modalclass + ' i.close.icon').appendTo('.ui.basic.modal');
    }

    if (settings.hasActions == undefined) {
        settings.hasActions = true;
    }

    if (settings.blurring == undefined) {
        settings.blurring = true;
    }

    if (!settings.hasActions) {
        $(modalclass + ' .actions').detach();
    } else {
        $(modalclass + ' .actions').appendTo('.ui.basic.modal');
    }

    if (!settings.onDeny) {
        settings.onDeny = function () {
            return true;
        };
    }

    if (!settings.onApprove) {
        settings.onApprove = function () {
            return true;
        };
    }

    if (!do_function) {
        do_function = function () {
            return true;
        };
    }

    modal = $(modalclass)
        .modal({
            closable  : settings.closeable,
            onDeny    : settings.onDeny,
            onApprove : settings.onApprove,
            blurring  : settings.blurring
        })
        .modal('show')
        .on('click.modal', do_function);

        return modal;
}

function safe_string(input)
{
    return input.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
}

function delete_object(element)
{
    var del_title = element.attr("title");

    if (del_title == undefined || del_title === "") {
        throw 'No attribute "title" found!';
        return false;
    }

    var del_text = element.attr("data-modal-text");

    if (del_text == undefined || del_text === "") {
        del_text = "Do you really want to delete this item?";
    }

    // for all objects
    show_modal({
        closeable : false,
        header : del_title,
        icon : 'red remove icon',
        content : del_text,
        onDeny : function () {
            return true;
        },
        onApprove : function () {
            return rpc_object_delete(element);
        }
    });

    return true;
}

function archive_object(element)
{
    var id = element.attr("data-id");

    if (id == undefined || id == "") {
        alert('no attribute "data-id" found!');
        return;
    }

    id = safe_string(id);

    if (id != 'all') {
        var state = $("#archive-state-"+id);
        if (state && state.text() == 'new') {
            state.text('processing');
        }
    } else {
        $('td.archive.state').text('processing');
    }

    var title = element.attr("title");

    if (title == undefined || title === "") {
        throw 'No attribute "title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    if (text == undefined || text === "") {
        if (!id.match(/-all$/)) {
            text = "Do you really want to archive this item?";
        } else {
            text = "Do you really want to archive all items?";

        }
    }

    // for all objects
    show_modal({
        closeable : false,
        header : title,
        content : text,
        icon : 'red archive icon',
        onDeny : function () {
            if (id != 'all') {
                state.text('new');
            } else {
                $('td.archive.state').text('new');
            }
            return true;
        },
        onApprove : function () {
            return rpc_object_archive(element);
        }
    });

    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
