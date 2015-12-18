<div class="ui segment template" style="display: none;">
 <h4 class="ui header">Document 1</h4>
 <p>Title: Document-Split-1-XXXX-{if $item->hasTitle}{$item->getTitle()}{else}{$item->getFileName()}{/if}</p>
 <p>Filename: {$item->getFileName()}</p>
 <div class="ui labeled input">
  <div class="ui label">Pages:</div>
  <input type="text" name="document_pages" placeholder="Selected pages...">
 </div>
</div>
<form class="ui form step3" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" title="Split {if $item->hasTitle}{$item->getTitle()}{else}{$item->getFileName()}{/if}">
 <button class="ui button" type="submit">Split!</button>
 <button class="ui button" type="submit">Split!</button>
</form>
<script type="text/javascript"><!--
if (documents === undefined || !documents instanceof Array) {
   throw 'Lost pages information!';
}

documents.forEach(function (pages, document_no) {
   segment = $(".ui.segment.template").clone();
   segment.removeClass("template");
   segment.find("h4.ui.header").text("Document "+ document_no);
   if ((input = segment.find("input[name=document_pages]")) === undefined) {
      throw 'failed to locate input element!';
      return false;
   }
   input.attr("name", "document_[" + document_no +"]");
   input.val(pages.join(','));
   segment.show();
   $("form.ui.form.step3").append(segment);
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
--></script>
