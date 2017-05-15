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
<h1 class="ui header"><i class="database icon"></i>Welcome to MTLDA!</h1>
<div class="ui two column grid">

 <!-- left column -->
 <div class="column">
  <i class="archive icon"></i>Recently archived documents
  <div class="ui very relaxed divided selection list">
{if isset($has_archived_items) && $has_archived_items}
{top10 type=archive}
   <div class="item">
    <i class="file text icon"></i>
    <div class="content">
     <div class="header">
      <a href="{get_url page=archive mode=show id=$item_safe_link}">{if $item->hasTitle()}{$item->getTitle()|truncate:50}{else}{$item->getFileName()|truncate:50}{/if}</a>&nbsp;
      <a href="{$app_web_path}/resources/pdfjs-1.6.210/web/viewer.html?file={get_url page=document mode=show id=$document_safe_link}" target="_blank"><i class="search icon"></i></a>
     </div>
     <div class="description">{if $item->hasCustomDate()}from {$item->getCustomDate()|date_format:"%Y.%m.%d"}{else}archived on {$item->getTime()|date_format:"%Y.%m.%d %H:%M"}{/if}.</div>
    </div>
   </div>
{/top10}
{/if}
  </div>
 </div>

 <!-- right column -->
 <div class="column">
  <i class="wait icon"></i>Recently enqueued documents
  <div class="ui very relaxed divided selection list">
{if isset($has_pending_queue_items) && $has_pending_queue_items}
{top10 type=queue}
   <div class="item">
    <i class="file text icon"></i>
    <div class="content">
     <div class="header">
      <a href="{get_url page=queue mode=show id=$item_safe_link}">{if $item->hasTitle()}{$item->getTitle()|truncate:50}{/if}{$item->getFileName()|truncate:50}</a>
      <a href="{$app_web_path}/resources/pdfjs-1.6.210/web/viewer.html?file={get_url page=queue mode=show id=$item_safe_link}" target="_blank"><i class="search icon"></i></a>
     </div>
     <div class="description">added {$item->getTime()|date_format:"%Y.%m.%d %H:%M"}.</div>
    </div>
   </div>
{/top10}
{else}
   <div class="item">
    <div class="content">No items pending in queue.</div>
   </div>
{/if}
  </div>
 </div>

</div>
