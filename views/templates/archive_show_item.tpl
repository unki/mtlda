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
<div class="item">
 {if $latest_document_version == $item->document_version}
 <div class="right floated content">
  <i class="ui big red tag icon" data-title="This is the latest version of the current document."></i>
 </div>
 {/if}
 <i class="{if $item->document_signed_copy == 'Y'}protect{else}file text{/if} icon" data-title="{if $item->document_signed_copy == 'Y'}This is a signed copy of the original document.{else}This is a copy of the original document.{/if}"></i>
 <div class="content">
  <div class="header">
   <a name="filename_{$item->document_idx}" class="inline editable content" data-orig-value="{$item->document_file_name}" href="{get_url page=document mode=show id=$item_safe_link file=$item->document_file_name}">{$item->document_file_name}</a>&nbsp;&nbsp;
   <a name="filename_{$item->document_idx}" class="inline editable edit link" data-inline-name="filename_{$item->document_idx}"><i class="tiny edit icon"></i></a>
   <a href="{get_url page=document mode=delete id=$item_safe_link}"><i class="remove circle icon"></i></a>
   <div name="filename_{$item->document_idx}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="filename_{$item->document_idx}" value="{$item->document_file_name}" data-action="update" data-model="document" data-key="document_file_name" data-id="{$item->document_idx}" />
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
  </div>
  <div class="description">Version {$item->document_version} (created {$item->document_time|date_format:"%Y.%m.%d %H:%M"})</div>
{if $item->document_signed_copy != 'Y' }
  <br />
  <a class="sign document" data-id="{$item->document_idx}" data-guid="{$item->document_guid}"><i class="protect icon"></i>Click to digitally sign document</a>.
{/if}
 </div>
</div>
{if $item_has_descendants}
<!-- starting subitem -->
<div class="item">
 <div class="content">
  <div class="ui tiny very relaxed divided selection list">
{/if}
{if $item_is_last_descendant}
  </div>
 </div>
</div>
<!-- ending subitem -->
{/if}
