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
   <h1 class="ui header"><i class="archive icon"></i>Keywords</h1>
  </div>
 </div>
 <div class="six wide column">
  <div class="right aligned container">
   <form class="ui form add" onsubmit="return false;" data-target="keyword_add">
    <div class="fields">
     <div class="field">
      <input type="text" name="keyword_add" placeholder="Add keywords" data-action="add" data-model="keyword" data-key="keyword_name" data-id="new" tabindex="0" />
     </div>
     <div class="field">
      <button class="circular ui button add" type="submit"><i class="save icon"></i>Add</button>
     </div>
    </div>
   </form>
  </div>
 </div>
</div>
<table class="ui celled table">
 <thead>
  <tr>
   <th>Keyword</th>
   <th>Actions</th>
  </tr>
 </thead>
 <tbody>
{keywords_list}
 <tr>
  <td>
   <div name="keyword_{$item->getId()}" class="inline editable content" data-orig-value="{$item->getName()}" style="float: left;">{$item->getName()}</div>&nbsp;
   <a name="keyword_{$item->getId()}" class="inline editable edit link" data-inline-name="keyword_{$item->getId()}"><i class="tiny edit icon"></i></a>
   <div name="keyword_{$item->getId()}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="keyword_{$item->getId()}" value="{$item->getName()}" data-action="update" data-model="keyword" data-key="keyword_name" data-id="{$item->getId()}" />
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
  <td><a class="delete item" title="Delete {$item->getName()|escape}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="keyword"><i class="remove circle icon"></i>Delete</a></td>
 </tr>
{/keywords_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="2">
{include file='pager.tpl' pager=$pager view=keywords}
   </th>
  </tr>
 </tfoot>
</table>
