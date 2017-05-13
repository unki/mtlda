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
<form class="ui form" id="queue_title" data-target="queue_title">
 <div class="two fields">
  <div class="field">
   <label>Document Title:</label>
   <input type="text" name="queue_title" value="{if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()|regex_replace:'/\.([a-zA-Z]+)$/':''|replace:'_':' '}{/if}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_title">
  </div>
  <div class="field">
   <label>&nbsp;</label>
   <button class="circular ui icon button save {if !$item->hasTitle()}red shape{/if}" type="submit" data-content="Save title"><i class="save icon"></i></button>
   <button class="circular ui icon button cancel" type="reset" data-content="Reset title"><i class="cancel icon"></i></button>
   <button class="circular ui icon button" onclick="$('input[name=queue_title]').val($('input[name=queue_file_name]').val()); return false;" data-content="Copy filename to title"><i class="copy icon"></i></button>
  </div>
 </div>
</form>
<br />
<form class="ui form" id="queue_file_name" data-target="queue_file_name">
 <div class="two fields">
  <div class="required field">
   <label>Filename:</label>
   <input type="text" name="queue_file_name" value="{$item->getFileName()}" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-action="update" data-model="queueitem" data-key="queue_file_name">
  </div>
  <div class="field">
   <label>&nbsp;</label>
   <button class="circular ui icon button save" type="submit" data-content="Save filename"><i class="save icon"></i></button>
   <button class="circular ui icon button cancel" type="reset" data-content="Reset filename"><i class="cancel icon"></i></button>
   <button class="circular ui icon button" onclick="$('input[name=queue_file_name]').val($('input[name=queue_title]').val()); return false;" data-content="Copy title to filename"><i class="copy icon"></i></button>
  </div>
 </div>
</form>
<br />
<button class="ui button" data-content="Continue to next step" onclick="$(this).addClass('loading'); $(this).popup('hide'); archiver_window('{$item->getGuid()}', {$next_step}); return false;">Next</button>
<script type="text/javascript"><!--

'use strict';

$('#archiver_modal_window_{$item->getGuid()} form.ui.form').on('submit', function () {
   rpc_object_update($(this), function (element, data) {
      if (typeof element === 'undefined' || !element) {
         throw new Error('lost element!');
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

$('#archiver_modal_window_{$item->getGuid()} form.ui.form input').on('input', function () {
   var form = $(this).closest('form');
   if (typeof form === 'undefined') {
      return true;
   }
   var savebutton = form.find('button.save');
   if (typeof savebutton === 'undefined') {
      return true;
   }
   if (!savebutton.hasClass('red shape')) {
      savebutton.addClass('red shape');
      savebutton.transition('bounce');
   }
   return true;
});
$('button.ui.button').popup({
   exclusive: true,
   lastResort: true,
});
--></script>
