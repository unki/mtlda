<script type="text/javascript"><!--
if (typeof documents === 'undefined' || !documents instanceof Array) {
   throw 'Lost pages information!';
}

doc_obj = new Object;

documents.forEach(function (pages, document_no) {
   doc_obj[document_no] = pages.join(',');
});

msg_body = new Object;
msg_body.id = '{$item->getId()}';
msg_body.guid = '{$item->getGuid()}';
msg_body.model = 'queueitem';
msg_body.documents = JSON.stringify(doc_obj);

var msg = new ThalliumMessage;
msg.setCommand('split-request');
msg.setMessage(msg_body);

if (!mbus.add(msg)) {
   throw 'ThalliumMessageBus.add() returned false!';
}

if (typeof subscribed === 'undefined') {
   mbus.subscribe('split-replies-handler', 'split-reply', function (reply) {
      if (!reply) {
         throw 'reply is empty!';
         return false;
      }
      if (!splitter_wnd) {
         throw 'Have no reference to the modal window!';
         return false;
      }

      if (reply.value != '100%') {
         return true;
      }

      console.log('splitting!');
      return true;
   }.bind(this));
   subscribed = 1;
}

if (!mbus.send()) {
   throw 'ThalliumMessageBus.send() returned false!';
}
--></script>
