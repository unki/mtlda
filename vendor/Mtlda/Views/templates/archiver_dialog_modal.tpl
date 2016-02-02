<div class="ui long fullscreen modal" id="archiver_modal_window_template">
 <div class="ui dimmer"></div>
 <i class="close icon" data-content="Exit archiver and close window"></i>
 <div class="header window title">Archiving</div>
 <div class="image content">
  <div class="image">
   <i class="archive icon"></i>
  </div>
  <div class="description">
   <div class="ui steps archiver">
    <a class="active step" id="archiver_step_1" data-content="Change to step 1 (Document)">
     <i class="configure icon"></i>
     <div class="content">
      <div class="title">Document</div>
      <div class="description">Enter document details.</div>
     </div>
    </a>
    <a class="disabled step" id="archiver_step_2" data-content="Change to step 2 (Meta)">
     <i class="calendar icon"></i>
     <div class="content">
      <div class="title">Meta</div>
      <div class="description">Keywords, expiration, etc.</div>
     </div>
    </a>
    <a class="disabled step" id="archiver_step_3" data-content="Change to step 3 (Description)">
     <i class="comments icon"></i>
     <div class="content">
      <div class="title">Description</div>
      <div class="description">Describe document.</div>
     </div>
    </a>
    <a class="disabled step" id="archiver_step_4" data-content="Change to step 4 (Archive)">
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
