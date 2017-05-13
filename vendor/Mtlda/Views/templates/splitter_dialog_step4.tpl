{*
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015-2017>  <Andreas Unterkircher>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
*}
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
<button class="ui button split" data-content="Invoke splitting process" data-action-title="Splitting {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}">Finish</button>
<script type="text/javascript"><!--

'use strict';

var documents, splitter_wnd;

if (!(substore = store.getSubStore('splitter_{$item->getGuid()}'))) {
    throw new Error('failed to get spitter ThalliumStore!');
}

if (!(splitter_wnd = substore.get('splitter_wnd'))) {
   throw new Error("somehow we lost our modal window!");
}

if (substore.has('documents')) {
   documents = substore.get('documents');
}

if (typeof documents === 'undefined' || !documents instanceof Object) {
   throw new Error('Lost pages information!');
}

$('.ui.button.exit, .ui.button.split').click(function () {
    var split_wnd, progressbar;

    splitter_wnd.modal('hide');
    //delete splitter_wnd;
    if (substore.has('splitter_wnd')) {
        substore.del('splitter_wnd');
    }

    if ($(this).hasClass('exit') && !$(this).hasClass('split')) {
        //delete documents;
        if (substore.has('documents')) {
            substore.del('documents');
        }
        return true;
    }

    split_wnd = show_modal('progress', {
        header : title,
        icon : 'wait icon',
        hasActions : false,
        content : 'Please wait a moment.',
        onShow : rpc_fetch_jobstatus()
   });

   progressbar = split_wnd.find('.description .ui.indicating.progress');

   if (typeof progressbar === 'undefined') {
      throw new Error('Can not find the progress bar in the modal window!');
      return false;
   }

   msg_body = new Object;
   msg_body.id = '{$item->getIdx()}';
   msg_body.guid = '{$item->getGuid()}';
   msg_body.model = 'queueitem';
   msg_body.documents = JSON.stringify(documents);

   var msg = new ThalliumMessage;
   msg.setCommand('split-request');
   msg.setMessage(msg_body);

   if (!mbus.add(msg)) {
      throw new Error('ThalliumMessageBus.add() returned false!');
   }

   mbus.subscribe('split-replies-handler', 'split-reply', function (reply) {
      if (!reply) {
         throw new Error('reply is empty!');
         return false;
      }
      if (typeof progressbar === 'undefined') {
         throw new Error('Have no reference to the progressbar!');
         return false;
      }
      if (typeof split_wnd === 'undefined') {
         throw new Error('Have no reference to the split_wnd!');
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
      if (substore.has('documents')) {
         substore.del('documents');
      }
      if (substore.has('splitter_wnd')) {
         substore.del('splitter_wnd');
      }
      //delete documents;
      //delete split_wnd;
      mbus.unsubscribe('split-replies-handler');

      location.reload();
      return true;

   }.bind(this));

   if (!mbus.send()) {
      throw new Error('ThalliumMessageBus.send() returned false!');
   }

   return true;
});

$('button.ui.button').popup({
   exclusive: true,
   lastResort: true,
});
--></script>
