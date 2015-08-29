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
<h1 class="ui header">{$item->document_file_name}</h1>
<div class="ui grid">
 <div class="row">
  <div class="column">Filename:</div>
  <div class="fifteen wide column">{$item->document_file_name}</div>
 </div>
 <div class="row">
  <div class="column">Size:</div>
  <div class="fifteen wide column">{$item->document_file_size}</div>
 </div>
 <div class="row">
  <div class="column">Versions:</div>
  <div class="fifteen wide column">
   <div class="ui list">
    <div class="item"><a href="{get_url page=document mode=show id=$item_safe_link}">v1 - Original imported document</a></div>
    <div class="item"><a href="{get_url page=document mode=sign id=$item_safe_link}">Sign</a></div>
{if $item_versions}
    <div class="item ui divider"></div>
{foreach $item_versions as $version}
 {assign var='safe_link' value="document-`$version->document_idx`-`$version->document_guid`"}
    <div class="item">
     <a href="{get_url page=document mode=show id=$safe_link}">v{$version->document_version} - {$version->document_file_name}</a>&nbsp;
     <a href="{get_url page=document mode=delete id=$safe_link}">Delete</a>
    </div>
{/foreach}
{/if}
   </div>
  </div>
 </div>
</div>
