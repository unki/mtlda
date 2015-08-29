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
<div class="ui long modal">
 <i class="close icon"></i>
 <div class="ui center aligned header">
   {$img_name}
 </div>
 <div class="ui center aligned image content">
 <div class="ui grid">
  <!-- first line -->
  <div class="row">
   <div class="two wide column">
    <a href="#" onclick="change_preview('prev');"><img src="{$image_arrow_left}" alt="left arrow icon" /></a>
   </div>
   <div class="twelve wide column">&nbsp;</div>
   <div class="two wide column">
    <a href="#" onclick="change_preview('next');"><img src="{$image_arrow_right}" alt="right arrow icon" /></a>
   </div>
  </div>
  <!-- /first line -->
  <!-- second line -->
  <div class="row">
   <div class="two wide column">
    Details:
   </div>
   <!-- preview image -->
   <div class="ten wide column">
    <div class="ui segment">
     <div class="ui active inverted dimmer">
      <div class="ui text inverted loader">Loading</div>
     </div>
     <div class="ui center aligned image">
      <img name="preview_image" data-src="{$img_url}" data-image-id="{$img_id}" />
     </div>
    </div>
   </div>
   <!-- /preview image -->
   <div class="four wide column">
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
   <!-- /second line -->
 </div>
 </div>
</div>
