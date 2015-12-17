<div class="ui long fullscreen modal queue splitter">
 <i class="close icon"></i>
 <div class="header window title">Archiving</div>
 <div class="image content">
  <div class="image">
   <i class="expand icon"></i>
  </div>
  <div class="description">
   <div class="ui steps">
    <a class="active step" id="step_1" title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
     <i class="configure icon"></i>
     <div class="content">
      <div class="title">Preview</div>
      <div class="description">prepare document.</div>
     </div>
    </a>
    <a class="disabled step" id="step_2" title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
     <i class="calendar icon"></i>
     <div class="content">
      <div class="title">Pages</div>
      <div class="description">Select pages.</div>
     </div>
    </a>
    <a class="disabled step" id="step_3" title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
     <i class="comments icon"></i>
     <div class="content">
      <div class="title">Summary</div>
      <div class="description">changes overview.</div>
     </div>
    </a>
    <a class="disabled step" id="step_4" title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
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
<script type="text/javascript"><!--
$('.ui.steps a.step').on('click', ':not(.disabled)', function () {
   link = eval($(this).closest('a.step'));
   id = $(link).attr('id');
   if (id === undefined || id == '') {
      id = 'step_1';
   }
   step_no = id.match(/^step_(\d)$/);
   if (step_no === undefined || step_no[1] === undefined || step_no[1] == '') {
      return false;
   }
   splitter_window($(link), step_no[1]);
});
--></script>
