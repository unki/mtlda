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
<h1 class="ui header">{$item->document_file_name}</h1>
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
  <div class="fifteen wide column">
   <div class="ui list">
    <div class="item"><a href="{get_url page=document mode=show id=$item_safe_link}">v1 - Original imported document</a></div>
    <div class="item"><a href="{get_url page=document mode=sign id=$item_safe_link}">Sign</a></div>
{if $item_versions}
    <div class="item ui divider"></div>
{foreach $item_versions as $version}
 {assign var='safe_link' value="document-`$version->document_idx`-`$version->document_guid`"}
    <div class="item">
     <a href="{get_url page=document mode=show id=$safe_link}">v{$version->document_version} - {$version->document_file_name}</a>&nbsp;
     <a href="{get_url page=document mode=delete id=$safe_link}">Delete</a>
    </div>
{/foreach}
{/if}
   </div>
  </div>
 </div>
 <div class="row">
  <div class="column">Keywords:</div>
  <div class="eight wide column">
   <form id="document_keywordѕ" class="ui form" onsubmit="return false;">
    <div class="fields">
     <div class="field">
      <select class="ui fluid search dropdown" name="assigned_keywords" multiple="">
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
$('.ui.fluid.search.dropdown').dropdown({
   allowAdditions: false,
   apiSettings: {
      method : 'POST',
      url    : '{$keywords_rpc_url}',
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
      $('.ui.form .field button')
         .addClass('red shape')
         .transition('bounce');
   }
});
$('.ui.form').on('submit', function() {
   console.log($('.ui.form select[name=assigned_keywords] option').filter(':selected'));
   var selected = $('.ui.form select[name=assigned_keywords] option').filter(':selected');
   var values = [];
   selected.each(function(index, element) {
      values.push(element.value);
   });
   console.log(values);
   return

    $.ajax({
        type: "POST",
        url: "rpc.html",
        data: ({
            type : 'rpc',
            action : 'save-keywords',
            id : del_id
        }),
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(data){
            if(data == "ok") {
               $('.ui.form .field button').removeClass('red shape');
               return;
            }
            alert('Server returned: ' + data + ', length ' + data.length);
            return;
        }
    });

    return true;
});
{/literal}--></script>
