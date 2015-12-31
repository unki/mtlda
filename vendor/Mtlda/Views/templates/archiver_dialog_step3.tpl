<form class="ui form" data-target="queue_description">
 <div class="field">
  <label>Document Description</label>
  <textarea name="queue_description" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_description">{$item->getDescription()}</textarea>
 </div>
 <div class="inline fields">
  <div class="field">
   <button class="circular ui icon button save" type="submit" data-content="Save description"><i class="save icon"></i></button>
   <button class="circular ui icon button cancel" type="reset" data-content="Reset description"><i class="cancel icon"></i></button>
  </div>
 </div>
</form>
<button class="ui button" data-modal-title="Archive {$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-content="Continue to next step" onclick="$(this).popup('hide'); archiver_window($(this), {$next_step}); return false;">Next</button>
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

$('#archiver_modal_window form.ui.form textarea').on('input', function () {
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

$('button.ui.button').popup();
--></script>
