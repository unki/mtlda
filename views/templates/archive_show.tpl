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
<h1 class="ui header"><i class="file text icon"></i>{$item->document_file_name}</h1>
<div class="ui grid">
 <div class="row">
  <div class="column">Filename:</div>
  <div class="fifteen wide column">{$item->document_file_name}</div>
 </div>
 <div class="row">
  <div class="column">Size:</div>
  <div class="fifteen wide column">{$item->document_file_size}</div>
 </div>
 <div class="row">
  <div class="column">Versions:</div>
  <div class="eight wide column">
   <div class="ui relaxed divided list">
    <div class="item">
     <i class="file text icon"></i>
     <div class="content">
      <a class="header" href="{get_url page=document mode=show id=$item_safe_link file=$item->document_file_name}">{$item->document_file_name}</a>
      <div class="description">Original document. Click <a href="{get_url page=document mode=sign id=$item_safe_link}">here</a> to digitally sign document.</div>
     </div>
    </div>
{if $item_versions}
{foreach $item_versions as $version}
 {assign var='safe_link' value="document-`$version->document_idx`-`$version->document_guid`"}
    <div class="item">
     <i class="file text icon"></i>
     <div class="content">
      <form id="document_edit_{$version->document_idx}" class="ui form filename" style="display: none;" onsubmit="return false;">
       <div class="fields">
        <div class="field small ui input">
         <input type="text" name="document_file_name[{$version->document_idx}]" value="{$version->document_file_name}" data-action="update" />
        </div>
        <div class="field">
         <button class="circular small ui icon button update document" data-target="document_file_name[{$version->document_idx}]" data-type="document" data-id="{$version->document_idx}" data-value="{$version->document_file_name}"><i class="save icon"></i></button>
        </div>
        <div class="field">
         <button class="circular small ui icon button cancel" data-target="document_file_name[{$version->document_idx}]" data-type="document" data-id="{$version->document_idx}" data-value="{$version->document_file_name}"><i class="cancel icon"></i></button>
        </div>
       </div>
      </form>
      <div id="document_show_{$version->document_idx}">
       <div class="header" id="document_label_{$version->document_idx}">
        <a href="{get_url page=document mode=show id=$safe_link file=$version->document_file_name}">{$version->document_file_name}</a>&nbsp;
        <a class="document update" data-type="document" data-id="{$version->document_idx}" data-value="{$version->document_file_name}"><i class="edit icon" ></i></a>
        <a href="{get_url page=document mode=delete id=$safe_link}"><i class="delete icon"></i></a>
       </div>
       <div class="description">Version {$version->document_version}</div>
      </div>
     </div>
    </div>
{/foreach}
{/if}
   </div>
  </div>
 </div>
 <div class="row">
  <div class="column">Keywords:</div>
  <div class="eight wide column">
   <form id="document_keywordѕ" class="ui form keywords" data-id="{$item->document_idx}" data-guid="{$item->document_guid}" onsubmit="return false;">
    <div class="fields">
     <div class="field">
      <select class="ui fluid search labeled dropdown" name="assigned_keywords" multiple="">
{foreach $keywords as $keyword}
    <option value="{$keyword->keyword_idx}" {if in_array($keyword->keyword_idx, $assigned_keywords)} selected="selected"{/if}>{$keyword->keyword_name}</option>
{/foreach}
      </select>
     </div>
     <div class="field">
      <button class="circular small ui icon button" type="submit" data-target="document_keywords" data-type="selected_keywords" data-id="{$item->document_idx}"><i class="save icon"></i></button>
     </div>
    </div>
   </form>
  </div>
 </div>
</div>
<script type="text/javascript"><!--{literal}

$(document).ready(function() {

   $('a.document.update, button.cancel').click(function(element) {
      type = $(this).attr('data-type');
      id = $(this).attr('data-id');
      value = $(this).attr('data-value');
      if (!type || !id || !value) {
         console.log('incomplete: ' + type + ', ' + id + ', ' + value);
         return
      }
      $('#' + type + '_show_' + id).toggle();
      $('#' + type + '_edit_' + id).toggle();
   });

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
         $('.ui.form.keywords .field button')
            .addClass('red shape')
            .transition('bounce');
      }
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
                  $('.ui.form.keywords .field button').removeClass('red shape');
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
