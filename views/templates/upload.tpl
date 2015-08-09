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
<div id="page_title">Upload</div>
<div id="dropzone" style="float: left; display: block;">
 <form action="{get_url page=upload id=$upload_token}" class="dropzone" id="upload">
  <div class="fallback"><input name="file" type="file" multiple /></div>
 </form>
</div>
<input type="button" id="submitbtn" value="Upload" />
<div id="transferlist" style="clear: left;">
 Transfer List:<br />
</div>
<div id="progress">Progress:</div>
