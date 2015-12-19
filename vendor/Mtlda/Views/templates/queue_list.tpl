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
   <th>Filename</th>
   <th>Size</th>
   <th>State</th>
   <th>Time</th>
   <th colspan="4" class="no-sort">
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
 <tr class="queue item">
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
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
      </div>
      <div class="field">
       <button class="circular ui icon button" onclick="$('input[name=title_{$item->getId()}]').val($('a[name=filename_{$item->getId()}]').text()); return false;" title="Copy filename"><i class="copy icon"></i></button>
      </div>
     </div>
    </form>
   </div><br />
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
      </div>
      <div class="field">
       <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
      </div>
     </div>
    </form>
   </div>
  </td>
  <td class="filterable">{$item->getFileSize()}</td>
  <td class="filterable archive state" id="archive-state-{$item->getId()}">{$item->getState()}</td>
  <td class="filterable">{$item->getTime()|date_format:"%Y.%m.%d %H:%M"}</td>
  <td><a href="{$app_web_path}/resources/pdfjs/web/viewer.html?file={get_url page=queue mode=show id=$item_safe_link}" title="Preview {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" id="queueitem-{$item_safe_link}" target="_blank"><i class="search icon"></i>Preview</a></td>
  <td>
   <a class="archive item" id="archive_link_{$item->getId()}" title="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-modal-text="Please confirm that you would like to archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem"><i class="archive icon"></i>Archive</a>
   <div class="ui dropdown advanced"><i class="dropdown icon"></i><div class="menu"><div class="item"><a class="archive item advanced" title="Archive {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem"><i class="settings icon"></i>Advanced</a></div></div></div>
  </td>
  <td>
   <i class="edit icon"></i>Edit
   <div class="ui dropdown edit"><i class="dropdown icon"></i><div class="menu"><div class="item"><a class="split item edit" title="Split {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem"><i class="expand icon"></i>Split</a></div></div></div>
  </td>
  <td><a id="delete_link_{$item->getId()}" class="delete item" title="Delete {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem"><i class="remove circle icon"></i>Delete</a></td>
 </tr>
{/queue_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="10">
    <div class="ui left floated borderless small menu">
     <a class="archive item" title="Archive selected items" data-modal-text="Please confirm that you would like to archive selected items." data-id="selected" data-guid="selected" data-model="queue"><i class="archive icon"></i>Archive selected</a>
     <a class="delete item" title="Delete selected items" data-modal-text="Do you really want to delete all selected items from queue?" data-id="selected" data-guid="selected" data-model="queue"><i class="remove circle icon"></i>Delete selected</a>
     <a class="archive item" title="Archive all items" data-id="all" data-guid="all" data-model="queue"><i class="archive icon"></i>Archive all</a>
     <a class="delete item" title="Delete all" data-modal-text="Do you really want to delete all items from queue?" data-id="all" data-guid="all" data-model="queue"><i class="remove circle icon"></i>Delete all</a>
    </div>
{if isset($pager)}
{include file='pager.tpl' pager=$pager view=queue}
{/if}
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
<div class="ui import archive delete modal">
 <i class="close icon"></i>
 <div class="header">header</div>
 <div class="image content">
  <div class="image">
   <i class="icon"></i>
  </div>
  <div class="description" style="width: 100%;">
   <p style="display: none">description</p>
   <div class="ui indicating progress active" id="progressbar">
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
    $('.ui.dropdown.advanced, .ui.dropdown.edit').dropdown();
});
--></script>
