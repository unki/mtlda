<form class="ui form" data-target="queue_description">
 <div class="field">
  <label>Document Description</label>
  <textarea name="queue_description" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_description">{$item->getDescription()}</textarea>
 </div>
 <div class="inline fields">
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
