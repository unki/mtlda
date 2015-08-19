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

<div class="ui teal inverted fixed menu">
 <div class="header item">
  <i class="gamepad icon"></i>MTLDA
 </div>
 <a href="{get_url page=main}" class="item active">Main</a>
 <a href="{get_url page=archive}" class="item">Archive</a>
 <a href="{get_url page=queue}" class="item">Queue</a>
 <a href="{get_url page=upload}" class="item">Upload</a>
 <a href="{get_url page=options}" class="item">Options</a>
 <a href="{get_url page=about}" class="item">About</a>
 <div class="right menu">
  <div class="item">
   <a href="logout.html" class="item">Logout</a>
  </div>
  <div class="item">
   <div class="ui icon input">
    <input type="text" placeholder="Search...">
    <i class="search link icon"></i>
   </div>
  </div>
 </div>
</div>
