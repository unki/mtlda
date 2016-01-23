<div class="ui long fullscreen modal" id="splitter_modal_window_template">
 <i class="close icon"></i>
 <div class="header window title">Splitting</div>
 <div class="image content">
  <div class="image">
   <i class="expand icon"></i>
  </div>
  <div class="description">
   <div class="ui splitter steps">
    <a class="active step" id="splitter_step_1" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
     <i class="configure icon"></i>
     <div class="content">
      <div class="title">Preview</div>
      <div class="description">prepare document.</div>
     </div>
    </a>
    <a class="disabled step" id="splitter_step_2" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
     <i class="calendar icon"></i>
     <div class="content">
      <div class="title">Pages</div>
      <div class="description">Select pages.</div>
     </div>
    </a>
    <a class="disabled step" id="splitter_step_3" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
     <i class="comments icon"></i>
     <div class="content">
      <div class="title">Summary</div>
      <div class="description">changes overview.</div>
     </div>
    </a>
    <a class="disabled step" id="splitter_step_4" data-modal-title="unknown" data-id="unknown" data-guid="unknown" data-model="queueitem">
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
$('.ui.splitter.steps a.step').on('click', ':not(.disabled)', function () {
   link = eval($(this).closest('a.step'));
   id = $(link).attr('id');
   if (typeof id === 'undefined' || id == '') {
      id = 'splitter_step_1';
   }
   step_no = id.match(/^splitter_step_(\d)$/);
   if (typeof step_no === 'undefined' || typeof step_no[1] === 'undefined' || step_no[1] == '') {
      return false;
   }
   splitter_window(step_no[1], '{$item->getGuid()}');
});
--></script>
