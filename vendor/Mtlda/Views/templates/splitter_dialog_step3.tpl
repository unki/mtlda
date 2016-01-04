<div class="ui segment template" style="display: none;">
 <h4 class="ui header">Document 1</h4>
 <div class="inline fields">
  <div class="two wide field">
    <div class="ui checkbox">
      <label>Title:</label>
      <input type="checkbox" class="hidden" {if $item->hasTitle()}checked="checked"{/if}" />
    </div>
  </div>
  <div class="{if !$item->hasTitle()}disabled{/if} fourteen wide field title">
   <input type="text" name="document_titles" value="{if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()|regex_replace:'/\.([a-zA-Z]+)$/':''|replace:'_':' '}{/if}" />
  </div>
 </div>
 <div class="required field">
  <label>Pages:</label>
  <input type="text" name="document_pages" placeholder="Selected pages..." size="3">
 </div>
 <div class="ui required field">
  <label>Filename:</label>
  <div class="ui right labeled input">
   <div class="ui label">{$item->getFileNameBase()}_</div>
   <input name="document_file_names" type="text" value="" />
   <div class="ui label">.{$item->getFileNameExtension()}</div>
  </div>
 </div>
</div>
<form class="ui form step3" data-id="{$item->getId()}" data-guid="{$item->getGuid()}" data-model="queueitem" data-modal-title="Split {if $item->hasTitle()}{$item->getTitle()}{else}{$item->getFileName()}{/if}">
 <button class="ui button upper submit" type="submit">Split!</button>
 <button class="ui button lower submit" type="submit">Split!</button>
</form>
<script type="text/javascript"><!--
if (typeof documents === 'undefined' || !documents instanceof Array) {
   throw 'Lost pages information!';
}

documents.forEach(function (pages, document_no) {
   segment = $(".ui.segment.template").clone();
   segment.removeClass("template");
   segment.find("h4.ui.header").text("Document "+ document_no);
   segment.attr("id", "document_"+ document_no);
   if (typeof (input = segment.find("input[name=document_pages]")) === 'undefined') {
      throw 'failed to locate input element!';
      return false;
   }
   input.attr("name", "document_pages[" + document_no +"]");
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
   input.attr("name", "document_use_title_" + document_no);
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

   documents = new Array;

   if (typeof (document_segments = $(this).find('[id^="document_"]')) === 'undefined') {
      throw 'Failed to find document segment!';
      return false;
   }

   document_segments.each(function () {
      var this_document, document_no, use_title, document_title, document_file_name, document_pages;

      document_no = $(this).attr('id').match(/^document_(\d+)$/);
      if (!document_no || !document_no[1] || document_no[1] == '') {
         throw 'Failed to retrieve page number!';
         return false;
      }
      document_no = document_no[1];
 
      if (typeof (use_title = $(this).find('input[type=checkbox][name^="document_use_title_"]').parent().checkbox('is checked')) === 'undefined') {
         throw 'Failed to read checkbox value!';
         return false;
      }

      if (typeof (document_title = $(this).find('input[name^="document_title"]').val()) === 'undefined') {
         throw 'Failed to read document title!';
         return false;
      }

      if (typeof (document_file_name = $(this).find('input[name^="document_file_name"]').val()) === 'undefined') {
         throw 'Failed to read document filename!';
         return false;
      }

      if (typeof (document_pages = $(this).find('input[name^="document_pages"]').val()) === 'undefined') {
         throw 'Failed to read document pages!';
         return false;
      }

      this_document = new Object;
      if (use_title) {
         this_document.title = document_title;
      }
      this_document.file_name = document_file_name;
      this_document.pages = document_pages;
      documents.push(this_document);
      return true;
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
