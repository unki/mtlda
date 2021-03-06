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
<div class="ui teal inverted fixed menu">
 <a href="{get_url page=main}" class="header brand item"><i class="database icon"></i>MTLDA</a>
 <a href="{get_url page=main}" class="item {get_menu_state page=main}">Main</a>
 <a href="{get_url page=archive}" class="item {get_menu_state page=archive}">Archive</a>
 <a href="{get_url page=keywords}" class="item {get_menu_state page=keywords}">Keywords</a>
 <a href="{get_url page=queue}" class="item {get_menu_state page=queue}">Queue</a>
{if isset($http_upload_is_enabled) }
 <a href="{get_url page=upload}" class="item {get_menu_state page=upload}">Upload</a>
{/if}
 <a href="{get_url page=options}" class="item {get_menu_state page=options}">Options</a>
 <a href="{get_url page=about}" class="item {get_menu_state page=about}">About</a>
 <div class="right menu container">
  <div class="item">
   <a href="logout.html" class="item">Logout</a>
  </div>
  <div class="item">
   <form class="ui form search" method="POST" action="{get_url page=search}">
    <div class="ui icon input">
     <input type="text" name="search" placeholder="Search...">
     <i class="search link icon"></i>
    </div>
   </form>
  </div>
 </div>
</div>
