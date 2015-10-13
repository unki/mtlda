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
<h1 class="ui header"><i class="archive icon"></i>Archive</h1>
<table class="ui celled table">
 <thead>
  <tr>
   <th>Idx</th>
   <th>Document</th>
   <th>Time</th>
   <th colspan="3">Actions</th>
  </tr>
 </thead>
 <tbody>
{archive_list}
 <tr>
  <td><a href="{get_url page=archive mode=show id=$item_safe_link}">{$item->document_idx}</a></td>
  <td>
   <a name="title_{$item->document_idx}" class="inline editable content" data-orig-value="{$item->document_title}" href="{get_url page=archive mode=show id=$item_safe_link}">{$item->document_title}</a>
   <a name="title_{$item->document_idx}" class="inline editable edit link" data-inline-name="title_{$item->document_idx}"><i class="tiny edit icon"></i></a>
   <div name="title_{$item->document_idx}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="title_{$item->document_idx}" value="{$item->document_title}" data-action="update" data-model="document" data-key="document_title" data-id="{$item->document_idx}" />
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
  <td>{$item->document_time}</td>
  <td><a href="{get_url page=archive mode=show id=$item_safe_link}"><i class="file text icon"></i>Details</a></td>
  <td><a href="{get_url page=document mode=show id=$document_safe_link}" target="_blank"><i class="icons"><i class="file text icon"></i><i class="corner search icon"></i></i>Show</a></td>
  <td><a class="delete document" data-id="{$item->document_idx}" data-guid="{$item->document_guid}"><i class="remove circle icon"></i>Delete</a></td>
 </tr>
{/archive_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="6">
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
<script type="text/javascript"><!--
$(document).ready(function() {
   $('a.title, button.title.cancel').click(function(element) {
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
});
--></script>
