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
<h1 class="ui block header">
 <i class="file text icon"></i>
 <div class="content">
  <div name="title" class="inline editable content" data-current-value="{if $origin->hasTitle()}{$origin->getTitle()}{/if}" data-orig-value="{if $origin->hasTitle()}{$origin->getTitle()}{/if}">{if $origin->hasTitle()}{$origin->getTitle()}{/if}</div>
  <a name="title" class="inline editable edit link" data-inline-name="title"><i class="tiny edit icon"></i></a>
 </div>
</h1>
<div name="title" class="inline editable formsrc" style="display: none;">
<form class="ui form" onsubmit="return false;">
 <div class="fields">
  <div class="field small ui input">
   <input type="text" name="title" value="{if $origin->hasTitle()}{$origin->getTitle()}{/if}" data-action="update" data-model="document" data-key="document_title" data-id="{$origin->getIdx()}" data-guid="{$origin->getGuid()}" />
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

<div class="ui container segment">
 <div class="ui header">Versions:</div>
 <div class="ui very relaxed divided selection list">
{list_versions}
  <div class="item">
   <div class="right floated content">
   {if $item->hasVersion()}
    {if $item->getVersion() == $latest_document_version}
    <i class="ui big red tag icon bubble" data-title="This is the latest version of the current document."></i>
    {/if}
    {if $item->getVersion() == 1}
     <i class="big blue file text icon bubble" data-title="This is the original document."></i>
    {else}
     <i class="big {if $item->isSignedCopy()}protect{else}copy{/if} icon bubble" data-title="{if $item->isSignedCopy()}This is a signed copy of the original document.{else}This is a copy of the original document.{/if}"></i>
    {/if}
   {/if}
   {if isset($pdf_signature_verification_is_enabled) && $pdf_signature_verification_is_enabled && $item->isSignedCopy()}
    <i class="big red {if !$item->verifySignature()}red unlock{else}green lock{/if} icon bubble" data-title="PDF-signature validation {if $item->verifySignature()}suceed{else}failed{/if}!"></i>
   {/if}
   </div>
   <i class="file text icon"></i>
   <div class="content">
    <div class="header">
     {if $item->hasVersion() && $item->getVersion() == 1}
     <!-- original document -->
     <a name="filename_{$item->getIdx()}" class="inline editable content" data-current-value="{$item->getFileName()}" data-orig-value="{$item->getFileName()}" href="{get_url page=document mode=show id=$item_safe_link file=$item->getFileName()}">{$item->getFileName()}</a>&nbsp;&nbsp;
     <a class="scan document" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-model="document" data-action-title="{if $item->hasTitle()}{$item->getTitle()}{/if}"><i class="find icon"></i></a>
     {else}
     <!-- derivates -->
     <a name="filename_{$item->getIdx()}" class="inline editable content" data-current-value="{$item->getFileName()}" data-orig-value="{$item->getFileName()}" href="{get_url page=document mode=show id=$item_safe_link file=$item->getFileName()}">{$item->getFileName()}</a>&nbsp;&nbsp;
     <a name="filename_{$item->getIdx()}" class="inline editable edit link" data-inline-name="filename_{$item->getIdx()}"><i class="tiny edit icon"></i></a>
     <a class="delete item" title="Delete {$item->getFileName()|escape}"  data-action-title="Deleting {$item->getFileName()|escape}" data-modal-title="Delete {$item->getFileName()|escape}" data-modal-text="Please confirm to delete {$item->getFileName()|escape}"  data-model="document" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}"><i class="remove circle icon"></i></a>
     <div name="filename_{$item->getIdx()}" class="inline editable formsrc" style="display: none;">
      <form class="ui form" onsubmit="return false;">
       <div class="fields">
        <div class="field small ui input">
         <input type="text" name="filename_{$item->getIdx()}" value="{$item->getFileName()}" data-action="update" data-model="document" data-key="document_file_name" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" />
        </div>
        <div class="field">
         <button class="circular ui icon button inline editable save" type="submit"><i class="save icon"></i></button>
        </div>
        <div class="field">
         <button class="circular ui icon button inline editable cancel"><i class="cancel icon"></i></button>
        </div>
       </div>
      </form>
     </div>
     {/if}
    </div>

    <div class="description">
     {if $item->hasVersion() && $item->getVersion() == 1}
     Original document (imported {$item->getTime()|date_format:"%Y.%m.%d %H:%M"})<br /><br />
     {else}
     Version {$item->getVersion()} (created {$item->getTime()|date_format:"%Y.%m.%d %H:%M"})
     {/if}
     {if ! $item->isSignedCopy()}
     <a class="sign document bubble" data-id="{$item->getIdx()}" data-guid="{$item->getGuid()}" data-action-title="Sign {if $item->hasTitle()}{$item->getTitle()}{/if}"><i class="big protect icon"></i>Create a digitally-signed document copy</a>.
     {/if}
    </div>
   </div>
  </div>
{/list_versions}
 </div>
</div>

