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
<html>
 <head>
{include "header.tpl"}
 </head>
 <body>
  <div id="head">
   <div id="title">MTLDA</div>
   <div id="menu">
{include "menu.tpl"}
   </div>
  </div>
  <div id="content">
{$page_content}
  </div>
  <div id="dialog"></div>
 </body>
</html
<!-- vim: set filetype=smarty expandtab softtabstop=1 tabstop=1 shiftwidth=1 -->
