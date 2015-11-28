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
<table class="ui sortable celled table" id="datatable">
 <thead>
  <tr>
   <th class="no-sort">
    <div class="ui fitted checkbox item select" id="select_all">
     <input type="checkbox">
     <label></label>
    </div>
   </th>
   <th>Idx</th>
   <th>Document</th>
   <th>Time</th>
   <th colspan="3" class="no-sort">Actions</th>
  </tr>
 </thead>
 <tbody>
{archive_list}
 <tr>
  <td>
   <div class="ui fitted checkbox item select" id="select_{$item->getId()}">
    <input type="checkbox">
    <label></label>
   </div>
  </td>
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
  <td>{if $item->hasCustomDate()}{$item->getCustomDate()|date_format:"%Y.%m.%d"}{else}{$item->document_time|date_format:"%Y.%m.%d %H:%M"}{/if}</td>
  <td><a href="{get_url page=archive mode=show id=$item_safe_link}"><i class="file text icon"></i>Details</a></td>
  <td><a href="{$app_web_path}/resources/pdfjs/web/viewer.html?file={get_url page=document mode=show id=$document_safe_link}" target="_blank"><i class="icons"><i class="file text icon"></i><i class="corner search icon"></i></i>Preview</a></td>
  <td><a id="delete_link_{$item->getId()}" class="delete item" title="Delete {$item->getTitle()|escape}" data-model="document" data-id="{$item->document_idx}" data-guid="{$item->document_guid}"><i class="remove circle icon"></i>Delete</a></td>
 </tr>
{/archive_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="7">
    <div class="ui left floated segment raised segments">
     <a class="ui segment delete item" title="Delete selected items" data-modal-text="Do you really want to delete all selected items from archive?" data-id="selected" data-guid="selected" data-model="archive"><i class="remove circle icon"></i>Delete selected</a>
    </div>
{if isset($pager)}
{include file='pager.tpl' pager=$pager view=archive}
{/if}
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
   $('#datatable').tablesort();
});
--></script>
