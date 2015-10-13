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
   <a name="keyword_{$item->keyword_idx}" class="inline editable content" data-orig-value="{$item->keyword_name}">{$item->keyword_name}</a>
   <a name="keyword_{$item->keyword_idx}" class="inline editable edit link" data-inline-name="keyword_{$item->keyword_idx}"><i class="tiny edit icon"></i></a>
   <div name="keyword_{$item->keyword_idx}" class="inline editable formsrc" style="display: none;">
    <form class="ui form" onsubmit="return false;">
     <div class="fields">
      <div class="field small ui input">
       <input type="text" name="keyword_{$item->keyword_idx}" value="{$item->keyword_name}" data-action="update" data-model="keyword" data-key="keyword_name" data-id="{$item->keyword_idx}" />
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
  <td><a class="delete" title="delete" id="keyword-{$item_safe_link}"><i class="remove circle icon"></i>Delete</a></td>
 </tr>
{/keywords_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="2">
    <div class="ui right floated pagination menu">
     <a class="icon item"><i class="left chevron icon"></i></a>
     <a class="item">1</a>
     <a class="item">2</a>
     <a class="item">3</a>
     <a class="item">4</a>
     <a class="icon item"><i class="right chevron icon"></i></a>
    </div>
   </th>
  </tr>
 </tfoot>
</table>
