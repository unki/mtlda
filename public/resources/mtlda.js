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

var mbus;
var store;

$(document).ready(function () {

    try {
        mbus = new ThalliumMessageBus;
    } catch (e) {
        throw 'Failed to load ThalliumMessageBus! '+ e;
        return false;
    }

    try {
        store = new ThalliumStore;
    } catch (e) {
        throw 'Failed to load ThalliumStore! ' + e;
        return false;
    }

    /* RPC handlers */
    $("form.ui.form.add").on('submit', function () {
        rpc_object_update($(this), function (element, data) {
            if (data != "ok") {
                return true;
            }
            var savebutton = element.find('button.save');
            savebutton.transition('tada').removeClass('red shape');
            return true;
        });
        return false;
    });
    $('.inline.editable.edit.link').click(function () {
        var inlineobj = new ThalliumInlineEditable($(this));
        inlineobj.toggle();
    });
    init_checkbox_selector();
    init_table_sort();
    init_table_filter();
    init_dropdowns();
    init_action_links();
    init_modal_steps();
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

    return true;
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
                var nofiles = this.getQueuedFiles().length;
                $('#uploadprogress').progress('reset');
                $('#uploadprogress').progress({
                    total: nofiles+1
                });
            });
            this.on('removedfile', function (file) {
                var nofiles = this.getQueuedFiles().length;
                $('#uploadprogress').progress('reset');
                $('#uploadprogress').progress({
                    total: nofiles+1
                });
            });
            this.on('canceled', function (file) {
                var nofiles = this.getQueuedFiles().length;
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

    return true;
}

function show_modal(type, settings, id, do_function, modalclass)
{
    if (typeof type === 'undefined' || !type) {
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

    if (typeof wnd === 'undefined' || !wnd) {
        throw 'show_modal(), unable to clone progress_template!';
        return false;
    }

    wnd.removeAttr('id');

    if (typeof id !== 'undefined' && id) {
        wnd.attr('id', id);
    }

    if (typeof settings === 'undefined') {
        var settings = {};
    }

    if (typeof settings.header !== 'undefined') {
        wnd.find('.header').html(settings.header);
    }

    if (typeof settings.icon !== 'undefined') {
        wnd.find('.image.content i.icon').removeClass().addClass(settings.icon);
    } else {
        settings.icon = 'icon';
    }

    if (typeof settings.iconHtml !== 'undefined') {
        wnd.find('.image.content i.' + settings.icon).html(settings.iconHtml);
    } else {
        wnd.find('.image.content i.' + settings.icon).html('');
    }

    if (typeof settings.content !== 'undefined') {
        wnd.find('.image.content .description p').html(settings.content);
    }

    if (typeof settings.closable === 'undefined') {
        settings.closable = true;
    }

    if (typeof settings.closable !== 'undefined' && !settings.closable) {
        wnd.find('i.close.icon').detach();
    } else {
        wnd.find('i.close.icon').appendTo(wnd);
    }

    if (typeof settings.hasActions === 'undefined') {
        settings.hasActions = true;
    }

    if (typeof settings.blurring === 'undefined') {
        settings.blurring = true;
    }

    if (typeof settings.hasActions === 'undefined') {
        wnd.find('.actions').detach();
    } else {
        wnd.find('.actions').appendTo(wnd);
    }

    if (typeof settings.onDeny === 'undefined') {
        settings.onDeny = function () {
            return true;
        };
    }

    if (typeof settings.onApprove === 'undefined') {
        settings.onApprove = function () {
            $(this).modal('hide');
            return true;
        };
    }

    if (typeof settings.onHidden === 'undefined') {
        settings.onHidden = function () {
            return true;
        };
    }

    if (typeof settings.detachable === 'undefined') {
        settings.detachable = true;
    }

    if (typeof settings.observeChanges === 'undefined') {
        settings.observeChanges = false;
    }

    if (typeof settings.allowMultiple === 'undefined') {
        settings.allowMultiple = false;
    }

    if (typeof do_function === 'undefined') {
        var do_function = function () {
            return true;
        };
    }

    var modal = wnd.modal({
        closable   : settings.closable,
        onDeny     : settings.onDeny,
        onApprove  : settings.onApprove,
        onHidden   : settings.onHidden,
        blurring   : settings.blurring,
        detachable : settings.detachable,
        observeChanges : settings.observeChanges,
        allowMultiple : settings.allowMultiple,
    })
    modal.modal('show').on('click.modal', do_function);

    return modal;
}

function safe_string(input)
{
    return input.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
}

function delete_object(element)
{
    var id = element.attr("data-id");

    if (typeof id === 'undefined' || id == "") {
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
            var item = $(this).attr('id')
            if (typeof item === 'undefined' || !item || item == '') {
                return false;
            }
            item = item.match(/^select_(\d+)$/);
            if (typeof item === 'undefined' || !item || !item[1] || item[1] == '') {
                return false;
            }
            var item_id = item[1];
            id.push(item_id);
        });
        if (id.length == 0) {
            return true;
        }
    }

    var title = element.attr("data-modal-title");

    if (typeof title === 'undefined' || title === "") {
        throw 'No attribute "data-modal-title" found!';
        return false;
    }

    var text = element.attr("data-modal-text");

    if (typeof text === 'undefined' || text === "") {
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

    show_modal('confirm', {
        header : title,
        icon : 'red remove icon',
        content : text,
        onDeny : function () {
            return true;
        },
        onApprove : function () {
            $(this).modal('hide');
            return rpc_object_delete(elements, function () {
                if (typeof elements === 'undefined') {
                    return true;
                }
                if (typeof id !== 'undefined' && id == 'all') {
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
    var id, guid, model, title, text, substore, archiver_wnd;

    if (typeof (title = element.attr('data-modal-title')) === 'undefined') {
        throw 'no "data-modal-title" attribute found!';
        return false;
    }

    if (typeof (text = element.attr("data-modal-text")) === 'undefined') {
        throw 'no "data-modal-text" attribute found!';
        return false;
    }

    if (typeof element === 'undefined' || ! element instanceof Array) {
        throw "element parameter is invalid!"
        return false;
    }

    if (typeof (id = element.attr('data-id')) === 'undefined') {
        throw 'no "data-id" attribute found!';
        return false;
    }

    if (typeof (guid = element.attr('data-guid')) === 'undefined') {
        throw 'no "data-guid" attribute found!';
        return false;
    }

    if (typeof (model = element.attr('data-model')) === 'undefined') {
        throw 'no "data-model" attribute found!';
        return false;
    }

    if (id == 'all' && guid == 'all') {
        $('td.archive.state').text('processing');
    } else if (id == 'selected' && guid == 'selected') {
        id = new Array;
        $('.checkbox.item.select[id!="select_all"]').each(function () {
            if (!($(this).checkbox('is checked'))) {
                return true;
            }
            var item = $(this).attr('id')
            if (typeof item === 'undefined' || !item || item == '') {
                return false;
            }
            item = item.match(/^select_(\d+)$/);
            if (typeof item === 'undefined' || !item || !item[1] || item[1] == '') {
                return false;
            }
            var item_id = item[1];
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

    if (typeof text === 'undefined' || text === "") {
        if (id instanceof String && !id.match(/-all$/)) {
            text = "Do you really want to archive this item?";
        } else {
            text = "Do you really want to archive all items?";
        }
    }

    var elements = new Array;
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
                    if (typeof elements === 'undefined') {
                        return true;
                    }
                    if (typeof id !== 'undefined' && id == 'all') {
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

    var substore = store.createSubStore('archiver_'+guid);
    substore.set('id', id);
    substore.set('guid', guid);
    substore.set('model', model);
    substore.set('title', title);
    archiver_wnd = substore.set('archiver_wnd', $("#archiver_modal_window_template").clone());

    if (typeof archiver_wnd === 'undefined' || archiver_wnd.length < 1) {
        throw "failed to clone #archiver_modal_window_template!";
        return false;
    }

    archiver_wnd.attr('id', 'archiver_modal_window_' + guid);
    archiver_wnd.modal({
        closable       : true,
        blurring       : false,
        title          : title,
        observeChanges : true,
        onShow         : archiver_window(guid, 1),
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
    }).modal('show');
    init_modal_steps();

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

    if (typeof progressbar === 'undefined') {
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
        var value;

        if (!reply) {
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
        on: 'hover',
        hoverable: true,
        onVisible: function () {
            $('input[name="filter_value"]').focus();
        }
    });
    $('input[name="filter_value"]').on('input', function () {
        var filter_value = $(this).val();
        try {
            var regexp = new RegExp(filter_value, 'i');
        } catch (e) {
            var regexp = safe_string(filter_value);
        }
        $('table#datatable .filterable').each(function () {
            $(this).closest('tr').removeClass('filter matches');
        });
        $('table#datatable .filterable').each(function () {
            var content = $(this).html();
            if (typeof filter_value === 'undefined' ||
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
        var id;

        if (typeof (id = $(this).attr('id')) === 'undefined') {
            throw 'Failed to locate id-attribute!';
            return false;
        }
        if (id != 'select_all') {
            return true;
        }

        var state = $(".item.select.checkbox#select_all").checkbox("is checked");

        if (state != true && state != false) {
            throw 'Checkbox returned an invalid state!';
            return false;
        }

        if (state === true) {
            var to_state = "set checked";
        } else {
            var to_state = "set unchecked";
        }

        $(".item.select.checkbox[id!='select_all']").checkbox(to_state);
        return true;
    });
}

function archiver_window(guid, step, allow_unsaved_data)
{
    var id, guid, model, title, substore, archiver_wnd;

    if (typeof guid === 'undefined') {
        throw "no GUID provided!"
        return false;
    }

    if (!(substore = store.getSubStore('archiver_'+ guid))) {
        throw "failed to get archiver ThalliumStore!";
        return false;
    }

    if (!substore.has('archiver_wnd')) {
        throw "somehow we lost our modal window!"
        return false;
    }

    archiver_wnd = substore.get('archiver_wnd');

    if (!substore.has('id')) {
        throw 'store does not have an "id" value!';
        return false;
    }
    id = substore.get('id');

    if (!substore.has('guid')) {
        throw 'store does not have an "guid" value!';
        return false;
    }
    guid = substore.get('guid');

    if (!substore.has('model')) {
        throw 'store does not have an "model" value!';
        return false;
    }
    model = substore.get('model');

    if (!substore.has('title')) {
        throw 'store does not have an "title" value!';
        return false;
    }
    title = substore.get('title');

    if (substore.has('current_step')) {
        var current_step = substore.get('current_step');
    }

    if (typeof current_step !== 'undefined' || current_step > 0) {
        var unsaved_data = false;
        var savebuttons = archiver_wnd.find('button.save');
        if (typeof savebuttons !== 'undefined' && savebuttons.length) {
            savebuttons.each(function () {
                if ($(this).hasClass('red') && $(this).hasClass('shape')) {
                    unsaved_data = true;
                }
            });

            if (unsaved_data === true && typeof allow_unsaved_data === 'undefined') {
                archiver_wnd
                    .modal('setting', { closable: false })
                    .removeClass('active')
                    .modal('refresh')
                    .addClass('blurring');
                archiver_wnd.find('.ui.dimmer').addClass('active');
                var del_wnd = show_modal('confirm', {
                    closable : false,
                    header : 'Unsaved data on page!',
                    icon : 'red warning icon',
                    content : 'Some data on this page has not been saved!<br />Do you really want to continue? Data will be lost.',
                    allowMultiple: true,
                    onDeny : function () {
                        archiver_wnd.find('.ui.dimmer').removeClass('active');
                        archiver_wnd
                            .removeClass('blurring')
                            .modal('setting', { closable: true })
                            .addClass('active')
                            .modal('refresh');
                        $(this).modal('hide');
                        return false;
                    },
                    onApprove : function () {
                        archiver_wnd.find('.ui.dimmer').removeClass('active');
                        archiver_wnd
                            .removeClass('blurring')
                            .modal('setting', { closable: true })
                            .addClass('active')
                            .modal('refresh');
                        $(this).modal('hide');
                        archiver_window(guid, step, true);
                        return false;
                    },
                });
                return;
            }
        }
    }

    if (typeof step === 'undefined' || !(/^[0-9]+$/).test(step)) {
        step = 1;
    }

    $('.ui.steps .active.step')
        .removeClass('active');
    $('.ui.steps #archiver_step_'+step)
        .removeClass('disabled')
        .addClass('active');

    var request_data = {
        content : 'archiver',
        id      : id,
        guid    : guid,
        model   : model,
        step    : step
    };

    $.when(rpc_get_content('queue', request_data)).done(function (data) {
        $('#archiver_modal_window_'+ guid + ' #archiver_content').html(data);
        eval($('#archiver_modal_window_'+ guid + ' .header.window.title').html(title));
        eval($('#archiver_modal_window_'+ guid + ' .ui.steps.archiver .step').attr('data-modal-title', title));
        eval($('#archiver_modal_window_'+ guid + '.ui.steps.archiver .step').attr('data-id', id));
        eval($('#archiver_modal_window_'+ guid + ' .ui.steps.archiver .step').attr('data-guid', guid));
        substore.set('current_step', step);
        return true;
    });

    return;
}

function load_datepickers(mode)
{
    if (typeof mode === 'undefined' || mode == "" || (mode != "document" && mode != "queue")) {
        throw 'mode parameter is invalid!'
        return false;
    }

    var current_custom_date = $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').val();
    if (typeof current_custom_date === 'undefined' ||
        !current_custom_date ||
        current_custom_date == '0000-00-00'
    ) {
        current_custom_date = null;
    }

    var current_expiry_date = $('#'+ mode +'_expiry_date_form input[type="text"][name="'+ mode +'_expiry_date"]').val();
    if (typeof current_expiry_date === 'undefined' ||
        !current_expiry_date ||
        current_expiry_date == '0000-00-00'
    ) {
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
            var curval = $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').val();
            var newval = $(this).datepicker('getDate');
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
            var curval = $('#'+ mode +'_expiry_date_form input[type="text"][name="'+ mode +'_expiry_date"]').val();
            var newval = $(this).datepicker('getDate');
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
            if (typeof current_custom_date === 'undefined' ||
                !current_custom_date ||
                current_custom_date == '' ||
                current_custom_date == '0000-00-00'
            ) {
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
            if (typeof current_expiry_date === 'undefined' ||
                !current_expiry_date ||
                current_expiry_date == '' ||
                current_expiry_date == '0000-00-00'
            ) {
                var current_custom_date = $('#'+ mode +'_custom_date_form input[type="text"][name="'+ mode +'_custom_date"]').val();
                if (current_custom_date) {
                    var current = new Date(current_custom_date);
                    $('#'+ mode +'_expiry_date').datepicker('setDate', new Date(new Date(current).setYear(current.getFullYear()+7)));
                } else {
                    $('#'+ mode +'_expiry_date').datepicker('setDate', new Date());
                }
            }
            $('#'+ mode +'_expiry_date_form input').trigger('input');
            $('#'+ mode +'_expiry_date_form').transition('fly down');
            return true;
        }
    });

    $('#'+ mode +'_custom_date_form input').on('input', function () {
        var savebutton = $('#'+ mode +'_custom_date_form button.save');
        if (!savebutton.hasClass('red shape')) {
            savebutton.addClass('red shape');
            savebutton.transition('bounce');
        }
    });

    $('#'+ mode +'_expiry_date_form input').on('input', function () {
        var savebutton = $('#'+ mode +'_expiry_date_form button.save');
        if (!savebutton.hasClass('red shape')) {
            savebutton.addClass('red shape');
            savebutton.transition('bounce');
        }
    });

    $('#'+ mode +'_custom_date_form').on('submit', function () {
        rpc_object_update($(this), function (element, data) {
            if (data != "ok") {
                return true;
            }
            var savebutton = element.find('button.save');
            savebutton.transition('tada').removeClass('red shape');
            return true;
        });
        return false;
    });

    $('#'+ mode +'_expiry_date_form').on('submit', function () {
        rpc_object_update($(this), function (element, data) {
            if (data != "ok") {
                return true;
            }
            var savebutton = element.find('button.save');
            savebutton.transition('tada').removeClass('red shape');
            return true;
        });
        return false;
    });

    $('form.ui.form.keywords').on('submit', function () {
        rpc_object_update($(this), function (element, data) {
            if (data != "ok") {
                return true;
            }
            var savebutton = element.find("button.save");
            savebutton.transition('tada').removeClass('red shape');
            return true;
        });
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
    var id, guid, model, title, text, substore, splitter_wnd;

    if (typeof (title = element.attr('data-modal-title')) === 'undefined') {
        throw 'no "data-modal-title" attribute found!';
        return false;
    }

    if (typeof (text = element.attr("data-modal-text")) === 'undefined') {
        throw 'no "data-modal-text" attribute found!';
        return false;
    }

    if (typeof element === 'undefined' || ! element instanceof Array) {
        throw "element parameter is invalid!"
        return false;
    }

    if (typeof (id = element.attr('data-id')) === 'undefined') {
        throw 'no "data-id" attribute found!';
        return false;
    }

    if (typeof (guid = element.attr('data-guid')) === 'undefined') {
        throw 'no "data-guid" attribute found!';
        return false;
    }

    if (typeof (model = element.attr('data-model')) === 'undefined') {
        throw 'no "data-model" attribute found!';
        return false;
    }

    var substore = store.createSubStore('splitter_'+guid);
    substore.set('id', id);
    substore.set('guid', guid);
    substore.set('model', model);
    substore.set('title', title);
    splitter_wnd = substore.set('splitter_wnd', $("#splitter_modal_window_template").clone());

    if (typeof splitter_wnd === 'undefined' || splitter_wnd.length < 1) {
        throw "failed to clone #splitter_modal_window_template!";
        return false;
    }

    splitter_wnd.attr('id', 'splitter_modal_window');
    splitter_wnd.modal({
        closable       : true,
        blurring       : false,
        title          : title,
        observeChanges : true,
        onShow         : splitter_window(1, guid)
    }).modal('show');

    return splitter_wnd;
}

function splitter_window(step, guid)
{
    var id, guid, model, title, substore, splitter_wnd;

    if (typeof guid === 'undefined') {
        guid = '';
    }

    if (!(substore = store.getSubStore('splitter_'+ guid))) {
        throw "failed to get splitter ThalliumStore!";
        return false;
    }

    if (!(splitter_wnd = substore.get('splitter_wnd'))) {
        throw "somehow we lost our modal window!"
        return false;
    }

    if (!(id = substore.get('id'))) {
        throw 'store does not have an "id" value!';
        return false;
    }

    if (!(guid = substore.get('guid'))) {
        throw 'store does not have an "guid" value!';
        return false;
    }

    if (!(model = substore.get('model'))) {
        throw 'store does not have an "model" value!';
        return false;
    }

    if (!(title = substore.get('title'))) {
        throw 'store does not have an "title" value!';
        return false;
    }

    if (typeof step === 'undefined') {
        if (substore.has('step')) {
            step = substore.get('step');
        }
        if (!step || !(/^[0-9]+$/).test(step)) {
            step = 1;
        }
    }

    substore.set('step', step);

    $('.ui.steps .active.step')
        .removeClass('active');
    $('.ui.steps #splitter_step_'+step)
        .removeClass('disabled')
        .addClass('active');

    var request_data = {
        content : 'splitter',
        id      : id,
        guid    : guid,
        model   : model,
        step    : step
    };

    $.when(rpc_get_content('queue', request_data)).done(function (data) {
        $('#splitter_modal_window #splitter_content').html(data);
        eval($('#splitter_modal_window .header.window.title').html(title));
        eval($('#splitter_modal_window .ui.steps.splitter .step').attr('data-modal-title', title));
        eval($('#splitter_modal_window .ui.steps.splitter .step').attr('data-id', id));
        eval($('#splitter_modal_window .ui.steps.splitter .step').attr('data-guid', guid));
        return true;
    });

    return;
}

function isInteger(x)
{
    return x % 1 === 0;
}

function init_action_links()
{
    $("a.delete.item").click(function () {
        delete_object($(this));
    });
    $("a.archive.item").click(function () {
        archive_object($(this));
    });
    $("a.split.item").click(function () {
        split_object($(this));
    });
    $("a.sign.document").click(function () {
        rpc_object_sign($(this));
    });
    $("a.scan.document, a.scan.item").click(function () {
        rpc_object_scan($(this));
    });

    return true;
}

function init_modal_steps()
{
    var test = $('.ui.archiver.steps a.step').on('click', ':not(.disabled)', function () {
        var modal, id, guid, link;
        modal = eval($(this).closest('.ui.long.fullscreen.modal[id^=archiver_modal_window_][id!=archiver_modal_window_template]'));
        if (typeof modal === 'undefined' || !modal) {
            throw 'can not find the modal window!';
            return false;
        }
        id = modal.attr('id');
        if (typeof id === 'undefined' || !id) {
            throw 'unable to retrieve id attribute from modal window element!';
            return false;
        }
        guid = id.match(/^archiver_modal_window_(\w+)$/);
        if (typeof guid === 'undefined' || typeof guid[1] === 'undefined' || !guid[1]) {
            throw 'unable to extract guid from element id!';
            return false;
        }
        guid = guid[1];

        link = eval($(this).closest('a.step'));
        id = $(link).attr('id');
        if (typeof id === 'undefined' || !id) {
            id = 'archiver_step_1';
        }
        var step_no = id.match(/^archiver_step_(\d)$/);
        if (typeof step_no === 'undefined' || typeof step_no[1] === 'undefined' || !step_no[1]) {
            throw 'unable to extract step from link id!';
            return false;
        }
        archiver_window(guid, step_no[1]);
    });

    $('a.step, i.close.icon').popup({
        exclusive: true,
        lastResort: true,
    });
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
