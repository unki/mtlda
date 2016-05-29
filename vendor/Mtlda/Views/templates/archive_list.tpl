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
<table class="ui striped single line selectable sortable celled table" id="datatable">
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
   <th class="no-sort">
    <div class="two column ui grid">
     <div class="column">Actions</div>
     <div class="column right aligned">
      <a id="filterbutton"><i class="filter icon"></i></a>
     </div>
    </div>
   </th>
  </tr>
 </thead>
 <tbody>
{archive_list}
 <tr>
  <td>
   <div class="ui fitted checkbox item select" id="select_{$item->getIdx()}">
    <input type="checkbox">
    <label></label>
   </div>
  </td>
  <td><a class="filterable" href="{get_url page=archive mode=show id=$item_safe_link}">{$item->getIdx()}</a></td>
  <td>
   <a name="title_{$item->getIdx()}" class="filterable inline editable content" data-current-value="{if $item->hasTitle()}{$item->getTitle()}{/if}" data-orig-value="{if $item->hasTitle()}{$item->getTitle()}{/if}" href="{get_url page=archive mode=show id=$item_safe_link}">{if $item->hasTitle()}{$item->getTitle()}{/if}</a>
   <a name="title_{$item->getIdx()}" class="inline editable edit link" data-inline-name="title_{$item->getIdx()}"><i class="tiny edit icon"></i></a>
   <div name="title_{$item->getIdx()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="title_{$item->getIdx()}" value="{$item->getTitle()}" data-action="update" data-model="document" data-key="document_title" data-id="{$item->getIdx()}" />
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
  <td class="filterable">{if $item->hasCustomDate()}{$item->getCustomDate()|date_format:"%Y.%m.%d"}{else}{$item->getTime()|date_format:"%Y.%m.%d %H:%M"}{/if}</td>
  <td>
   <div class="ui icon buttons">
    <a class="open item ui icon button" href="{get_url page=archive mode=show id=$item_safe_link}"><i class="file text icon"></i></a>
    <a class="preview item ui icon button action link" href="{$app_web_path}/resources/pdfjs/web/viewer.html?file={get_url page=document mode=show id=$document_safe_link}" target="_blank" data-content="Preview {$item->getTitle()|escape}" data-variation="wide"><i class="search icon"></i></a>
    <a id="delete_link_{$item->getIdx()}" class="delete item ui icon button action link" data-action-title="Deleting {$item->getTitle()|escape}" data-modal-title="Delete {$item->getTitle()|escape}" data-modal-text="Please confirm to delete {$item->getTitle()|escape}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="document" data-content="Delete {$item->getTitle()|escape}" data-variation="wide"><i class="remove circle icon"></i></a>
   </div>
  </td>
 </tr>
{/archive_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="5">
    <div class="ui left floated borderless small menu">
     <a class="delete item" data-action-title="Deleting selected items" data-modal-title="Delete selected items" data-modal-text="Do you really want to delete selected items from archive?" data-id="selected" data-guid="selected" data-model="archive"><i class="remove circle icon"></i>Delete selected</a>
    </div>
{if isset($pager)}
{include file='pager.tpl' pager=$pager view=archive}
{/if}
   </th>
  </tr>
 </tfoot>
</table>
<script type="text/javascript"><!--

'use strict';

$(document).ready(function() {
   $('#datatable').tablesort();
   $('a.action.link').popup({
      exclusive: true,
      lastResort: true,
   });
});
--></script>
