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
<div class="ui right floated pagination borderless small menu">
{if isset($pager) && $pager->getNumberOfPages() > 1}
{if $pager->getFirstPageNumber()}
 <a class="icon item" href="{get_url page=$view number=$pager->getFirstPageNumber()}"><i class="angle double left icon"></i></a>
{/if}
{if $pager->getPreviousePageNumber()}
 <a class="icon item" href="{get_url page=$view number=$pager->getPreviousePageNumber()}"><i class="angle left icon"></i></a>
{/if}
{foreach $pager->getDeltaPageNumbers() as $pageno}
 <a class="item {if $pager->isCurrentPage($pageno)}active{/if}" href="{get_url page=$view number=$pageno}">{$pageno}</a>
{/foreach}
{if $pager->getNextPageNumber()}
 <a class="icon item" href="{get_url page=$view number=$pager->getNextPageNumber()}"><i class="angle right icon"></i></a>
{/if}
{if $pager->getLastPageNumber()}
 <a class="icon item" href="{get_url page=$view number=$pager->getLastPageNumber()}"><i class="angle double right icon"></i></a>
{/if}
 <div class="item inactive">Page:</div>
 <div class="item">
  <div class="ui compact search selection dropdown" id="pager_page_select">
   <input type="hidden" name="pagergoto" value="{$pager->getCurrentPage()}">
   <i class="dropdown icon"></i>
   <div class="default text">Goto page:</div>
   <div class="menu">
{foreach $pager->getPageNumbers() as $pageno}
    <div class="item {if $pager->isCurrentPage($pageno)}active{/if}" data-value="{get_url page=$view number=$pageno}">{$pageno}</div>
{/foreach}
   </div>
  </div>
 </div>
{/if}
 <div class="item inactive">Items:</div>
 <div class="item">
  <div class="ui compact search selection dropdown" id="pager_items_select">
   <input type="hidden" name="itemsgoto" value="{$pager->getCurrentItemsLimit()}">
   <i class="dropdown icon"></i>
   <div class="default text">Items per page:</div>
   <div class="menu">
{foreach $pager->getItemsLimits() as $item_cnt}
    <div class="item{if $pager->isCurrentItemsLimit($item_cnt)} active{/if}" data-value="{get_url page=$view items_per_page=$item_cnt}">{if $item_cnt == 0}all{else}{$item_cnt}{/if}</div>
{/foreach}
   </div>
  </div>
 </div>
</div>
<script type="text/javascript"><!--
$(document).ready(function () {
   $('#pager_page_select, #pager_items_select').dropdown({
      match: 'text',
      onChange : function(value, text, choice) {
         if (value === undefined || value == "") {
            return false;
         }
         window.location = value;
      }
   });
});
--></script>
