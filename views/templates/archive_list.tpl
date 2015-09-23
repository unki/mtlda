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
<h1 class="ui header"><i class="archive icon"></i>Archive</h1>
<table class="ui celled table">
 <thead>
  <tr>
   <th>Idx</th>
   <th>Document</th>
   <th>Time</th>
   <th colspan="2">Actions</th>
  </tr>
 </thead>
 <tbody>
{archive_list}
 <tr>
  <td><a href="{get_url page=archive mode=show id=$item_safe_link}">{$item->document_idx}</a></td>
  <td><a href="{get_url page=archive mode=show id=$item_safe_link}">{$item->document_title}</a></td>
  <td>{$item->document_time}</td>
  <td><a href="{get_url page=document mode=show id=$document_safe_link}" target="_blank">Open</a></td>
  <td><a class="delete" title="delete" id="document-{$item_safe_link}">Delete</a></td>
 </tr>
{/archive_list}
 </tbody>
 <tfoot>
  <tr>
   <th colspan="6">
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
