<h2 class="ui header">Ready for archiving!</h2>
<div class="ui segment">
 <h3 class="ui header">Summary for {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}</h3>
 <div class="ui segment">
  <p>Filename: {$item->getFileName()}</p>
  <p>Keywords: {$item->getAssignedKeywords()}</p>
 </div>
 <div class="ui segment">
  <h5 class="ui header">Description</h5>
  <p>{$item->getDescription()}</p>
 </div>
 <div class="ui segment">
  <p>Click on "Finish" to start archiving this document.</p>
 </div>
</div>
<button class="ui button exit" title="Close window">Exit</button>
<button class="ui button archive" title="Archive {$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem">Finish</button>
<script type="text/javascript"><!--
$('.ui.button.exit').click(function () {
   if (!wnd) {
      throw 'Have no reference to the modal window!';
      return false;
   }
   wnd.modal('hide');
});
$('.ui.button.archive').click(function () {
   if (!wnd) {
      throw 'Have no reference to the modal window!';
      return false;
   }
   wnd.modal('hide');
   elements = new Array;
   elements.push($(this));
   rpc_object_archive(elements, function () {
         if (elements === undefined) {
            return true;
         }
         elements.forEach(function (value) {
            if ((id == $(this).attr('data-id')) === undefined) {
               return true;
            }
            $('tr#queue_item_'+ id).hide(400, function () {
               $(this).remove();
            });
         });
         return true;
   });
});
--></script>
