{*
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
*}
<div class="ui grid">
 <div class="ten wide column">
  <div class="left aligned container">
   <h1 class="ui header"><i class="wait icon"></i>Queue</h1>
  </div>
 </div>
 <div class="six wide column">
  <div class="right aligned container">
   <a href="{get_url page=upload}"><i class="upload icon"></i>Upload</a>&nbsp;
   <a id="mailimport"><i class="mail outline icon"></i>Mailimport</a>
  </div>
 </div>
</div>
<table class="ui celled table">
 <thead>
  <tr>
   <th>Idx</th>
   <th>Filename</th>
   <th>Size</th>
   <th>State</th>
   <th>Time</th>
   <th colspan="4">Actions</th>
  </tr>
 </thead>
 <tbody>
{queue_list}
 <tr class="queueitem">
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}">{$item->queue_idx}</a></td>
  <td>
   <form id="filename_edit_{$item->queue_idx}" class="ui form" style="display: none;" onsubmit="return false;">
    <div class="fields">
     <div class="field small ui input">
      <input type="text" name="queue_file_name[{$item->queue_idx}]" value="{$item->queue_file_name}" data-action="update" data-model="queueitem" />
     </div>
     <div class="field">
      <button class="circular small ui icon button update filename" data-target="queue_file_name[{$item->queue_idx}]" data-type="filename" data-id="{$item->queue_idx}" data-value="{$item->queue_file_name}"><i class="save icon"></i></button>
     </div>
     <div class="field">
      <button class="circular small ui icon button cancel filename" data-target="queue_file_name[{$item->queue_idx}]" data-type="filename" data-id="{$item->queue_idx}" data-value="{$item->queue_file_name}"><i class="cancel icon"></i></button>
     </div>
    </div>
   </form>
   <div id="filename_show_{$item->queue_idx}">
    <div style="float: left;"><a id="filename_label_{$item->queue_idx}" href="{get_url page=queue mode=edit id=$item_safe_link}">{$item->queue_file_name}</a></div>&nbsp;
    <a class="filename" data-type="filename" data-id="{$item->queue_idx}" data-value="{$item->queue_file_name}"><i class="edit icon" ></i></a>
   </div>
  </td>
  <td>{$item->queue_file_size}</td>
  <td class="state" id="queueitem-{$item_safe_link}">{$item->queue_state}</td>
  <td>{$item->queue_time}</td>
  <td><a class="preview" title="archive" id="queueitem-{$item_safe_link}">Preview</a></td>
  <td><a class="archive" title="archive" id="queueitem-{$item_safe_link}">Archive</a></td>
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}">Edit</a></td>
  <td><a class="delete" title="delete" id="queueitem-{$item_safe_link}">Delete</a></td>
 </tr>
{/queue_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="9">
    <div class="ui left floated">
     <a class="archive" title="archive" id="queueitem-all">Archive all</a>,&nbsp;
     <a class="delete" title="deleteall" id="queueitem-flush">Flush queue</a>
    </div>
    <div class="ui right floated pagination menu">
     <a class="icon item"><i class="left chevron icon"></i></a>
     <a class="item">1</a>
     <a class="item">2</a>
     <a class="item">3</a>
     <a class="item">4</a>
     <a class="icon item"><i class="right chevron icon"></i></a>
    </div>
   </th>
  </tr>
 </tfoot>
</table>
<div class="ui modal">
  <i class="close icon"></i>
  <div class="header">Header </div>
  <div class="image content">
    <div class="image">
      An image can appear on left or an icon
    </div>
    <div class="description">
      A description can appear on the right
    </div>
  </div>
  <div class="actions">
    <div class="ui button">Cancel</div>
    <div class="ui button">OK</div>
  </div>
</div>
<script type="text/javascript"><!--

$(document).ready(function() {

$('a.filename, button.filename.cancel').click(function(element) {
   type = $(this).attr('data-type');
   id = $(this).attr('data-id');
   value = $(this).attr('data-value');
   if (!type || !id || !value) {
      console.log('incomplete: ' + type + ', ' + id + ', ' + value);
      return
   }
   $('#' + type + '_show_' + id).toggle();
   $('#' + type + '_edit_' + id).toggle();
});

$('a#mailimport').click(function() {

   modal_wnd = show_modal({
      closeable : false,
      header : 'Mailimport',
      icon : 'mail outlined icon',
      content : '<div class="ui progress" id="uploadprogress"><div class="bar"><div class="progress"></div></div><div class="label"></div></div>',
      hasActions : false
   });

   pbar = $('.ui.basic.modal .image.content .description p');
   pbar.progress({
        autoSuccess: true,
        label: 'ratio',
        text: {
            label  : '{literal}{percent}{/literal} success',
            ratio  : 'percent'
        },
    });

    $.ajax({
        type: 'POST',
        url: 'rpc.html',
        data: ({
            type : 'rpc',
            action : 'mailimport'
        }),
        beforeSend: function() {
            pbar.progress({ percent: 10 });
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(data){
            if (data == "ok") {
               pbar.progress({ percent: 100 });
               setTimeout(function() {
                  if (modal_wnd.modal('is active')) {
                     modal_wnd.modal('hide');
                  }
               }, 2000);
               return;
            }
            modal_wnd.modal('setting', 'closable', true);
            modal_wnd.addClass('long');
            modal_wnd.modal('refresh');
            pbar.html('Server returned: ' + data + ', length ' + data.length);
            return;
        }
    });

    return true;

});
});

--></script>
