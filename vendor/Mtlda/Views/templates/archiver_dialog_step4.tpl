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
<h2 class="ui header">Ready for archiving!</h2>
<div class="ui segment">
 <h3 class="ui header">Summary for {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}</h3>
 <div class="ui segment">
  <p>Filename: {$item->getFileName()}</p>
  <p>Keywords: {$item->getAssignedKeywords()}</p>
 </div>
 <div class="ui segment">
  <h5 class="ui header">Description</h5>
{if $item->hasDescription()}
  <p>{$item->getDescription()}</p>
{else}
  <p>No description available.</p>
{/if}
 </div>
 <div class="ui segment">
  <p>Click on "Finish" to start archiving this document.</p>
 </div>
</div>
<button class="ui button exit" data-content="Exit archiving and close this window">Exit</button>
<button class="ui button archive" data-content="Invoke archiving process" data-action-title="Archiving {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="queueitem">Finish</button>
<script type="text/javascript"><!--

'use strict';

var substore, archiver_wnd;

if (!(substore = store.getSubStore('archiver_{$item->getGuid()}'))) {
    throw new Error('failed to get spitter ThalliumStore!');
}

if (substore.has('archiver_wnd')) {
   archiver_wnd = substore.get('archiver_wnd')
}

$('.ui.button.exit').click(function () {
   $(this).popup('hide');
   if (typeof archiver_wnd === 'undefined' || !archiver_wnd) {
      throw new Error('Have no reference to the modal window!');
      return false;
   }
   archiver_wnd.modal('hide');
});
$('.ui.button.archive').click(function () {
   $(this).popup('hide');
   if (typeof archiver_wnd === 'undefined' || !archiver_wnd) {
      throw new Error('Have no reference to the modal window!');
      return false;
   }
   archiver_wnd.modal('hide');
   var elements = new Array;
   elements.push($(this));
   rpc_object_archive(elements, function () {
         if (typeof elements === 'undefined') {
            return true;
         }
         elements.forEach(function (element) {
            var id;
            if (typeof (id = $(element).attr('data-id')) === 'undefined') {
               return true;
            }
            $('tr#queue_item_'+ id).hide(400, function () {
               $(this).remove();
            });
         });
         return true;
   });
});

$('button.ui.button').popup({
   exclusive: true,
   lastResort: true,
});
--></script>
