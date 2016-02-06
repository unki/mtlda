<div class="ui segment"> <div class="ui active inverted dimmer preview waiting">
    <div class="ui text loader">Creating document previews.<br />Please wait a moment.</div>
  </div>
  <br /><br /><br /><br />
  <button class="ui button continue" onclick="splitter_window('{$item->getGuid()}', 2);">Continue</button>
  <br /><br /><br /><br />
</div>
<script type="text/javascript"><!--

'use strict';

var id, guid, model, substore, msg_body, msg;

if (!(substore = store.getSubStore('splitter_{$item->getGuid()}'))) {
    throw new Error('failed to get spitter ThalliumStore!');
}

if (!(id = substore.get('id'))) {
    throw new Error('Failed to get "id" value from store!');
}

if (!(guid = substore.get('guid'))) {
    throw new Error('Failed to get "guid" value from store!');
}

if (!(model = substore.get('model'))) {
    throw new Error('Failed to get "model" value from store!');
}

msg_body = new Object;
msg_body.id = safe_string(id);
msg_body.guid = safe_string(guid);
msg_body.model = safe_string(model);

msg = new ThalliumMessage;
msg.setCommand('preview-request');
msg.setMessage(msg_body);

if (!mbus.add(msg)) {
    throw new Error('ThalliumMessageBus.add() returned false!');
}

mbus.subscribe('preview-replies-handler', 'preview-reply', function (reply) {
    if (!reply) {
        throw 'reply is empty!';
        return false;
    }

    if (reply.value != '100%') {
        return true;
    }

    $('.ui.dimmer.preview.waiting').removeClass('active');
    mbus.unsubscribe('preview-replies-handler');
    $('button.ui.button.continue').trigger('click');
    return true;

}.bind(this));

if (!mbus.send()) {
    throw new Error('ThalliumMessageBus.send() returned false!');
}
--></script>
