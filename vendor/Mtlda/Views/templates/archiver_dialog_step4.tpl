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
<button class="ui button exit" data-content="Exit archiving and close this window">Exit</button>
<button class="ui button archive" data-content="Invoke archiving process" data-action-title="Archiving {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem">Finish</button>
<script type="text/javascript"><!--
$('.ui.button.exit').click(function () {
   $(this).popup('hide');
   if (!archiver_wnd) {
      throw 'Have no reference to the modal window!';
      return false;
   }
   archiver_wnd.modal('hide');
});
$('.ui.button.archive').click(function () {
   $(this).popup('hide');
   if (!archiver_wnd) {
      throw 'Have no reference to the modal window!';
      return false;
   }
   archiver_wnd.modal('hide');
   elements = new Array;
   elements.push($(this));
   rpc_object_archive(elements, function () {
         if (typeof elements === 'undefined') {
            return true;
         }
         elements.forEach(function (value) {
            if (typeof (id = $(this).attr('data-id')) === 'undefined') {
               return true;
            }
            $('tr#queue_item_'+ id).hide(400, function () {
               $(this).remove();
            });
         });
         return true;
   });
});

$('button.ui.button').popup();
--></script>
