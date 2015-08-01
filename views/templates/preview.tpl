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
<!-- first line -->
<div style="float: left; width: 20%; vertical-align: top">
 <a href="#" onclick="change_preview('prev');"><img src="{$image_arrow_left}" alt="left arrow icon" /></a>
</div>
<div style="float: left; width: 60%; vertical-align: middle; text-align: center">
 {$img_name}
</div>
<div style="float: left; width: 20%; vertical-align: top">
 <a href="#" onclick="change_preview('next');"><img src="{$image_arrow_right}" alt="right arrow icon" /></a>
</div>
<!-- /first line -->

<!-- second line -->
<div style="float: left; width: 20%; vertical-align: middle; clear: left">
 Details:
</div>
<!-- preview image -->
<div style="float: left; width: 60%; vertical-align: top; text-align: center; padding-top: 20px">
 <img name="previewimg" load="{$img_url}" src="{$img_load}" imgid="{$img_id}" />
</div>
<!-- /preview image -->
<div style="float: left; width: 20%; vertical-align: middle">
  <div style="clear: left;">
   Signing options:
  </div>
  <div style="float: left; clear: left;">
   <a href="#" onclick="update_sign_position('top-left');"><img src="{$document_top_left}" alt="top left document icon" /></a>
  </div>
  <div style="float: left;">
   <a href="#" onclick="update_sign_position('top-center');"><img src="{$document_top_center}" alt="top center document icon" /></a>
  </div>
  <div style="float: left;">
   <a href="#" onclick="update_sign_position('top-right');"><img src="{$document_top_right}" alt="top right document icon" /></a>
  </div>
   <div style="float: left; clear: left;">
   <a href="#" onclick="update_sign_position('middle-left');"><img src="{$document_middle_left}" alt="middle left document icon" /></a>
  </div>
  <div style="float: left;">
   <a href="#" onclick="update_sign_position('middle-center');"><img src="{$document_middle_center}" alt="middle center document icon" /></a>
  </div>
  <div style="float: left;">
   <a href="#" onclick="update_sign_position('middle-right');"><img src="{$document_middle_right}" alt="middle right document icon" /></a>
  </div>
  <div style="float: left; clear: left;">
   <a href="#" onclick="update_sign_position('bottom-left');"><img src="{$document_bottom_left}" alt="bottom left document icon" /></a>
  </div>
  <div style="float: left;">
   <a href="#" onclick="update_sign_position('bottom-enter');"><img src="{$document_bottom_center}" alt="bottom center document icon" /></a>
  </div>
  <div style="float: left;">
   <a href="#" onclick="update_sign_position('bottom-right');"><img src="{$document_bottom_right}" alt="bottom right document icon" /></a>
  </div>
</div>
