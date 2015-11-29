<form class="ui form" id="queue_title" title="Archive {$item->getFileName()}" data-target="queue_title">
 <div class="inline fields">
  <div class="field">
   <label>Document Title</label>
   <input type="text" name="queue_title" value="{if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_title">
  </div>
  <div class="field">
   <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
  </div>
  <div class="field">
   <button class="circular ui icon button cancel" type="reset"><i class="cancel icon"></i></button>
  </div>
 </div>
</form>
<form class="ui form" id="queue_file_name" title="Archive {$item->getFileName()}" data-target="queue_file_name">
 <div class="inline fields">
  <div class="required field">
   <label>Filename</label>
   <input type="text" name="queue_file_name" value="{$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_file_name">
  </div>
  <div class="field">
   <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
  </div>
  <div class="field">
   <button class="circular ui icon button cancel" type="reset"><i class="cancel icon"></i></button>
  </div>
 </div>
</form>
<button class="ui button" title="Archive {$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" onclick="archiver_window($(this), {$next_step}); return false;">Next</button>
<script type="text/javascript"><!--
$('.archiver.modal form.ui.form').on('submit', function () {
   rpc_object_update($(this));
   return false;
});
--></script>
