<h2 class="ui header">Ready for splitting!</h2>
<div class="ui segment">
 <h3 class="ui header">Summary for {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}</h3>
 <div class="ui segment">
  <p>Filename: {$item->getFileName()}</p>
 </div>
 <div class="ui segment">
  <p>Click on "Finish" to start splitting this document.</p>
 </div>
</div>
<button class="ui button exit" data-content="Exit splitting and close this window">Exit</button>
<button class="ui button split" data-content="Invoke splitting process" data-action-title="Splitting {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem">Finish</button>
<script type="text/javascript"><!--
if (typeof documents === 'undefined' || !documents instanceof Array) {
   throw 'Lost pages information!';
}

$('.ui.button.exit').click(function () {
   if (typeof splitter_wnd === 'undefined') {
      throw 'Have no reference to the modal window!';
      return false;
   }
   splitter_wnd.modal('hide');
   delete documents;
   delete splitter_wnd;
});
$('.ui.button.split').click(function () {
   if (typeof splitter_wnd === 'undefined') {
      throw 'Have no reference to the modal window!';
      return false;
   }
   splitter_wnd.modal('hide');
   delete splitter_wnd;

   var split_wnd = show_modal('progress', {
      header : title,
      icon : 'wait icon',
      hasActions : false,
      content : 'Please wait a moment.',
      onShow : rpc_fetch_jobstatus()
   });

   progressbar = split_wnd.find('.description .ui.indicating.progress');

   if (typeof progressbar === 'undefined') {
      throw 'Can not find the progress bar in the modal window!';
      return false;
   }

   msg_body = new Object;
   msg_body.id = '{$item->getId()}';
   msg_body.guid = '{$item->getGuid()}';
   msg_body.model = 'queueitem';
   msg_body.documents = JSON.stringify(documents);

   var msg = new ThalliumMessage;
   msg.setCommand('split-request');
   msg.setMessage(msg_body);

   if (!mbus.add(msg)) {
      throw 'ThalliumMessageBus.add() returned false!';
   }

   mbus.subscribe('split-replies-handler', 'split-reply', function (reply) {
      if (!reply) {
         throw 'reply is empty!';
         return false;
      }
      if (typeof progressbar === 'undefined') {
         throw 'Have no reference to the progressbar!';
         return false;
      }
      if (typeof split_wnd === 'undefined') {
         throw 'Have no reference to the split_wnd!';
         return false;
      }

      var newData = new Object;

      if (reply.value && (value = reply.value.match(/([0-9]+)%$/))) {
         newData.percent = value[1];
      }
      if (reply.body) {
         newData.text = {
            active : reply.body,
            success: reply.body
         };
      }
      if (!progressbar.hasClass('active')) {
         progressbar.addClass('active');
      }
      progressbar.progress(newData);
      split_wnd.modal('refresh');

      if (reply.value != '100%') {
         return true;
      }

      progressbar.removeClass('active').addClass('success');

      split_wnd.modal('hide');
      delete documents;
      delete split_wnd;
      mbus.unsubscribe('split-replies-handler');

      location.reload();
      return true;

   }.bind(this));

   if (!mbus.send()) {
      throw 'ThalliumMessageBus.send() returned false!';
   }

   return true;
});

$('button.ui.button').popup({
   lastResort: true,
});
--></script>
