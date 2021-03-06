{*
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017>  <Andreas Unterkircher>
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
{if isset($http_upload_is_enabled) }
   <a class="action link" href="{get_url page=upload}" data-content="Goto to document upload page"><i class="upload icon"></i>Upload</a>&nbsp;
{/if}
{if isset($mail_import_is_enabled) }
   <a class="mail import action link" data-content="Invoke mail importer run"><i class="mail outline icon"></i>Mailimport</a>
{/if}
{if isset($user_triggers_import_enabled)}
   <a class="trigger import action link" data-content="Invoke importer on configured incoming directory"><i class="spy icon"></i>Trigger Import{if isset($pending_incoming_items)} ({$pending_incoming_items}){/if}</a>
{/if}
  </div>
 </div>
</div>
<table class="ui fixed striped single line selectable sortable celled table" id="datatable">
 <thead>
  <tr>
   <th class="no-sort one wide center aligned">
    <div class="ui fitted checkbox item select" id="select_all">
     <input type="checkbox">
     <label></label>
    </div>
   </th>
   <th class="eight wide" data-content="Click to sort list">Filename</th>
   <th data-content="Click to sort list">Size</th>
   <th data-content="Click to sort list">Time</th>
   <th class="no-sort four wide">
    <div class="two column ui grid">
     <div class="column">Actions</div>
     <div class="column right aligned">
      <a id="filterbutton" data-content="Filter list"><i class="filter icon"></i></a>
     </div>
    </div>
   </th>
  </tr>
 </thead>
 <tbody>
{queue_list}
 <tr class="queue item" id="queue_item_{$item->getIdx()}">
  <td class="center aligned">
   <div class="ui fitted checkbox item select" id="select_{$item->getIdx()}">
    <input type="checkbox">
    <label></label>
   </div>
  </td>
  <td class="nine wide">
{if $item->hasTitle()}
   <h5 class="ui header">
{/if}
   <div class="content">
{if $item->hasTitle()}
   <a name="title_{$item->getIdx()}" class="filterable inline editable content" data-current-value="{$item->getTitle()}" data-orig-value="{$item->getTitle()}" href="{get_url page=queue mode=show id=$item_safe_link}" target="_blank">{$item->getTitle()}</a>
   <a name="title_{$item->getIdx()}" class="inline editable edit link" data-inline-name="title_{$item->getIdx()}"><i class="tiny edit icon"></i></a>
   <div name="title_{$item->getIdx()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="title_{$item->getIdx()}" value="{$item->getTitle()}" data-action="update" data-model="queueitem" data-key="queue_title" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" />
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable save" type="submit"><i class="save icon"></i></button>
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
       <button class="circular ui icon button" onclick="$('input[name=title_{$item->getIdx()}]').val($('a[name=filename_{$item->getIdx()}]').text()); return false;" title="Copy filename"><i class="copy icon"></i></button>
      </div>
     </div>
    </form>
   </div>
   <br />
   <div class="sub header">
{/if}
   <a name="filename_{$item->getIdx()}" class="filterable inline editable content" data-current-value="{$item->getFileName()}" data-orig-value="{$item->getFileName()}" href="{get_url page=queue mode=show id=$item_safe_link}" target="_blank">{$item->getFileName()}</a>
   <a name="filename_{$item->getIdx()}" class="inline editable edit link" data-inline-name="filename_{$item->getIdx()}"><i class="tiny edit icon"></i></a>
   <div name="filename_{$item->getIdx()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="filename_{$item->getIdx()}" value="{$item->getFileName()}" data-action="update" data-model="queueitem" data-key="queue_file_name" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" />
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable save" type="submit"><i class="save icon"></i></button>
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
       <button class="circular ui icon button" onclick="$('input[name=filename_{$item->getIdx()}]').val($('a[name=title_{$item->getIdx()}]').text()); return false;" title="Copy title"><i class="copy icon"></i></button>
      </div>
     </div>
    </form>
   </div>
{if $item->hasTitle()}
</div>
{/if}
    </div>
{if $item->hasTitle()}
   </h5>
{/if}
  </td>
  <td class="filterable" data-sort-value="{$item->getFileSize()}">{$item->getFileSize()|filesize}</td>
  <td class="filterable">{$item->getTime()|date_format:"%Y.%m.%d %H:%M"}</td>
  <td style="overflow: visible">
   <div class="ui icon buttons">
    <a class="action link ui icon button" href="{$app_web_path}/resources/pdfjs-1.6.210/web/viewer.html?file={get_url page=queue mode=show id=$item_safe_link}" data-content="Preview {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide" id="queueitem-{$item_safe_link}" target="_blank"><i class="search icon"></i></a>
    <div class="ui top left pointing dropdown button action link">
     <i class="archive icon"></i><i class="dropdown icon"></i>
     <div class="menu">
      <a class="archive item action link" id="archive_link_{$item->getIdx()}" data-action-title="Archiving {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-title="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-text="Please confirm that you would like to archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide"><i class="archive icon"></i>Quick</a>
      <a class="archive item advanced action link" data-modal-title="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-text="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide"><i class="settings icon"></i>Advanced</a>
     </div>
    </div>
    <div class="ui top left pointing dropdown button action link">
     <i class="edit icon"></i><i class="dropdown icon"></i>
     <div class="menu">
      <a class="split item edit action link" data-modal-title="Split {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-text="Split {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Split {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide"><i class="expand icon"></i>Split</a>
      <a class="scan item edit action link" data-action-title="Scan {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-title="Scan {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Scan {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide"><i class="find icon"></i>Scan</a>
     </div>
    </div>
    <a id="delete_link_{$item->getIdx()}" class="delete item ui icon button action link" data-action-title="Deleting {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-title="Delete {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-text="Please confirm to delete {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Delete {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}"><i class="remove circle icon"></i></a>
   </div>
  </td>
 </tr>
{/queue_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="5">
    <div class="ui left floated borderless small menu">
     <a class="archive item action link" data-action-title="Archiving selected items" data-modal-title="Archive selected items" data-modal-text="Please confirm that you would like to archive selected items." data-id="selected" data-guid="selected" data-model="queue" data-content="Archive selected items"><i class="archive icon"></i>Archive selected</a>
     <a class="delete item action link" data-action-title="Deleting selected items" data-modal-title="Delete selected items" data-modal-text="Do you really want to delete selected items from queue?" data-id="selected" data-guid="selected" data-model="queue" data-content="Delete selected items"><i class="remove circle icon"></i>Delete selected</a>
     <a class="archive item action link" data-action-title="Archiving all items" data-modal-title="Archive all items" data-modal-text="Please confirm that you would like to archive all items." data-id="all" data-guid="all" data-model="queue" data-content="Archive all items"><i class="archive icon"></i>Archive all</a>
     <a class="delete item action link" data-action-title="Deleting all items" data-modal-title="Delete all items" data-modal-text="Do you really want to delete all items from queue?" data-id="all" data-guid="all" data-model="queue" data-content="Delete all items"><i class="remove circle icon"></i>Delete all</a>
    </div>
{if isset($pager)}
{include file='pager.tpl' pager=$pager view=queue}
{/if}
   </th>
  </tr>
 </tfoot>
</table>
{include "archiver_dialog_modal.tpl"}
{include "splitter_dialog_modal.tpl"}
<script type="text/javascript"><!--

'use strict';

$(document).ready(function() {

    $("a.mail.import").click(function() {
        rpc_mail_import($(this));
    });

    $("a.trigger.import").click(function() {
        trigger_import_run();
    });

    $('#datatable').tablesort();
    $('.ui.dropdown.action.link').dropdown({
        preserve: true,
        on: 'hover',
        hoverable: true,
    });
    $('a.action.link, table#datatable thead tr th, a#filterbutton').popup({
        exclusive: true,
        lastResort: true,
    });
});
--></script>