<div class="ui container segment">
 <div class="ui header">Key data:</div>
 <div class="ui toggle checkbox" name="document_custom_date_checkbox">
  <input type="checkbox" name="use_document_custom_date" {if $origin->hasCustomDate()}checked{/if} />
  <label>Assign custom date to document.</label>
 </div>
 <br /><br />
 <form id="document_custom_date_form" class="ui form" data-target="document_custom_date" onsubmit="return false;" style="{if !$origin->hasCustomDate()}display: none;{/if}">
  <div class="fields">
   <div class="field ui input">
    <input type="text" id="document_custom_date" name="document_custom_date" value="{if $origin->hasCustomDate()}{$origin->getCustomDate()}{/if}" data-action="update" data-model="document" data-key="document_custom_date" data-id="{$origin->getIdx()}" data-guid="{$origin->getGuid()}" />
   </div>
   <div class="field">
    <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
   </div>
   <div class="field">
    <button class="circular ui icon button cancel"><i class="cancel icon"></i></button>
   </div>
  </div>
  <div id="document_custom_date_picker"></div>
{if isset($has_date_suggestions) && $has_date_suggestions}
  <div class="ui horizontal list">
   <div class="disabled item">Suggestions:</div>
{date_suggestions}
    <a class="item" onclick="$('#document_custom_date').val('{$suggest}')">{$suggest}</a>
{/date_suggestions}
  </div>
{/if}
 </form>

 <div class="ui toggle checkbox" name="document_expiry_date_checkbox">
  <input type="checkbox" name="use_document_expiry_date" {if $origin->hasExpiryDate()}checked{/if} />
  <label>Assign expiry date to document.</label>
 </div>
 <br /><br />
 <form id="document_expiry_date_form" class="ui form" data-id="{$origin->getIdx()}" data-guid="{$origin->getGuid()}" data-target="document_expiry_date" onsubmit="return false;" style="{if !$origin->hasExpiryDate()}display: none;{/if}">
  <div class="fields">
   <div class="field ui input">
    <input type="text" name="document_expiry_date" value="{if $origin->hasExpiryDate()}{$origin->getExpiryDate()}{/if}" data-action="update" data-model="document" data-key="document_expiry_date" data-id="{$origin->getIdx()}" data-guid="{$origin->getGuid()}" />
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

<div class="ui container segment">
 <form id="document_keyword" class="ui form keywords" data-target="assigned_keywords">
  <div class="field">
   <label>Keywords:</label>
   <div class="ui fluid search dropdown multiple selection" id="keyword_dropdown">
    <input type="hidden" name="assigned_keywords" value="{','|implode:$origin->getKeywords()}" data-id="{$origin->getIdx()}" data-guid="{$origin->getGuid()}" data-model="document" data-action="update" data-key="document_keywords">
    <i class="dropdown icon"></i>
    <input class="search">
    <div class="default text">No keywords assigned.</div>
    <div class="menu">
{foreach $keywords as $keyword}
     <div class="item" data-value="{$keyword->getIdx()}" data-text="{$keyword->getName()}">{$keyword->getName()}</div>
{/foreach}
    </div>
   </div>
  </div>
  <div class="inline field">
   <button class="circular small ui icon save button" type="submit">
    <i class="save icon"></i>
   </button>
{if (isset($has_keyword_suggestions) && $has_keyword_suggestions) || (isset($has_keyword_suggestions_similar) && $has_keyword_suggestions_similar)}
   <div class="ui horizontal list">
    <div class="disabled item">Suggestions:</div>
{if isset($has_keyword_suggestions) && $has_keyword_suggestions}
{keyword_suggestions}
     <a class="item" onclick="$('#keyword_dropdown').dropdown('set selected', ['{$keyword}']); $(this).remove();">{$keyword}{if isset($occurrences) && !empty($occurrences)} ({$occurrences}){/if}</a>
{/keyword_suggestions}
{/if}
{if isset($has_keyword_suggestions_similar) && $has_keyword_suggestions_similar}
{keyword_suggestions_similar}
     <a class="item" onclick="$('#keyword_dropdown').dropdown('set selected', ['{$keyword}']); $(this).remove();">{$keyword}{if isset($occurrences) && !empty($occurrences)} ({$occurrences}){/if}</a>
{/keyword_suggestions_similar}
{/if}
   </div>
{/if}
  </div>
 </form>
</div>

<div class="ui container segment">
 <form id="document_description" class="ui form description" data-target="document_description">
  <div class="field">
   <label>Description:</label>
   <textarea name="document_description" data-id="{$origin->getIdx()}" data-guid="{$origin->getGuid()}" data-model="document" data-action="update" data-key="document_description">{if $origin->hasDescription()}{$origin->getDescription()}{/if}</textarea>
  </div>
  <button class="circular small ui icon save button" type="submit">
   <i class="save icon"></i>
  </button>
 </form>
</div>

{if isset($pdf_indexing_is_enabled) && $pdf_indexing_is_enabled}
<div class="ui container segment">
 <div class="ui header">Document Properties:</div>
 <div class="ui grid">
  <div class="row">
   <div class="five wide column">Filename:</div>
   <div class="eleven wide column">{$origin->getFileName()}</div>
  </div>
  <div class="row">
   <div class="five wide column">Size:</div>
   <div class="eleven wide column">{get_humanreadable_filesize size=$origin->getFileSize()}</div>
  </div>
{document_properties}
  <div class="row">
   <div class="five wide column">{$property->getDocumentProperty()}:</div>
   <div class="eleven wide column">{$property->getDocumentValue()}</div>
  </div>
{/document_properties}
 </div>
</div>
{/if}

<script type="text/javascript"><!--

'use strict';

$(document).ready(function() {

   $('form.ui.form.description textarea').on('input', function() {
      var savebutton = $('form.ui.form.description button.save');
      if(!savebutton.hasClass('red shape')) {
         savebutton.addClass('red shape');
         savebutton.transition('bounce');
      }
   });
   // bounce save icon once more if focus leaves the textarea field
   $('form.ui.form.description textarea').on('focusout', function() {
      var savebutton = $('form.ui.form.description button.save');
      if(savebutton.hasClass('red shape')) {
         savebutton.transition('bounce');
      }
   });

   $('form.ui.form.description').on('submit', function() {
      rpc_object_update($(this), function (element, data) {
            if (data != "ok") {
                return true;
            }
            var savebutton = element.find('button.save');
            savebutton.transition('tada').removeClass('red shape');
            return true;
        });
        return false;
   });
   load_datepickers("document");
});
--></script>
