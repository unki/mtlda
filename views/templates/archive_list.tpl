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
<table>
 <tr>
  <th>Idx</th>
  <th>Filename</th>
  <th>Hash</th>
  <th>Size</th>
  <th>Time</th>
 </tr>
{queue_list}
 <tr>
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}">{$item->queue_idx}</a></td>
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}">{$item->queue_file_name}</a></td>
  <td>{$item->queue_file_size}</td>
  <td>{$item->queue_time}</td>
  <td><a class="preview" title="archive" id="queueitem-{$item_safe_link}">Preview</a></td>
  <td><a class="archive" title="archive" id="queueitem-{$item_safe_link}">Archive</a></td>
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}">Edit</a></td>
  <td><a class="delete" title="delete" id="queueitem-{$item_safe_link}">Delete</a></td>
 </tr>
{/queue_list}