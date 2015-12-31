<div class="ui long fullscreen modal" id="archiver_modal_window">
 <div class="ui inverted dimmer"></div>
 <i class="close icon" data-content="Exit archiver and close window"></i>
 <div class="header window title">Archiving</div>
 <div class="image content">
  <div class="image">
   <i class="archive icon"></i>
  </div>
  <div class="description">
   <div class="ui steps archiver">
    <a class="active step" id="archiver_step_1" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem" data-content="Change to step 1 (Document)">
     <i class="configure icon"></i>
     <div class="content">
      <div class="title">Document</div>
      <div class="description">Enter document details.</div>
     </div>
    </a>
    <a class="disabled step" id="archiver_step_2" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem" data-content="Change to step 2 (Meta)">
     <i class="calendar icon"></i>
     <div class="content">
      <div class="title">Meta</div>
      <div class="description">Keywords, expiration, etc.</div>
     </div>
    </a>
    <a class="disabled step" id="archiver_step_3" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem" data-content="Change to step 3 (Description)">
     <i class="comments icon"></i>
     <div class="content">
      <div class="title">Description</div>
      <div class="description">Describe document.</div>
     </div>
    </a>
    <a class="disabled step" id="archiver_step_4" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem" data-content="Change to step 4 (Archive)">
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
<script type="text/javascript"><!--
$('.ui.archiver.steps a.step').on('click', ':not(.disabled)', function () {
   link = eval($(this).closest('a.step'));
   id = $(link).attr('id');
   if (id === undefined || id == '') {
      id = 'archiver_step_1';
   }
   step_no = id.match(/^archiver_step_(\d)$/);
   if (step_no === undefined || step_no[1] === undefined || step_no[1] == '') {
      return false;
   }
   archiver_window($(link), step_no[1]);
});

$('a.step, i.close.icon').popup();
--></script>
