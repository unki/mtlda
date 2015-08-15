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
<div id="page_title">Archive</div>
<input type="text" name="keyword_name[new]" action="new" size="30" />&nbsp;
<input type="button" value="Add" target="keyword_name[new]" />&nbsp;
<table>
 <tr>
  <th>Idx</th>
  <th>Keyword</th>
 </tr>
{keywords_list}
 <tr>
  <td>{$item->keyword_idx}</td>
  <td>{$item->keyword_name}&nbsp;<input type="button" value="Edit" target="{$item->keyword_idx}" ></td>
  <td><a class="delete" title="delete" id="keyword-{$item_safe_link}">Delete</a></td>
 </tr>
{/keywords_list}
