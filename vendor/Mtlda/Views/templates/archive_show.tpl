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
  <div name="title" class="inline editable content" data-current-value="{if $item->hasTitle()}{$item->getTitle()}{/if}" data-orig-value="{if $item->hasTitle()}{$item->getTitle()}{/if}">{if $item->hasTitle()}{$item->getTitle()}{/if}</div>
  <a name="title" class="inline editable edit link" data-inline-name="title"><i class="tiny edit icon"></i></a>
 </div>
</h1>
<div name="title" class="inline editable formsrc" style="display: none;">
<form class="ui form" onsubmit="return false;">
 <div class="fields">
  <div class="field small ui input">
   <input type="text" name="title" value="{if $item->hasTitle()}{$item->getTitle()}{/if}" data-action="update" data-model="document" data-key="document_title" data-id="{$item->getId()}" />
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
         <a href="{get_url page=document mode=show id=$item_safe_link file=$item->getFileName()}">{$item->getFileName()}</a>
         <a class="scan document" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="document" data-action-title="{if $item->hasTitle()}{$item->getTitle()}{/if}"><i class="find icon"></i></a>
        </div>
        <div class="description">Original document (imported {$item->getTime()|date_format:"%Y.%m.%d %H:%M"})<br /><br /><a class="sign document" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action-title="Sign {if $item->hasTitle()}{$item->getTitle()}{/if}"><i class="protect icon"></i>Click to digitally sign document</a>.</div>
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
     <form id="document_custom_date_form" class="ui form" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-target="document_custom_date" onsubmit="return false;" style="{if !$item->hasCustomDate()}display: none;{/if}">
      <div class="fields">
       <div class="field ui input">
        <input type="text" name="document_custom_date" value="{if $item->hasCustomDate()}{$item->getCustomDate()}{/if}" data-action="update" data-model="document" data-key="document_custom_date" data-id="{$item->getId()}" />
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
   <div class="row">
    <div class="two wide column">&nbsp;</div>
    <div class="fourteen wide column">
     <div class="ui toggle checkbox" name="document_expiry_date_checkbox">
      <input type="checkbox" name="use_document_expiry_date" {if $item->hasExpiryDate()}checked{/if} />
      <label>Assign expiry date to document.</label>
     </div><br /><br />
     <form id="document_expiry_date_form" class="ui form" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-target="document_expiry_date" onsubmit="return false;" style="{if !$item->hasExpiryDate()}display: none;{/if}">
      <div class="fields">
       <div class="field ui input">
        <input type="text" name="document_expiry_date" value="{if $item->hasExpiryDate()}{$item->getExpiryDate()}{/if}" data-action="update" data-model="document" data-key="document_expiry_date" data-id="{$item->getId()}" />
       </div>
       <div class="field">
        <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
       </div>
       <div class="field">
        <button class="circular ui icon button cancel"><i class="cancel icon"></i></button>
       </div>
      </div>
      <div id="document_expiry_date_picker"></div>
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
     <form id="document_keyword" class="ui form keywords" data-target="assigned_keywords">
      <div class="field">
       <label>Keywords:</label>
       <div class="ui fluid search dropdown multiple selection" id="keyword_dropdown">
        <input type="hidden" name="assigned_keywords" value="{','|implode:$item->getKeywords()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="document" data-action="update" data-key="document_keywords">
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
      <button class="circular small ui icon save button" type="submit">
       <i class="save icon"></i>
      </button>
     </form>
    </div>
   </div>
   <div class="row">
    <div class="twelve wide column">
     <form id="document_description" class="ui form description" data-target="document_description">
      <div class="field">
       <label>Description:</label>
       <textarea name="document_description" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="document" data-action="update" data-key="document_description">{if $item->hasDescription()}{$item->getDescription()}{/if}</textarea>
      </div>
      <button class="circular small ui icon save button" type="submit">
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
        <div class="eleven wide column">{$item->getFileName()}</div>
       </div>
       <div class="row">
        <div class="five wide column">Size:</div>
        <div class="eleven wide column">{get_humanreadable_filesize size=$item->getFileSize()}</div>
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
      rpc_object_update($(this));
      return false;
   });

   load_datepickers("document");
});
{/literal}--></script>
