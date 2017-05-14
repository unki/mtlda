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
<div class="ui long fullscreen modal" id="archiver_modal_window_template">
 <div class="ui dimmer"></div>
 <i class="close icon bubble" data-content="Exit archiver and close window"></i>
 <div class="header window title">Archiving</div>
 <div class="image content">
  <div class="image">
   <i class="archive icon"></i>
  </div>
  <div class="description">
   <div class="ui steps archiver">
    <a class="active step bubble" id="archiver_step_1" data-content="Change to step 1 (Document)">
     <i class="configure icon"></i>
     <div class="content">
      <div class="title">Document</div>
      <div class="description">Enter document details.</div>
     </div>
    </a>
    <a class="disabled step bubble" id="archiver_step_2" data-content="Change to step 2 (Meta)">
     <i class="calendar icon"></i>
     <div class="content">
      <div class="title">Meta</div>
      <div class="description">Keywords, expiration, etc.</div>
     </div>
    </a>
    <a class="disabled step bubble" id="archiver_step_3" data-content="Change to step 3 (Description)">
     <i class="comments icon"></i>
     <div class="content">
      <div class="title">Description</div>
      <div class="description">Describe document.</div>
     </div>
    </a>
    <a class="disabled step bubble" id="archiver_step_4" data-content="Change to step 4 (Archive)">
     <i class="info icon"></i>
     <div class="content">
      <div class="title">Archive.</div>
     </div>
    </a>
   </div>
   <div id="archiver_content" class="ui segment">
      Loading...
    <div class="ui active inverted dimmer">
     <div class="ui loader"></div>
    </div>
   </div>
  </div>
 </div>
</div>
