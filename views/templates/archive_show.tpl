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
  <td>Filename:</td>
  <td>{$item->document_file_name}</td>
 </tr>
 <tr>
  <td>Size:</td>
  <td>{$item->document_file_size}</td>
 </tr>
 <tr>
  <td>Versions:</td>
  <td>
   <a href="{get_url page=document mode=show id=$item_safe_link}">v1 - Original imported document</a>
   <a href="{get_url page=document mode=sign id=$item_safe_link}">Sign</a>
   <br />
   {foreach $item_versions as $version}
    {assign var='safe_link' value="document-`$version->document_idx`-`$version->document_guid`"}
    <a href="{get_url page=document mode=show id=$safe_link}">v{$version->document_version} - {$version->document_file_name}</a>&nbsp;
    <a href="{get_url page=document mode=delete id=$safe_link}">Delete</a>
    <br />
   {/foreach}
  </td>
 </tr>
</table>
