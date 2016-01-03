<div class="ui segment">
 <p>Document total pages: {$page_count}</p>
 <div class="ui labeled input">
  <div class="ui label">
    Split into
  </div>
  <input type="text" name="document_count" value="1" size="3">
  <div class="ui label">
    documents
  </div>
 </div>
 <button class="ui icon button document count minus"><i class="red minus icon"></i></button>
 <button class="ui icon button document count plus"><i class="green plus icon"></i></button>
{if $page_count < 10}
 <button class="ui icon button document one per page">One page per document</button>
{/if}
</div>
<div id="checkbox_template" class="field" style="display: none;">
 <div class="ui checkbox">
  <input type="checkbox" name="" value="1" tabindex="0" class="hidden">
  <label>template text</label>
 </div>
</div>
<form class="ui form step2" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-modal-title="Split {if $item->hasTitle}{$item->getTitle()}{else}{$item->getFileName()}{/if}">
{for $page_no=1 to $page_count}
 <div class="ui segment">
  <div class="ui grid">
   <div class="six wide column">
    <a href="{$app_web_path}/preview/queueitem-{$image_safe_link}/{$page_no}/full" target="_blank">
    <img src="{$app_web_path}/preview/queueitem-{$image_safe_link}/{$page_no}" class="ui bordered image" height="300" />
    </a>
   </div>
   <div class="{if $page_no == 1 || $page_no == $page_count}seven{else}ten{/if} wide column">
    Page {$page_no}
    <div class="grouped fields" data-page="{$page_no}">
     <label for="page_mode[{$page_no}]">Page appears in:</label>
    </div>
   </div>
{if $page_no == 1 || $page_no == $page_count}
   <div class="three wide column {if $page_no == $page_count}bottom{/if} aligned">
    <button type="submit" class="ui button">Continue</button>
   </div>
{/if}
  </div>
 </div>
{/for}
</form>
<script type="text/javascript"><!--

$('#splitter_content input[name=document_count]').on('input', function () {
   if ((value = $(this).val()) === undefined) {
      throw 'Failed to fetch document count!';
      return false;
   }

   if (value == "") {
      return true;
   }

   if (!isInteger(value)) {
      $(this).val(document_count);
      return true;
   }

   if (value < 1) {
      $(this).val(document_count);
      return true;
   }

   document_count = value;
   $('#splitter_content form.ui.form.step2').trigger('checkboxchange');
   return true;
});

$('#splitter_content button.ui.button.document.count').click(function () {
   if (!isInteger(document_count)) {
      document_count = 1;
   }
   if ($(this).hasClass('minus')) {
      if (document_count > 10) {
         document_count = 10;
      }
      if (document_count > 1) {
         document_count--;
      }
   } else if ($(this).hasClass('plus')) {
      if (document_count < 10) {
         document_count++;
      }
   }
   $('#splitter_content input[name=document_count]').val(document_count);
   $('#splitter_content form.ui.form.step2').trigger('checkboxchange');
   return true;
});

{if $page_count < 10 }
$('#splitter_content button.ui.button.one.per.page').click(function () {
   document_count = {$page_count};
   $('#splitter_content input[name=document_count]').val({$page_count});
   $('#splitter_content form.ui.form.step2').trigger('checkboxchange');
   return true;
});
{/if}

$('#splitter_content form.ui.form.step2').on('checkboxchange', function () {
   if ((checkbox_template = $('#splitter_content #checkbox_template')) === undefined) {
      throw 'failed to locate checkbox_template!';
      return false;
   }
   $(this).find('.grouped.fields').each(function () {
      if ((page_no = $(this).attr('data-page')) === undefined) {
         throw 'no data-page attribute!';
         return false;
      }
      if ((document_checkboxes = $(this).find(".field")) !== undefined) {
         document_checkboxes.each(function () {
            $(this).remove();
         });
      }
      for (i = 1; i <= document_count; i++) {
         checkbox = checkbox_template.clone();
         checkbox.removeAttr("id");
         checkbox.find("input").val(i);
         checkbox.find("label").text("Document " + i);
         checkbox.attr("data-document", i);
         if (i == page_no) {
            checkbox.find("input").attr("checked", "checked");
         }
         checkbox.find("input").attr("name", "page_mode["+ page_no +"]");
         checkbox.show();
         $(this).append(checkbox);
      }
   });
   $('#splitter_content .ui.checkbox').checkbox();
   return true;
});

$('#splitter_content .ui.form.step2').submit(function () {
   for (i = 1; i <= document_count; i++) {
      documents[i] = new Array;
   }

   $(this).find('input:checked[name^=page_mode]').each(function () {
      if ((name = $(this).attr('name')) === undefined) {
         throw 'Failed to read name attribute!';
         return false;
      }
      if ((into_document = $(this).val()) === undefined) {
         throw 'Failed to read checkbox value!';
         return false;
      }
      page = name.match(/^page_mode\[(\d+)\]$/);
      if (!page || !page[1] || page[1] == '') {
         throw 'Failed to retrieve page number!';
         return false;
      }
      page_no = page[1];
      documents[into_document].push(page_no);
      return true;
   });

   splitter_window($(this), {$next_step});
   // this form must return false!
   return false;
});

document_count = 1;
documents = new Array;
$('#splitter_content form.ui.form.step2').trigger('checkboxchange');

--></script>
