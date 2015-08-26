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
<h1 class="ui header"><i class="upload icon"></i>Document Upload</h1>
<div class="ui grid">
 <div class="row">
  <div id="dropzone" class="eight wide column ui segment" style="display: block">
   <form action="{get_url page=upload id=$upload_token}" class="dropzone" id="upload">
    <div class="fallback"><input name="file" type="file" multiple /></div>
   </form>
  </div>
  <div class="eight wide column">
   <div class="ui segment">
    <input type="button" id="uploadbtn" value="Upload" />
    <br /><br />
    <div class="ui progress" id="uploadprogress">
     <div class="bar">
      <div class="progress"></div>
     </div>
     <div class="label"></div>
    </div>
   </div>
   <div class="ui segment">
    Transfer List:<br />
    <div id="transferlist"></div>
   </div>
  </div>
 </div>
</div>
