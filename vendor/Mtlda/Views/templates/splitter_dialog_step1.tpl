<div class="ui segment">
  <div class="ui active inverted dimmer preview waiting">
    <div class="ui text loader">Creating document previews.<br />Please wait a moment.</div>
  </div>
  <br /><br /><br /><br />
  <button class="ui button continue" onclick="splitter_window(2, '{$item->getGuid()}');" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-modal-title="Split {if $item->hasTitle}{$item->getTitle()}{else}{$item->getFileName()}{/if}">Continue</button>
  <br /><br /><br /><br />
</div>
<script type="text/javascript"><!--
$(document).ready(function () {

    var id, guid, model;

    if (!(substore = store.getSubStore('splitter_{$item->getGuid()}'))) {
        throw 'failed to get spitter ThalliumStore!';
        return false;
    }

    if (!(id = substore.get('id'))) {
       throw 'Failed to get "id" value from store!';
       return false;
    }

    if (!(guid = substore.get('guid'))) {
       throw 'Failed to get "guid" value from store!';
       return false;
    }

    if (!(model = substore.get('model'))) {
       throw 'Failed to get "model" value from store!';
       return false;
    }

    msg_body = new Object;
    msg_body.id = safe_string(id);
    msg_body.guid = safe_string(guid);
    msg_body.model = safe_string(model);

    var msg = new ThalliumMessage;
    msg.setCommand('preview-request');
    msg.setMessage(msg_body);

    if (!mbus.add(msg)) {
        throw 'ThalliumMessageBus.add() returned false!';
        return false;
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
        throw 'ThalliumMessageBus.send() returned false!';
        return false;
    }

    return true;
});
--></script>
