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
 {if $item->hasVersion() && $latest_document_version == $item->getVersion()}
 <div class="right floated content">
  <i class="ui big red tag icon" data-title="This is the latest version of the current document."></i>
 </div>
 {/if}
 <i class="{if $item->isSignedCopy()}protect{else}file text{/if} icon" data-title="{if $item->isSignedCopy()}This is a signed copy of the original document.{else}This is a copy of the original document.{/if}"></i>
 <div class="content">
  <div class="header">
   <a name="filename_{$item->getIdx()}" class="inline editable content" data-current-value="{$item->getFileName()}" data-orig-value="{$item->getFileName()}" href="{get_url page=document mode=show id=$item_safe_link file=$item->getFileName()}">{$item->getFileName()}</a>&nbsp;&nbsp;
   <a name="filename_{$item->getIdx()}" class="inline editable edit link" data-inline-name="filename_{$item->getIdx()}"><i class="tiny edit icon"></i></a>
   <a class="delete item" title="Delete {$item->getFileName()|escape}" data-model="document" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}"><i class="remove circle icon"></i></a>
   <div name="filename_{$item->getIdx()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="filename_{$item->getIdx()}" value="{$item->getFileName()}" data-action="update" data-model="document" data-key="document_file_name" data-id="{$item->getIdx()}" />
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
  <div class="description">Version {$item->getVersion()} (created {$item->getTime()|date_format:"%Y.%m.%d %H:%M"})</div>
{if ! $item->isSignedCopy()}
  <br />
  <a class="sign document" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-title="{$item->getTitle()}"><i class="protect icon"></i>Click to digitally sign document</a>.
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
