<div class="ui segment">
  <div class="ui active inverted dimmer preview waiting">
    <div class="ui text loader">Creating document previews.<br />Please wait a moment.</div>
  </div>
  <br /><br /><br /><br />
  <a onclick="splitter_window($(this), 2);" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-modal-title="Split {if $item->hasTitle}{$item->getTitle()}{else}{$item->getFileName()}{/if}">Continue.</a>
  <br /><br /><br /><br />
</div>
<script type="text/javascript"><!--
$(document).ready(function () {

    msg_body = new Object;
    msg_body.id = safe_string(id);
    msg_body.guid = safe_string(guid);
    msg_body.model = safe_string(model);

    var msg = new MtldaMessage;
    msg.setCommand('preview-request');
    msg.setMessage(msg_body);

    if (!mbus.add(msg)) {
        throw 'MtldaMessageBus.add() returned false!';
        return false;
    }

    mbus.subscribe('preview-replies-handler', 'preview-reply', function (reply) {
        if (!reply) {
            throw 'reply is empty!';
            return false;
        }
        if (!wnd) {
            throw 'Have no reference to the modal window!';
            return false;
        }

        if (reply.value != '100%') {
            return true;
        }

        $('.ui.dimmer.preview.waiting').removeClass('active');
        mbus.unsubscribe('preview-replies-handler');
        return true;

    }.bind(this));

    if (!mbus.send()) {
        throw 'MtldaMessageBus.send() returned false!';
        return false;
    }

    return true;
});
--></script>
