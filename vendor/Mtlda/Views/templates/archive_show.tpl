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
    <div class="two wide column">Versions:</div>
    <div class="fourteen wide column">
     <div class="ui very relaxed divided selection list">
      <div class="item">
       <i class="file text icon"></i>
       <div class="content">
        <div class="header">
         <a href="{get_url page=document mode=show id=$item_safe_link file=$item->document_file_name}">{$item->document_file_name}</a>
         <a class="scan document" data-id="{$item->document_idx}" data-guid="{$item->document_guid}" data-title="{$item->document_title}"><i class="find icon"></i></a>
        </div>
        <div class="description">Original document (imported {$item->document_time|date_format:"%Y.%m.%d %H:%M"})<br /><br /><a class="sign document" data-id="{$item->document_idx}" data-guid="{$item->document_guid}" data-title="{$item->document_title}"><i class="protect icon"></i>Click to digitally sign document</a>.</div>
       </div>
      </div>
{list_versions}
     </div>
    </div>
   </div>
   <div class="row">
    <div class="two wide column">&nbsp;</div>
    <div class="fourteen wide column">
     <div class="ui toggle checkbox" name="document_custom_date_checkbox">
      <input type="checkbox" name="use_document_custom_date" {if $item->hasCustomDate()}checked{/if} />
      <label>Assign custom date to document.</label>
     </div><br /><br />
     <form id="document_custom_date_form" class="ui form" data-id="{$item->document_idx}" data-guid="{$item->document_guid}" data-target="document_custom_date" onsubmit="return false;" style="{if !$item->hasCustomDate()}display: none;{/if}">
      <div class="fields">
       <div class="field ui input">
        <input type="text" name="document_custom_date" value="{$item->document_custom_date}" data-action="update" data-model="document" data-key="document_custom_date" data-id="{$item->document_idx}" />
       </div>
       <div class="field">
        <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
       </div>
       <div class="field">
        <button class="circular ui icon button cancel"><i class="cancel icon"></i></button>
       </div>
      </div>
      <div id="document_custom_date_picker"></div>
     </form>
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
       <div class="ui fluid search dropdown multiple selection" id="keyword_dropdown">
        <input type="hidden" name="assigned_keywords" value="{$assigned_keywords}">
        <i class="dropdown icon"></i>
        <input class="search">
        <div class="default text">No keywords assigned.</div>
        <div class="menu">
{foreach $keywords as $keyword}
         <div class="item" data-value="{$keyword->keyword_idx}" data-text="{$keyword->keyword_name}">{$keyword->keyword_name}</div>
{/foreach}
        </div>
       </div>
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
       <label>Description:</label>
       <textarea>{$item->document_description}</textarea>
      </div>
      <button class="circular small ui icon save button" type="submit" data-target="document_description" data-type="document_description" data-id="{$item->document_idx}">
       <i class="save icon"></i>
      </button>
     </form>
    </div>
   </div>
{if isset($pdf_indexing_is_enabled) && $pdf_indexing_is_enabled}
   <div class="row">
    <div class="twelve wide column">
     <div class="ui container segment">
      <div class="ui header">Document Properties:</div>
      <div class="ui grid">
       <div class="row">
        <div class="five wide column">Filename:</div>
        <div class="eleven wide column">{$item->document_file_name}</div>
       </div>
       <div class="row">
        <div class="five wide column">Size:</div>
        <div class="eleven wide column">{get_humanreadable_filesize size=$item->document_file_size}</div>
       </div>
{document_properties}
       <div class="row">
        <div class="five wide column">{$property->getDocumentProperty()}:</div>
        <div class="eleven wide column">{$property->getDocumentValue()}</div>
       </div>
{/document_properties}
      </div>
     </div>
    </div>
   </div>
{/if}
  </div>
 </div>
</div>
<script type="text/javascript"><!--{literal}

$(document).ready(function() {

   $('#keyword_dropdown').dropdown({
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
      var input = $('.ui.form.keywords input[name=assigned_keywords]');
      var values = input.val();

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

   var curdate = $('#document_custom_date_form input[type="text"][name="document_custom_date"]').val();
   if (!curdate || curdate == '0000-00-00') {
      curdate = null;
   }

   $('#document_custom_date_picker').datepicker({
      defaultDate: curdate,
      changeMonth: true,
      changeYear: true,
      numberOfMonths: 1,
      dateFormat: 'yy-mm-dd',
      showOtherMonths: true,
      showWeek: true,
      selectOtherMonths: true,
      showButtonPanel: true,
      firstDay: 1,
      altFormat: 'yy-mm-dd',
      altField: 'input[type="text"][name="document_custom_date"]',
      onSelect: function () {
         curval = $('#document_custom_date_form input[type="text"][name="document_custom_date"]').val();
         newval = $(this).datepicker('getDate');
         if (curval && newval && curval == newval) {
            return true;
         }
         $('#document_custom_date_form input[type="text"][name="document_custom_date"]').trigger('input');
      }
   });

   $('.ui.toggle.checkbox[name="document_custom_date_checkbox"]').checkbox({
      onChange : function () {

         if ($('.ui.toggle.checkbox[name="document_custom_date_checkbox"]').checkbox('is unchecked')) {
            $('#document_custom_date_form').transition('fly up');
            $('#document_custom_date_form input[type="text"][name="document_custom_date"]').val('0000-00-00');
            $('#document_custom_date_form').trigger('submit');
            return true;
         }

         var curdate = $('#document_custom_date_form input[type="text"][name="document_custom_date"]').val();
         if (!curdate || curdate == '' || curdate == '0000-00-00') {
            $('#document_custom_date_picker').datepicker('setDate', new Date());
         }
         $('#document_custom_date_form input').trigger('input');
         $('#document_custom_date_form').transition('fly down');
         return true;
      }
   });

   $('#document_custom_date_form input').on('input', function() {
      savebutton = $('#document_custom_date_form button.save');
      if(!savebutton.hasClass('red shape')) {
         savebutton.addClass('red shape');
         savebutton.transition('bounce');
      }
   });

   $('#document_custom_date_form').on('submit', function() {
      rpc_object_update($(this), function (data) {
         if(data != "ok") {
            return;
         }
         $('#document_custom_date_form button.save')
            .transition('tada')
            .removeClass('red shape');
            return;
      });
   });
});
{/literal}--></script>
<div class="ui signer scanner modal">
 <i class="close icon"></i>
 <div class="header">header</div>
 <div class="image content">
  <div class="image">
   <i class="icon"></i>
  </div>
  <div class="description" style="width: 100%;">
   <p style="display: none">description</p>
   <div class="ui indicating progress active" id="progressbar">
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
