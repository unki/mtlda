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
        mbus = new ThalliumMessageBus;
    } catch (e) {
        throw 'Failed to load ThalliumMessageBus! '+ e;
        return false;
    }

    /* RPC handlers */
    $("a.delete.item").click(function () {
        delete_object($(this));
    });
    $("a.archive.item").click(function () {
        archive_object($(this));
    });
    $("a.split.item").click(function () {
        split_object($(this));
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
    init_dropdowns();
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

function show_modal(type, settings, id, do_function, modalclass)
{
    if (!type || type === undefined) {
        throw 'show_modal(), mandatory type parameter is missing!';
        return false;
    }

    if (type == 'progress') {
        var wnd = $('#progress_template').clone();
    } else if (type == 'confirm') {
        var wnd = $('#confirm_template').clone();
    } else {
        throw 'show_modal(), unsupported type!';
        return false;
    }

    if (!wnd || wnd === undefined) {
        throw 'show_modal(), unable to clone progress_template!';
        return false;
    }

    wnd.removeAttr('id');

    if (id && id !== undefined) {
        wnd.attr('id', id);
    }

    if (settings === undefined) {
        settings = {};
    }

    if (settings.header !== undefined) {
        wnd.find('.header').html(settings.header);
    }

    if (settings.icon !== undefined) {
        wnd.find('.image.content i.icon').removeClass().addClass(settings.icon);
    } else {
        settings.icon = 'icon';
    }

    if (settings.iconHtml !== undefined) {
        wnd.find('.image.content i.' + settings.icon).html(settings.iconHtml);
    } else {
        wnd.find('.image.content i.' + settings.icon).html('');
    }

    if (settings.content !== undefined) {
        wnd.find('.image.content .description p').html(settings.content);
    }

    if (settings.closeable === undefined) {
        settings.closeable = true;
    }

    if (!settings.closeable !== undefined) {
        wnd.find('i.close.icon').detach();
    } else {
        wnd.find('i.close.icon').appendTo(wnd);
    }

    if (settings.hasActions === undefined) {
        settings.hasActions = true;
    }

    if (settings.blurring === undefined) {
        settings.blurring = true;
    }

    if (settings.hasActions === undefined) {
        wnd.find('.actions').detach();
    } else {
        wnd.find('.actions').appendTo(wnd);
    }

    if (settings.onDeny === undefined) {
        settings.onDeny = function () {
            return true;
        };
    }

    if (settings.onApprove === undefined) {
        settings.onApprove = function () {
            $(this).modal('hide');
            return true;
        };
    }

    if (settings.onHidden === undefined) {
        settings.onHidden = function () {
            return true;
        };
    }

    if (settings.detachable === undefined) {
        settings.detachable = true;
    }

    if (settings.observeChanges === undefined) {
        settings.observeChanges = false;
    }

    if (settings.allowMultiple === undefined) {
        settings.allowMultiple = false;
    }

    if (do_function === undefined) {
        do_function = function () {
            return true;
        };
    }

    var modal = wnd
        .modal({
            closable   : settings.closeable,
            onDeny     : settings.onDeny,
            onApprove  : settings.onApprove,
            onHidden   : settings.onHidden,
            blurring   : settings.blurring,
            detachable : settings.detachable,
            observeChanges : settings.observeChanges,
            allowMultiple : settings.allowMultiple,
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

    if (id === undefined || id == "") {
        alert('no attribute "data-id" found!');
        return;
    }

    id = safe_string(id);

    if (id == 'selected') {
        id = new Array;
        $('.checkbox.item.select[id!="select_all"]').each(function () {
            if (!($(this).checkbox('is checked'))) {
                return true;
            }
            item = $(this).attr('id')
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

    var title = element.attr("data-modal-title");

    if (title === undefined || title === "") {
        throw 'No attribute "data-modal-title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    if (text === undefined || text === "") {
        if (id instanceof String && !id.match(/-all$/)) {
            text = "Do you really want to delete this item?";
        } else {
            text = "Do you really want to delete all items?";
        }
    }

    var elements = new Array;
    if (id instanceof Array) {
        id.forEach(function (value) {
            elements.push($('#delete_link_'+value));
        });
    } else {
        elements.push(element);
    }

    var del_wnd = show_modal('confirm', {
        header : title,
        icon : 'red remove icon',
        content : text,
        onDeny : function () {
            return true;
        },
        onApprove : function () {
            $(this).modal('hide');
            return rpc_object_delete(elements, function () {
                if (elements === undefined) {
                    return true;
                }
                if (id !== undefined && id == 'all') {
                    $('table#datatable tbody tr').each(function () {
                        $(this).hide(400, function () {
                            $(value).remove();
                        });
                    });
                    return true;
                }
                elements.forEach(function (value) {
                    $(value).closest('tr').hide(400, function () {
                        $(value).remove();
                    });
                });
                return true;
            });
        },
    });

    return true;
}

function archive_object(element)
{
    var id = element.attr("data-id");

    if (id === undefined || id == "") {
        alert('no attribute "data-id" found!');
        return;
    }

    id = safe_string(id);

    if (id == 'all') {
        $('td.archive.state').text('processing');
    } else if (id == 'selected') {
        id = new Array;
        $('.checkbox.item.select[id!="select_all"]').each(function () {
            if (!($(this).checkbox('is checked'))) {
                return true;
            }
            item = $(this).attr('id')
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
        if (id.length < 1) {
            return true;
        }
        id.forEach(function (value) {
            $('#archive-state-'+value).text('processing');
        });
    } else {
        var state = $("#archive-state-"+id);
        if (state && state.text() == 'new') {
            state.text('processing');
        }
    }

    var title = element.attr("data-modal-title");

    if (title === undefined || title === "") {
        console.log(element);
        throw 'No attribute "data-modal-title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    if (text === undefined || text === "") {
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

    if (id == 'all' ||
        elements.length > 1 ||
        (elements.length == 1 && !element.hasClass('advanced'))
    ) {
        show_modal('confirm', {
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
                $(this).modal('hide');
                return rpc_object_archive(elements, function () {
                    if (elements === undefined) {
                        return true;
                    }
                    if (id !== undefined && id == 'all') {
                        $('table#datatable tbody tr').each(function () {
                            $(this).hide(400, function () {
                                $(value).remove();
                            });
                        });
                        return true;
                    }
                    elements.forEach(function (value) {
                        $(value).closest('tr').hide(400, function () {
                            $(value).remove();
                        });
                    });
                    return true;
                });
            }
        });
        return true;
    }

    archiver_wnd = $(".ui.fullscreen.modal.queue.archiver");

    if (archiver_wnd === undefined || archiver_wnd.length < 1) {
        throw "failed to locate .ui.fullscreen.modal.queue.archiver!";
        return false;
    }

    archiver_wnd.modal({
        closable       : true,
        blurring       : false,
        title          : title,
        observeChanges : true,
        onShow         : archiver_window(elements[0]),
        allowMultiple  : true,
        onHidden       : function () {
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
        }
    })
        .modal('show');
        //.on('click.modal', do_function);

    return archiver_wnd;
}

function trigger_import_run()
{
    import_wnd = show_modal('progress', {
        header : 'Check Incoming Directory',
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
    msg.setCommand('import-request');

    if (!mbus.add(msg)) {
        throw 'ThalliumMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('import-replies-handler', 'import-reply', function (reply) {
        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!import_wnd) {
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
        import_wnd.modal('refresh');

        if (reply.value != '100%') {
            return true;
        }

        progressbar.removeClass('active').addClass('success');

        setTimeout(function () {
            import_wnd.modal('hide');
            mbus.unsubscribe('import-replies-handler');
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
        on: 'click',
        onVisible: function () {
            $('input[name="filter_value"]').focus();
        }
    });
    $('input[name="filter_value"]').on('input', function () {
        filter_value = $(this).val();
        try {
            regexp = new RegExp(filter_value, 'i');
        } catch (e) {
            regexp = safe_string(filter_value);
        }
        $('table#datatable .filterable').each(function () {
            $(this).closest('tr').removeClass('filter matches');
        });
        $('table#datatable .filterable').each(function () {
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
    $('input[name="filter_value"]').keypress(function (e) {
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if (keycode != '13') {
            return;
        }
        $('#filterbutton').popup('toggle');
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

function archiver_window(element, step)
{
    if (archiver_wnd === undefined) {
        throw "somehow we lost our modal window!"
        return false;
    }

    if (element === undefined || ! element instanceof Array) {
        throw "element parameter is invalid!"
        return false;
    }

    if ((id = element.attr('data-id')) === undefined) {
        throw 'no "data-id" attribute found!';
        return false;
    }

    if ((guid = element.attr('data-guid')) === undefined) {
        throw 'no "data-guid" attribute found!';
        return false;
    }

    if ((model = element.attr('data-model')) === undefined) {
        throw 'no "data-model" attribute found!';
        return false;
    }

    if ((title = element.attr('data-modal-title')) === undefined) {
        throw 'no "data-modal-title" attribute found!';
        return false;
    }

    if (step === undefined || !(/^[0-9]+$/).test(step)) {
        step = 1;
    }

    $('.ui.steps .active.step')
        .removeClass('active');
    $('.ui.steps #archiver_step_'+step)
        .removeClass('disabled')
        .addClass('active');

    request_data = {
        content : 'archiver',
        id      : id,
        guid    : guid,
        model   : model,
        step    : step
    };

    $.when(rpc_get_content('queue', request_data)).done(function (data) {
        $('#archiver_content').html(data);
        eval($('.archiver.modal .header.window.title').html(title));
        eval($('.archiver.modal .ui.steps .step').attr('data-modal-title', title));
        eval($('.archiver.modal .ui.steps .step').attr('data-id', id));
        eval($('.archiver.modal .ui.steps .step').attr('data-guid', guid));
        return true;
    });
}

function load_datepickers(mode)
{
    if (mode === undefined || mode == "" || (mode != "document" && mode != "queue")) {
        throw 'mode parameter is invalid!'
        return false;
    }

    var current_custom_date = $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').val();
    if (!current_custom_date || current_custom_date == '0000-00-00') {
        current_custom_date = null;
    }

    var current_expiry_date = $('#'+ mode +'_expiry_date_form input[type="text"][name="'+ mode +'_expiry_date"]').val();
    if (!current_expiry_date || current_expiry_date == '0000-00-00') {
        current_expiry_date = null;
    }

    $('#'+ mode +'_custom_date').datepicker({
        defaultDate: current_custom_date,
        changeMonth: true,
        changeYear: true,
        numberOfMonths: 1,
        dateFormat: 'yy-mm-dd',
        showOtherMonths: true,
        showWeek: true,
        selectOtherMonths: true,
        showButtonPanel: true,
        firstDay: 1,
        altFormat: 'yy-mm-dd',
        altField: 'input[type="text"][name="'+ mode +'_custom_date"]',
        onSelect: function () {
            curval = $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').val();
            newval = $(this).datepicker('getDate');
            if (curval && newval && curval == newval) {
                return true;
            }
            $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').trigger('input');
        }
    });

    $('#'+ mode +'_expiry_date').datepicker({
        defaultDate: current_expiry_date,
        changeMonth: true,
        changeYear: true,
        numberOfMonths: 1,
        dateFormat: 'yy-mm-dd',
        showOtherMonths: true,
        showWeek: true,
        selectOtherMonths: true,
        showButtonPanel: true,
        firstDay: 1,
        altFormat: 'yy-mm-dd',
        altField: 'input[type="text"][name="'+ mode +'_expiry_date"]',
        onSelect: function () {
            curval = $('#'+ mode +'_expiry_date_form input[type="text"][name="'+ mode +'_expiry_date"]').val();
            newval = $(this).datepicker('getDate');
            if (curval && newval && curval == newval) {
                return true;
            }
            $('#'+ mode +'_expiry_date_form input[type="text"][name="'+ mode +'_expiry_date"]').trigger('input');
        }
    });

    $('.ui.toggle.checkbox[name="'+ mode +'_custom_date_checkbox"]').checkbox({
        onChange : function () {
            if ($('.ui.toggle.checkbox[name="'+ mode +'_custom_date_checkbox"]').checkbox('is unchecked')) {
                $('#'+ mode +'_custom_date_form').transition('fly up');
                $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').val('0000-00-00');
                $('#'+ mode +'_custom_date_form').trigger('submit');
                return true;
            }
            var current_custom_date = $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').val();
            if (!current_custom_date || current_custom_date == '' || current_custom_date == '0000-00-00') {
                $('#'+ mode +'_custom_date').datepicker('setDate', new Date());
            }
            $('#'+ mode +'_custom_date_form input').trigger('input');
            $('#'+ mode +'_custom_date_form').transition('fly down');
            return true;
        }
    });

    $('.ui.toggle.checkbox[name="'+ mode +'_expiry_date_checkbox"]').checkbox({
        onChange : function () {
            if ($('.ui.toggle.checkbox[name="'+ mode +'_expiry_date_checkbox"]').checkbox('is unchecked')) {
                $('#'+ mode +'_expiry_date_form').transition('fly up');
                $('#'+ mode +'_expiry_date_form input[type="text"][name="'+ mode +'_expiry_date"]').val('0000-00-00');
                $('#'+ mode +'_expiry_date_form').trigger('submit');
                return true;
            }
            var current_expiry_date = $('#'+ mode +'_expiry_date_form input[type="text"][name="'+ mode +'_expiry_date"]').val();
            if (!current_expiry_date || current_expiry_date == '' || current_expiry_date == '0000-00-00') {
                $('#'+ mode +'_expiry_date').datepicker('setDate', new Date());
            }
            $('#'+ mode +'_expiry_date_form input').trigger('input');
            $('#'+ mode +'_expiry_date_form').transition('fly down');
            return true;
        }
    });

    $('#'+ mode +'_custom_date_form input').on('input', function () {
        savebutton = $('#'+ mode +'_custom_date_form button.save');
        if (!savebutton.hasClass('red shape')) {
            savebutton.addClass('red shape');
            savebutton.transition('bounce');
        }
    });

    $('#'+ mode +'_expiry_date_form input').on('input', function () {
        savebutton = $('#'+ mode +'_expiry_date_form button.save');
        if (!savebutton.hasClass('red shape')) {
            savebutton.addClass('red shape');
            savebutton.transition('bounce');
        }
    });

    $('#'+ mode +'_custom_date_form').on('submit', function () {
        rpc_object_update($(this), function (data) {
            if (data != "ok") {
                return true;
            }
            $('#'+ mode +'_custom_date_form button.save')
                .transition('tada')
                .removeClass('red shape');
            return true;
        });
        return false;
    });

    $('#'+ mode +'_expiry_date_form').on('submit', function () {
        rpc_object_update($(this), function (data) {
            if (data != "ok") {
                return true;
            }
            $('#'+ mode +'_expiry_date_form button.save')
                .transition('tada')
                .removeClass('red shape');
            return true;
        });
        return false;
    });

    $('form.ui.form.keywords').on('submit', function () {
        rpc_object_update($(this));
        return false;
    });
}

function init_dropdowns()
{
    $('#keyword_dropdown').dropdown({
        allowAdditions: false,
        onChange : function (value, text, choice) {
            $('.ui.form.keywords button.save')
                .addClass('red shape')
                .transition('bounce');
        }
    });
}

function split_object(element)
{
    var title = element.attr("data-modal-title");

    if (title === undefined || title === "") {
        throw 'No attribute "data-modal-title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    splitter_wnd = $(".ui.fullscreen.modal.queue.splitter");

    if (splitter_wnd === undefined || splitter_wnd.length < 1) {
        throw "failed to locate .ui.fullscreen.modal.queue.splitter!";
        return false;
    }

    splitter_wnd.modal({
        closable       : true,
        blurring       : false,
        title          : title,
        observeChanges : true,
        onShow         : splitter_window($(element))
    })
        .modal('show');
        //.on('click.modal', do_function);

    return splitter_wnd;
}

function splitter_window(element, step)
{
    if (splitter_wnd === undefined) {
        throw "somehow we lost our modal window!"
        return false;
    }

    if (element === undefined || ! element instanceof Array) {
        throw "element parameter is invalid!"
        return false;
    }

    if ((id = element.attr('data-id')) === undefined) {
        throw 'no "data-id" attribute found!';
        return false;
    }

    if ((guid = element.attr('data-guid')) === undefined) {
        throw 'no "data-guid" attribute found!';
        return false;
    }

    if ((model = element.attr('data-model')) === undefined) {
        throw 'no "data-model" attribute found!';
        return false;
    }

    if ((title = element.attr('data-modal-title')) === undefined) {
        throw 'no "data-modal-title" attribute found!';
        return false;
    }

    if (step === undefined || !(/^[0-9]+$/).test(step)) {
        step = 1;
    }

    $('.ui.steps .active.step')
        .removeClass('active');
    $('.ui.steps #splitter_step_'+step)
        .removeClass('disabled')
        .addClass('active');

    request_data = {
        content : 'splitter',
        id      : id,
        guid    : guid,
        model   : model,
        step    : step
    };

    $.when(rpc_get_content('queue', request_data)).done(function (data) {
        $('#splitter_content').html(data);
        eval($('.splitter.modal .header.window.title').html(title));
        eval($('.splitter.modal .ui.steps .step').attr('data-modal-title', title));
        eval($('.splitter.modal .ui.steps .step').attr('data-id', id));
        eval($('.splitter.modal .ui.steps .step').attr('data-guid', guid));
        return true;
    });
}

function isInteger(x)
{
    return x % 1 === 0;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
