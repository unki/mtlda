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

    init_checkbox_selector();
    init_table_sort();
    init_table_filter();
});

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
    var id = element.attr("data-id");

    if (id == undefined || id == "") {
        alert('no attribute "data-id" found!');
        return;
    }

    id = safe_string(id);

    if (id == 'selected') {
        id = new Array;
        $('.checkbox.item.select[name!="select_all"]').each(function () {
            if (!($(this).checkbox('is checked'))) {
                return true;
            }
            item = $(this).attr('name')
            if (!item || item == '') {
                return false;
            }
            item = item.match(/^select_(\d+)$/);
            if (!item || !item[1] || item[1] == '') {
                return false;
            }
            item_id = item[1];
            id.push(item_id);
        });
        if (id.length == 0) {
            return true;
        }
    }

    var title = element.attr("title");

    if (title == undefined || title === "") {
        throw 'No attribute "title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    if (text == undefined || text === "") {
        if (id instanceof String && !id.match(/-all$/)) {
            text = "Do you really want to archive this item?";
        } else {
            text = "Do you really want to archive all items?";

        }
    }

    elements = new Array;
    if (id instanceof Array) {
        id.forEach(function (value) {
            elements.push($('#delete_link_'+value));
        });
    } else {
        elements.push(element);
    }

    show_modal({
        closeable : false,
        header : title,
        icon : 'red remove icon',
        content : text,
        onDeny : function () {
            return true;
        },
        onApprove : function () {
            return rpc_object_delete(elements);
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

    if (id == 'all') {
        $('td.archive.state').text('processing');
    } else if (id == 'selected') {
        id = new Array;
        $('.checkbox.item.select[name!="select_all"]').each(function () {
            if (!($(this).checkbox('is checked'))) {
                return true;
            }
            item = $(this).attr('name')
            if (!item || item == '') {
                return false;
            }
            item = item.match(/^select_(\d+)$/);
            if (!item || !item[1] || item[1] == '') {
                return false;
            }
            item_id = item[1];
            id.push(item_id);
        });
        id.forEach(function (value) {
            $('#archive-state-'+value).text('processing');
        });
    } else {
        var state = $("#archive-state-"+id);
        if (state && state.text() == 'new') {
            state.text('processing');
        }
    }

    var title = element.attr("title");

    if (title == undefined || title === "") {
        throw 'No attribute "title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    if (text == undefined || text === "") {
        if (id instanceof String && !id.match(/-all$/)) {
            text = "Do you really want to archive this item?";
        } else {
            text = "Do you really want to archive all items?";

        }
    }

    elements = new Array;
    if (id instanceof Array) {
        id.forEach(function (value) {
            elements.push($('#archive_link_'+value));
        });
    } else {
        elements.push(element);
    }

    // for all objects
    show_modal({
        closeable : false,
        header : title,
        content : text,
        icon : 'red archive icon',
        onDeny : function () {
            if (id != 'all') {
                if (id instanceof Array) {
                    id.forEach(function (value) {
                        $('#archive-state-'+value).text('new');
                    });
                } else {
                    state.text('new');
                }
            } else {
                $('td.archive.state').text('new');
            }
            return true;
        },
        onApprove : function () {
            return rpc_object_archive(elements);
        }
    });

    return true;
}

function trigger_import_run()
{
    wnd = show_modal({
        blurring : true,
        closeable : false,
        header : 'Check Incoming Directory',
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

    var msg = new MtldaMessage;
    msg.setCommand('import-request');

    if (!mbus.add(msg)) {
        throw 'MtldaMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('import-replies-handler', 'import-reply', function (reply) {
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
            mbus.unsubscribe('import-replies-handler');
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

function init_table_sort()
{
    $('#datatable').tablesort();
}

function init_table_filter()
{
    $('#filterbutton').popup({
        popup: '#filterpopup',
        exclusive: true,
        preserve: true,
        on: 'click'
    });
    $('input[name="filter_value"]').on('input', function () {
        filter_value = $(this).val();
        try {
            regexp = new RegExp(filter_value, 'i');
        } catch (e) {
            regexp = safe_string(filter_value);
        }
        $('.filterable').each(function () {
            $(this).closest('tr').removeClass('filter matches');
        });
        $('.filterable').each(function () {
            content = $(this).html();
            if (filter_value === undefined ||
                filter_value == "" ||
                content.match(regexp)
            ) {
                $(this).closest('tr')
                    .show()
                    .addClass('filter matches');
                return;
            }
            if ($(this).closest('tr').hasClass('filter matches')) {
                return;
            }
            $(this).closest('tr').hide();
            return;
        });
    });
    $('a.filter.reset').click(function () {
        $('input[name="filter_value"]').val('').trigger('input');
    });
}

function init_checkbox_selector()
{
    $(".item.select.checkbox").checkbox().click(function () {
        if (!(id = $(this).attr('id'))) {
            throw 'Failed to locate id-attribute!';
            return false;
        }
        if (id != 'select_all') {
            return true;
        }

        state = $(".item.select.checkbox#select_all").checkbox("is checked");

        if (state != true && state != false) {
            throw 'Checkbox returned an invalid state!';
            return false;
        }

        if (state === true) {
            to_state = "set checked";
        } else {
            to_state = "set unchecked";
        }

        $(".item.select.checkbox[id!='select_all']").checkbox(to_state);
        return true;
    });
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
