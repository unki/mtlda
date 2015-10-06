{*
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015>  <Andreas Unterkircher>
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

<h1 class="ui block header">
 <i class="file text icon"></i>
 <div class="content">
  <div name="title" class="inline editable content" data-orig-value="{$item->document_title}">{$item->document_title}</div>
  <a name="title" class="inline editable edit link" data-inline-name="title"><i class="tiny edit icon"></i></a>
 </div>
</h1>
<div name="title" class="inline editable formsrc" style="display: none;">
<form class="ui form" onsubmit="return false;">
 <div class="fields">
  <div class="field small ui input">
   <input type="text" name="title" value="{$item->document_title}" data-action="update" data-model="document" data-key="document_title" data-id="{$item->document_idx}" />
  </div>
  <div class="field">
   <button class="circular ui big icon button inline editable save" type="submit"><i class="save icon"></i></button>
  </div>
  <div class="field">
   <button class="circular ui big icon button inline editable cancel"><i class="cancel icon"></i></button>
  </div>
 </div>
</form>
</div>

<div class="ui two column grid">

 <!-- left column -->
 <div class="column">
  <div class="ui grid">
   <div class="row">
    <div class="two wide column">Filename:</div>
    <div class="fourteen wide column">{$item->document_file_name}</div>
   </div>
   <div class="row">
    <div class="two wide column">Size:</div>
    <div class="fourteen wide column">{get_humanreadable_filesize size=$item->document_file_size}</div>
   </div>
   <div class="row">
    <div class="two wide column">Versions:</div>
    <div class="fourteen wide column">
     <div class="ui very relaxed divided selection list">
      <div class="item">
       <i class="file text icon"></i>
       <div class="content">
        <a class="header" href="{get_url page=document mode=show id=$item_safe_link file=$item->document_file_name}">{$item->document_file_name}</a>
        <div class="description">Original document (imported {$item->document_time|date_format:"%Y.%m.%d %H:%M"})<br /><br /><a class="sign document" data-id="{$item->document_idx}" data-guid="{$item->document_guid}" data-title="{$item->document_title}"><i class="protect icon"></i>Click to digitally sign document</a>.</div>
       </div>
      </div>
{list_versions}
     </div>
    </div>
   </div>
  </div>
 </div>

 <!-- right column -->
 <div class="column">
  <div class="ui grid">
   <div class="row">
    <div class="twelve wide column">
     <form id="document_keywordѕ" class="ui form keywords" data-id="{$item->document_idx}" data-guid="{$item->document_guid}" onsubmit="return false;">
      <div class="field">
       <label>Keywords:</label>
       <select class="ui fluid search labeled dropdown" name="assigned_keywords" multiple="">
{foreach $keywords as $keyword}
        <option value="{$keyword->keyword_idx}" {if in_array($keyword->keyword_idx, $assigned_keywords)} selected="selected"{/if}>{$keyword->keyword_name}</option>
{/foreach}
       </select>
      </div>
      <button class="circular small ui icon save button" type="submit" data-target="document_keywords" data-type="selected_keywords" data-id="{$item->document_idx}">
       <i class="save icon"></i>
      </button>
     </form>
    </div>
   </div>
   <div class="row">
    <div class="twelve wide column">
     <form id="document_description" class="ui form description" data-id="{$item->document_idx}" data-guid="{$item->document_guid}" onsubmit="return false;">
      <div class="field">
       <label>Description</label>
       <textarea>{$item->document_description}</textarea>
      </div>
      <button class="circular small ui icon save button" type="submit" data-target="document_description" data-type="document_description" data-id="{$item->document_idx}">
       <i class="save icon"></i>
      </button>
     </form>
    </div>
   </div>
 </div>
</div>
<script type="text/javascript"><!--{literal}

$(document).ready(function() {

   $('.ui.fluid.search.dropdown').dropdown({
      allowAdditions: false,
      apiSettings: {
         method : 'POST',
         url    : '{/literal}{$keywords_rpc_url}{literal}',
         data   : {
            type   : 'rpc',
            action : 'get-keywords'
         },
         onError : function(errorMessage, element, xhr) {
            window.alert(errorMessage + ' ' + element + ' ' + xhr);
         },
         onFailure : function(response, element)  {
            window.alert(message + ' ' + element);
         }
      },
      onChange : function(value, text, choice) {
         $('.ui.form.keywords button.save')
            .addClass('red shape')
            .transition('bounce');
      }
   });

   $('form.ui.form.description textarea').on('input', function() {
      savebutton = $('form.ui.form.description button.save');
      if(!savebutton.hasClass('red shape')) {
         savebutton.addClass('red shape');
         savebutton.transition('bounce');
      }
   });
   // bounce save icon once more if focus leaves the textarea field
   $('form.ui.form.description textarea').on('focusout', function() {
      savebutton = $('form.ui.form.description button.save');
      if(savebutton.hasClass('red shape')) {
         savebutton.transition('bounce');
      }
   });

   $('form.ui.form.description').on('submit', function() {
      var document_id = $(this).attr('data-id');
      if (!document_id) {
         window.alert('Failed to fetch data-id!');
         return false;
      }

      var document_guid = $(this).attr('data-guid');
      if (!document_guid) {
         window.alert('Failed to fetch data-guid!');
         return false;
      }

      var desc_field = $('form.ui.form.description textarea');
      if (!desc_field) {
         window.alert('Failed to find description field');
         return false;
      }

      $.ajax({
           type: "POST",
           url    : '{/literal}{$keywords_rpc_url}{literal}',
           data: ({
               type   : 'rpc',
               action : 'save-description',
               id     : document_id,
               guid   : document_guid,
               description : desc_field.val()
           }),
           error: function(XMLHttpRequest, textStatus, errorThrown) {
               alert('Failed to contact server! ' + textStatus);
           },
           success: function(data){
               if(data == "ok") {
                  $('form.ui.form.description button.save')
                     .transition('tada')
                     .removeClass('red shape');
                  return;
               }
               alert('Server returned: ' + data + ', length ' + data.length);
               return;
           }
       });

       return true;
   });

   $('.ui.form.keywords').on('submit', function() {
      var document_id = $(this).attr('data-id');
      if (!document_id) {
         window.alert('Failed to fetch data-id!');
         return false;
      }
      var document_guid = $(this).attr('data-guid');
      if (!document_guid) {
         window.alert('Failed to fetch data-guid!');
         return false;
      }
      var selected = $('.ui.form.keywords select[name=assigned_keywords] option').filter(':selected');
      var values = [];
      selected.each(function(index, element) {
         values.push(element.value);
      });

      $.ajax({
           type: "POST",
           url    : '{/literal}{$keywords_rpc_url}{literal}',
           data: ({
               type   : 'rpc',
               action : 'save-keywords',
               id     : document_id,
               guid   : document_guid,
               values : values
           }),
           error: function(XMLHttpRequest, textStatus, errorThrown) {
               alert('Failed to contact server! ' + textStatus);
           },
           success: function(data){
               if(data == "ok") {
                  $('.ui.form.keywords button.save')
                     .transition('tada')
                     .removeClass('red shape');
                  return;
               }
               alert('Server returned: ' + data + ', length ' + data.length);
               return;
           }
       });

       return true;
   });
});
{/literal}--></script>
<div class="ui signer modal">
 <i class="close icon"></i>
 <div class="header">header</div>
 <div class="image content">
  <div class="image">
   <i class="icon"></i>
  </div>
  <div class="description" style="width: 100%;">
   <p style="display: none">description</p>
   <div class="ui indicating progress active" id="signingprogress">
    <div class="bar">
     <div class="progress">0%</div>
    </div>
    <div class="label">Please wait.</div>
   </div>
  </div>
 </div>
 <div class="actions">
  <div class="two fluid ui inverted buttons">
   <div class="ui red basic inverted button deny">
    <i class="remove icon"></i>No
   </div>
   <div class="ui green basic inverted button approve">
    <i class="checkmark icon"></i>Yes
   </div>
  </div>
 </div>
</div>
