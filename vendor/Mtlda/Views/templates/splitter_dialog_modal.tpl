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
<div class="ui long fullscreen modal" id="splitter_modal_window_template">
 <i class="close icon" data-content="Exit splitter and close window"></i>
 <div class="header window title">Splitting</div>
 <div class="image content">
  <div class="image">
   <i class="expand icon"></i>
  </div>
  <div class="description">
   <div class="ui steps splitter">
    <a class="active step" id="splitter_step_1" data-content="Change to step 1 (Preview)">
     <i class="configure icon"></i>
     <div class="content">
      <div class="title">Preview</div>
      <div class="description">prepare document.</div>
     </div>
    </a>
    <a class="disabled step" id="splitter_step_2" data-content="Change to step 2 (Pages)">
     <i class="calendar icon"></i>
     <div class="content">
      <div class="title">Pages</div>
      <div class="description">Select pages.</div>
     </div>
    </a>
    <a class="disabled step" id="splitter_step_3" data-content="Change to step 3 (Summary)">
     <i class="comments icon"></i>
     <div class="content">
      <div class="title">Summary</div>
      <div class="description">changes overview.</div>
     </div>
    </a>
    <a class="disabled step" id="splitter_step_4" data-content="Change to step 4 (Split)">
     <i class="expand icon"></i>
     <div class="content">
      <div class="title">Split.</div>
     </div>
    </a>
   </div>
   <div id="splitter_content" class="ui segment">
      Loading...
    <div class="ui active inverted dimmer">
     <div class="ui loader"></div>
    </div>
   </div>
  </div>
 </div>
</div>
