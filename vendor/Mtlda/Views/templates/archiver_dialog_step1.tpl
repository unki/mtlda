<form class="ui form" id="queue_title" data-target="queue_title">
 <div class="inline fields">
  <div class="field">
   <label>Document Title</label>
   <input type="text" name="queue_title" value="{if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()|regex_replace:'/\.([a-zA-Z]+)$/':''|replace:'_':' '}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_title">
  </div>
  <div class="field">
   <button class="circular ui icon button save {if !$item->hasTitle()}red shape{/if}" type="submit"><i class="save icon"></i></button>
   <button class="circular ui icon button cancel" type="reset"><i class="cancel icon"></i></button>
   <button class="circular ui icon button" onclick="$('input[name=queue_title]').val($('input[name=queue_file_name]').val()); return false;" title="Copy filename"><i class="copy icon"></i></button>
  </div>
 </div>
</form>
<form class="ui form" id="queue_file_name" data-target="queue_file_name">
 <div class="inline fields">
  <div class="required field">
   <label>Filename</label>
   <input type="text" name="queue_file_name" value="{$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_file_name">
  </div>
  <div class="field">
   <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
   <button class="circular ui icon button cancel" type="reset"><i class="cancel icon"></i></button>
   <button class="circular ui icon button" onclick="$('input[name=queue_file_name]').val($('input[name=queue_title]').val()); return false;" title="Copy filename"><i class="copy icon"></i></button>
  </div>
 </div>
</form>
<button class="ui button" data-modal-title="Archive {$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" onclick="archiver_window($(this), {$next_step}); return false;">Next</button>
<script type="text/javascript"><!--
$('#archiver_modal_window form.ui.form').on('submit', function () {
   rpc_object_update($(this), function (element, data) {
      if (element === undefined || !element) {
         throw 'lost element!';
         return false;
      }
      if (data != "ok") {
         return true;
      }
      var savebutton = element.find('button.save');
      savebutton.transition('tada').removeClass('red shape');
      return true;
   });
   return false;
});
$('#archiver_modal_window form.ui.form input').on('input', function () {
   var form = $(this).closest('form');
   if (form === undefined) {
      return true;
   }
   var savebutton = form.find('button.save');
   if (savebutton === undefined) {
      return true;
   }
   if (!savebutton.hasClass('red shape')) {
      savebutton.addClass('red shape');
      savebutton.transition('bounce');
   }
   return true;
});
--></script>
