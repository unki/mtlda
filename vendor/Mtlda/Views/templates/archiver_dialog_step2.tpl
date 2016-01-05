<div class="ui two column grid">
 <div class="column">
  <div class="field">
   <label>Import Date</label>
   <div class="text">{$item->getTime()|date_format:"%Y.%m.%d %H:%M"}</div>
  </div>
  <div class="field">
   <label>Custom Date</label>
  </div>
  <div class="ui toggle checkbox" name="queue_custom_date_checkbox">
   <input type="checkbox" name="use_queue_custom_date" {if $item->hasCustomDate()}checked{/if} />
   <label>Assign custom date to document.</label>
  </div><br /><br />
  <form id="queue_custom_date_form" class="ui form" data-target="queue_custom_date" style="{if !$item->hasCustomDate()}display: none;{/if}">
   <div class="fields">
    <div class="field ui input">
     <input type="text" id="queue_custom_date" name="queue_custom_date" value="{if $item->hasCustomDate()}{$item->getCustomDate()}{/if}" data-action="update" data-model="queueitem" data-key="queue_custom_date" data-id="{$item->getId()}" />
    </div>
    <div class="field">
     <button class="circular ui icon button save" type="submit" data-content="Save custom date"><i class="save icon"></i></button>
     <button class="circular ui icon button cancel" type="reset" data-content="Reset custom date"><i class="cancel icon"></i></button>
    </div>
   </div>
{if isset($has_date_suggestions) && $has_date_suggestions}
   Suggestions:<br />
{date_suggestions}
   <a onclick="$('#queue_custom_date').val('{$suggest}')">{$suggest}</a><br />
{/date_suggestions}
{/if}
  </form>

  <div class="field">
   <label>Expiry Date</label>
  </div>
  <div class="ui toggle checkbox" name="queue_expiry_date_checkbox">
   <input type="checkbox" name="use_queue_expiry_date" {if $item->hasExpiryDate()}checked="checked"{/if} />
   <label>Assign expiry date to document.</label>
  </div><br /><br />
  <form id="queue_expiry_date_form" class="ui form" data-target="queue_expiry_date" style="{if !$item->hasExpiryDate()}display: none;{/if}">
   <div class="fields">
    <div class="field ui input">
     <input type="text" id="queue_expiry_date" name="queue_expiry_date" value="{if $item->hasExpiryDate()}{$item->getExpiryDate()}{/if}" data-action="update" data-model="queueitem" data-key="queue_expiry_date" data-id="{$item->getId()}" />
    </div>
    <div class="field">
     <button class="circular ui icon button save" type="submit" data-content="Save expiry date"><i class="save icon"></i></button>
     <button class="circular ui icon button cancel" type="reset" data-content="Reset expiry date"><i class="cancel icon"></i></button>
    </div>
   </div>
  </form>
  <button id="next_button" class="ui button" data-content="Continue to next step" data-modal-title="Archive {$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" onclick="$(this).popup('hide'); archiver_window($(this), {$next_step}); return false;">Next</button>
 </div>

 <div class="column">
  <form id="document_keyword" class="ui form keywords" data-target="assigned_keywords">
   <div class="field">
    <label>Keywords:</label>
    <div class="ui fluid search dropdown multiple selection" id="keyword_dropdown">
     <input type="hidden" name="assigned_keywords" value="{','|implode:$item->getKeywords()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-action="update" data-key="queue_keywords" />
     <i class="dropdown icon"></i>
     <input class="search" />
     <div class="default text">No keywords assigned.</div>
     <div class="menu">
{foreach $keywords as $keyword}
      <div class="item" data-value="{$keyword->getId()}" data-text="{$keyword->getName()}">{$keyword->getName()}</div>
{/foreach}
     </div>
    </div>
   </div>
   <button class="circular small ui icon save button" type="submit" data-content="Save keywords">
    <i class="save icon"></i>
   </button>
   <button class="circular small ui icon clear button" type="submit" data-content="Clear keywords">
    <i class="delete icon"></i>
   </button>
{if isset($has_keyword_suggestions) && $has_keyword_suggestions}
   <br />Suggestions:<br />
{keyword_suggestions}
   <a onclick="$('#keyword_dropdown').dropdown('set selected', ['{$keyword}']); $(this).hide();">{$keyword}{if isset($occurrences) && !empty($occurrences)} ({$occurrences}){/if}</a><br />
{/keyword_suggestions}
{/if}
{if isset($has_keyword_suggestions_similar) && $has_keyword_suggestions_similar}
   <br />Similar documents use the following keywords:<br />
{keyword_suggestions_similar}
   <a onclick="$('#keyword_dropdown').dropdown('set selected', ['{$keyword}']); $(this).hide();">{$keyword}{if isset($occurrences) && !empty($occurrences)} ({$occurrences}){/if}</a><br />
{/keyword_suggestions_similar}
{/if}

  </form>
  <br />
{if !$item->hasIndices()}
  <a class="scan document ui icon button" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-action-title="{if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}" data-content="Scan {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}"><i class="find icon"></i></a>
{/if}
  <a class="ui icon button" href="{$app_web_path}/resources/pdfjs/web/viewer.html?file={get_url page=queue mode=show id=$item_safe_link}" data-content="Preview {if $item->hasTitle()}{$item->getTitle()|escape}{else}{$item->getFileName()|escape}{/if}" data-variation="wide" id="queueitem-{$item_safe_link}" target="_blank"><i class="search icon"></i></a>
 </div>
</div>
<script type="text/javascript"><!--
load_datepickers("queue");
init_dropdowns();
$("a.scan.document").click(function () {
   $('#archiver_modal_window')
      .modal('setting', { closable: false })
      .removeClass('active')
      .modal('refresh');
   $('#archiver_modal_window').addClass('blurring');
   $('#archiver_modal_window .ui.dimmer').addClass('active');
   return rpc_object_scan($(this), function (scan_wnd) {
      $('#archiver_modal_window .ui.dimmer').removeClass('active');
      $('#archiver_modal_window').removeClass('blurring');
      $('#archiver_modal_window')
         .modal('setting', { closable: true })
         .addClass('active')
         .modal('refresh');
      scan_wnd.modal('hide');
      archiver_window($('button#next_button'), 2);
      return true;
   });
});

$('#archiver_modal_window form.ui.form').on('submit', function () {
   rpc_object_update($(this), function (element, data) {
      if (typeof element === 'undefined' || !element) {
         throw 'lost element!';
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

$('#archiver_modal_window form.ui.form input').on('input', function () {
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

$('#archiver_modal_window form.ui.form button.clear.button').click(function () {
   $('#keyword_dropdown').dropdown('clear');
});

$('button.ui.button, a.ui.icon.button').popup();
--></script>
