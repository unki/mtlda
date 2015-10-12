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
   <a class="mail import"><i class="mail outline icon"></i>Mailimport</a>
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
  <td><a href="{get_url page=queue mode=show id=$item_safe_link}" target="_blank">{$item->queue_idx}</a></td>
  <td>
   <a name="filename_{$item->queue_idx}" class="inline editable content" data-orig-value="{$item->queue_file_name}" href="{get_url page=queue mode=show id=$item_safe_link}" target="_blank">{$item->queue_file_name}</a>
   <a name="filename_{$item->queue_idx}" class="inline editable edit link" data-inline-name="filename_{$item->queue_idx}"><i class="tiny edit icon"></i></a>
   <div name="filename_{$item->queue_idx}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="filename_{$item->queue_idx}" value="{$item->queue_file_name}" data-action="update" data-model="queueitem" data-key="queue_file_name" data-id="{$item->queue_idx}" />
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable save" type="submit"><i class="save icon"></i></button>
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
      </div>
     </div>
    </form>
   </div>
  </td>
  <td>{$item->queue_file_size}</td>
  <td class="state" id="queueitem-{$item_safe_link}">{$item->queue_state}</td>
  <td>{$item->queue_time}</td>
  <td><a class="preview" title="archive" id="queueitem-{$item_safe_link}"><i class="search icon"></i>Preview</a></td>
  <td><a class="archive" title="archive" id="queueitem-{$item_safe_link}"><i class="archive icon"></i>Archive</a></td>
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}"><i class="edit icon"></i>Edit</a></td>
  <td><a class="delete" title="delete" id="queueitem-{$item_safe_link}"><i class="remove circle icon"></i>Delete</a></td>
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

    $("a.mail.import").click(function() {
        rpc_mail_import($(this));
    });

});

--></script>
<div class="ui import modal">
 <i class="close icon"></i>
 <div class="header">header</div>
 <div class="image content">
  <div class="image">
   <i class="icon"></i>
  </div>
  <div class="description" style="width: 100%;">
   <p style="display: none">description</p>
   <div class="ui indicating progress active" id="importprogress">
    <div class="bar">
     <div class="progress">0%</div>
    </div>
    <div class="label">Please wait.</div>
   </div>
  </div>
 </div>
 <div class="actions">
  <div class="two fluid ui inverted buttons">
   <div class="ui red basic inverted button deny">
    <i class="remove icon"></i>No
   </div>
   <div class="ui green basic inverted button approve">
    <i class="checkmark icon"></i>Yes
   </div>
  </div>
 </div>
</div>