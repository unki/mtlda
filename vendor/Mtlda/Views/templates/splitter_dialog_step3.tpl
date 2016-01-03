<div class="ui segment template" style="display: none;">
 <h4 class="ui header">Document 1</h4>
 <div class="inline fields">
  <div class="two wide field">
    <div class="ui checkbox">
      <label>Title:</label>
      <input type="checkbox" class="hidden" {if $item->hasTitle()}checked="checked"{/if}" />
    </div>
  </div>
  <div class="{if !$item->hasTitle()}disabled{/if} thirteen wide field title">
   <input type="text" name="document_titles" value="{if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()|regex_replace:'/\.([a-zA-Z]+)$/':''|replace:'_':' '}{/if}" />
  </div>
 </div>
 <div class="ui required field">
  <label>Filename:</label>
  <div class="ui right labeled input">
   <div class="ui label">{$item->getFileNameBase()}_</div>
   <input name="document_file_names" type="text" value="" />
   <div class="ui basic label">.{$item->getFileNameExtension()}</div>
  </div>
 </div>
 <div class="ui inline required field">
  <label>Pages:</label>
  <input type="text" name="document_pages" placeholder="Selected pages..." size="3">
 </div>
</div>
<form class="ui form step3" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-modal-title="Split {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}">
 <button class="ui button upper submit" type="submit">Split!</button>
 <button class="ui button lower submit" type="submit">Split!</button>
</form>
<script type="text/javascript"><!--
if (documents === undefined || !documents instanceof Array) {
   throw 'Lost pages information!';
}

document_file_prefix = "{$item->getFileName()|regex_replace:'/\.([a-zA-Z]+)$/':''}";

documents.forEach(function (pages, document_no) {
   segment = $(".ui.segment.template").clone();
   segment.removeClass("template");
   segment.find("h4.ui.header").text("Document "+ document_no);
   if (typeof (input = segment.find("input[name=document_pages]")) === 'undefined') {
      throw 'failed to locate input element!';
      return false;
   }
   input.attr("name", "document_[" + document_no +"]");
   input.val(pages.join(','));
   if (typeof (input = segment.find("input[name=document_titles]")) === 'undefined') {
      throw 'failed to locate input element!';
      return false;
   }
   input.attr("name", "document_title["+ document_no +"]");
   input.val(input.val() + ' Split ' + pages.join('_'));
   if (typeof (input = segment.find("input[type=checkbox]")) === 'undefined') {
      throw 'failed to locate input element!';
      return false;
   }
   input.attr("data-target", "document_title["+ document_no +"]");
   if (typeof (input = segment.find("input[name=document_file_names]")) === 'undefined') {
      throw 'failed to locate input element!';
      return false;
   }
   input.attr("name", "document_file_name["+ document_no +"]");
   input.val('split_' + pages.join('_'));
   segment.show();
   segment.insertBefore('form.ui.form.step3 button.ui.button.lower.submit');
   return true;
});

$('form.ui.form.step3').submit(function () {

   if ((document_elements = $(this).find('input[name^=document_]')) === undefined) {
      throw 'Failed to find input elements';
   }

   document_elements.each(function () {

      if ((value = $(this).val()) === undefined) {
         throw 'Failed to read values of input field!';
         return false;
      }
      if ((pages = value.split(',')) === undefined) {
         throw 'Failed to split input field value!';
         return false;
      }

      if ((name = $(this).attr("name")) === undefined) {
         throw 'Failed to find input elements name attribute!';
         return false;
      }

      document_no = name.match(/^document_\[(\d+)\]$/);
      if (!document_no || !document_no[1] || document_no[1] == '') {
         throw 'Failed to retrieve page number!';
         return false;
      }
      document_no = document_no[1];
   });

   splitter_window($(this), {$next_step});
   return false;
});

$('.ui.checkbox').checkbox({
   onChecked: function () {
      var target;
      if (typeof (target = $(this).attr('data-target')) === 'undefined') {
         return false;
      }
      $('input[name="'+ target +'"]').parent().removeClass('disabled');
      return true;
   },
   onUnchecked: function () {
      var target;
      if (typeof (target = $(this).attr('data-target')) === 'undefined') {
         return false;
      }
      $('input[name="'+ target +'"]').parent().addClass('disabled');
      return true;
   },
});
--></script>
