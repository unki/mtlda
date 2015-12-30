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
{if isset($http_upload_is_enabled) }
   <a href="{get_url page=upload}"><i class="upload icon"></i>Upload</a>&nbsp;
{/if}
{if isset($mail_import_is_enabled) }
   <a class="mail import"><i class="mail outline icon"></i>Mailimport</a>
{/if}
{if isset($user_triggers_import_enabled)}
   <a class="trigger import"><i class="spy icon"></i>Trigger Import{if isset($pending_incoming_items)} ({$pending_incoming_items}){/if}</a>
{/if}
  </div>
 </div>
</div>
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
   <th>Filename</th>
   <th>Size</th>
   <th>State</th>
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
{queue_list}
 <tr class="queue item" id="queue_item_{$item->getId()}">
  <td>
   <div class="ui fitted checkbox item select" id="select_{$item->getId()}">
    <input type="checkbox">
    <label></label>
   </div>
  </td>
  <td><a href="{get_url page=queue mode=show id=$item_safe_link}" target="_blank">{$item->getId()}</a></td>
  <td>
{if $item->hasTitle()}
   <a name="title_{$item->getId()}" class="filterable inline editable content" data-orig-value="{$item->getTitle()}" href="{get_url page=queue mode=show id=$item_safe_link}" target="_blank">{$item->getTitle()}</a>
   <a name="title_{$item->getId()}" class="inline editable edit link" data-inline-name="title_{$item->getId()}"><i class="tiny edit icon"></i></a>
   <div name="title_{$item->getId()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="title_{$item->getId()}" value="{$item->getTitle()}" data-action="update" data-model="queueitem" data-key="queue_title" data-id="{$item->getId()}" />
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable save" type="submit"><i class="save icon"></i></button>
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
       <button class="circular ui icon button" onclick="$('input[name=title_{$item->getId()}]').val($('a[name=filename_{$item->getId()}]').text()); return false;" title="Copy filename"><i class="copy icon"></i></button>
      </div>
     </div>
    </form>
   </div>&nbsp;(
{/if}
   <a name="filename_{$item->getId()}" class="filterable inline editable content" data-orig-value="{$item->getFileName()}" href="{get_url page=queue mode=show id=$item_safe_link}" target="_blank">{$item->getFileName()}</a>
   <a name="filename_{$item->getId()}" class="inline editable edit link" data-inline-name="filename_{$item->getId()}"><i class="tiny edit icon"></i></a>
   <div name="filename_{$item->getId()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="filename_{$item->getId()}" value="{$item->getFileName()}" data-action="update" data-model="queueitem" data-key="queue_file_name" data-id="{$item->getId()}" />
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable save" type="submit"><i class="save icon"></i></button>
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
       <button class="circular ui icon button" onclick="$('input[name=filename_{$item->getId()}]').val($('a[name=title_{$item->getId()}]').text()); return false;" title="Copy title"><i class="copy icon"></i></button>
      </div>
     </div>
    </form>
   </div>
{if $item->hasTitle()}
)
{/if}
  </td>
  <td class="filterable" data-sort-value="{$item->getFileSize()}">{$item->getFileSize()|filesize}</td>
  <td class="filterable archive state" id="archive-state-{$item->getId()}">{$item->getState()}</td>
  <td class="filterable">{$item->getTime()|date_format:"%Y.%m.%d %H:%M"}</td>
  <td>
   <div class="ui icon buttons">
    <a class="action link ui icon button" href="{$app_web_path}/resources/pdfjs/web/viewer.html?file={get_url page=queue mode=show id=$item_safe_link}" data-content="Preview {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide" id="queueitem-{$item_safe_link}" target="_blank"><i class="search icon"></i></a>
    <div class="ui top left pointing dropdown button action link">
     <i class="archive icon"></i><i class="dropdown icon"></i>
     <div class="menu">
      <a class="archive item action link" id="archive_link_{$item->getId()}" data-action-title="Archiving {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-title="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-text="Please confirm that you would like to archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide"><i class="archive icon"></i>Quick</a>
      <a class="archive item advanced action link" data-modal-title="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide"><i class="settings icon"></i>Advanced</a>
     </div>
    </div>
    <div class="ui top left pointing dropdown button action link">
     <i class="edit icon"></i><i class="dropdown icon"></i>
     <div class="menu">
      <a class="split item edit action link" data-modal-title="Split {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Split {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide"><i class="expand icon"></i>Split</a>
     </div>
    </div>
    <a id="delete_link_{$item->getId()}" class="delete item ui icon button" data-action-title="Deleting {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-title="Delete {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-text="Please confirm to delete {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem"><i class="remove circle icon"></i></a>
   </div>
  </td>
 </tr>
{/queue_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="7">
    <div class="ui left floated borderless small menu">
     <a class="archive item" data-action-title="Archiving selected items" data-modal-title="Archive selected items" data-modal-text="Please confirm that you would like to archive selected items." data-id="selected" data-guid="selected" data-model="queue"><i class="archive icon"></i>Archive selected</a>
     <a class="delete item" data-action-title="Deleting selected items" data-modal-title="Delete selected items" data-modal-text="Do you really want to delete selected items from queue?" data-id="selected" data-guid="selected" data-model="queue"><i class="remove circle icon"></i>Delete selected</a>
     <a class="archive item" data-action-title="Archiving all items" data-modal-title="Archive all items" data-modal-text="Please confirm that you would like to archive all items." data-id="all" data-guid="all" data-model="queue"><i class="archive icon"></i>Archive all</a>
     <a class="delete item" data-action-title="Deleting all items" data-modal-title="Delete all items" data-modal-text="Do you really want to delete all items from queue?" data-id="all" data-guid="all" data-model="queue"><i class="remove circle icon"></i>Delete all</a>
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
$(document).ready(function() {

    $("a.mail.import").click(function() {
        rpc_mail_import($(this));
    });

    $("a.trigger.import").click(function() {
        trigger_import_run();
    });

    $('#datatable').tablesort();
    $('.ui.dropdown.action.link').dropdown({
      preserve: true
    });
    $('a.action.link').popup();
});
--></script>
