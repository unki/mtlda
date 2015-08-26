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
   <form class="ui form">
    <div class="fields">
     <div class="field">
      <label>Add keyword:</label>
      <input type="text" name="keyword_name[new]" placeholder="invoice, personal, ..." action="new" tabindex="0" />
     </div>
     <div class="field">
      <label>&nbsp;</label>
      <button class="circular ui button" target="new"><i class="save icon"></i>Add</button>
     </div>
    </div>
   </form>
  </div>
 </div>
</div>
<table class="ui celled table">
 <thead>
  <tr>
   <th>Idx</th>
   <th>Keyword</th>
   <th colspan="2">Actions</th>
  </tr>
 </thead>
 <tbody>
{keywords_list}
 <tr>
  <td>{$item->keyword_idx}</td>
  <td>{$item->keyword_name}&nbsp;<input type="button" value="Edit" target="{$item->keyword_idx}" ></td>
  <td><a class="delete" title="delete" id="keyword-{$item_safe_link}">Delete</a></td>
 </tr>
{/keywords_list}
 <tfoot>
  <tr>
   <th colspan="9">
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
 </tbody>
</table>
