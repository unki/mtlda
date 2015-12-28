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
     <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
    </div>
    <div class="field">
     <button class="circular ui icon button cancel" type="reset"><i class="cancel icon"></i></button>
    </div>
   </div>
   Suggestions:&nbsp;
{date_suggestions}
   <a onclick="$('#queue_custom_date').val('{$suggest}')">{$suggest}</a>&nbsp;
{/date_suggestions}
  </form>

  <div class="field">
   <label>Expiry Date</label>
  </div>
  <div class="ui toggle checkbox" name="queue_expiry_date_checkbox">
   <input type="checkbox" name="use_queue_expiry_date" {if $item->hasExpiryDate()}checked{/if} />
   <label>Assign expiry date to document.</label>
  </div><br /><br />
  <form id="queue_expiry_date_form" class="ui form" data-target="queue_expiry_date" style="{if !$item->hasExpiryDate()}display: none;{/if}">
   <div class="fields">
    <div class="field ui input">
     <input type="text" id="queue_expiry_date" name="queue_expiry_date" value="{if $item->hasExpiryDate()}{$item->getExpiryDate()}{/if}" data-action="update" data-model="queueitem" data-key="queue_expiry_date" data-id="{$item->getId()}" />
    </div>
    <div class="field">
     <button class="circular ui icon button save" type="submit"><i class="save icon"></i></button>
    </div>
    <div class="field">
     <button class="circular ui icon button cancel" type="reset"><i class="cancel icon"></i></button>
    </div>
   </div>
  </form>
  <button class="ui button" class="ui form" data-modal-title="Archive {$item->getFileName()}" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" onclick="archiver_window($(this), {$next_step}); return false;">Next</button>
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
   <button class="circular small ui icon save button" type="submit">
    <i class="save icon"></i>
   </button>
  </form>
  <a class="scan document" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-action-title="{if $item->hasTitle()}{$item->getTitle()}{/if}"><i class="find icon"></i>Index document.</a>
 </div>
</div>
<script type="text/javascript"><!--
load_datepickers("queue");
init_dropdowns();
$("a.scan.document").click(function () {
   rpc_object_scan($(this));
});
--></script>
