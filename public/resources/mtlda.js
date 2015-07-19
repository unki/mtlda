function show_preview(element)
{
    var obj_id = element.attr("id");

    if(obj_id == undefined || obj_id == "") {
        alert('no attribute "id" found!');
        return;
    }

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({
         type      : 'rpc',
         action    : 'get-content',
         content   : 'preview',
         model     : 'queueitem',
         id        : obj_id
      }),
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data) {
         $('#dialog').html(data);
         $('#dialog').dialog({
            title:      'Preview',
            modal:      true,
            autoOpen:   false,
            draggable:  false,
            resizeable: false,
            buttons:    {
               Ok: function() {
                  $(this).dialog("close");
               }
            }
         });
         if(!$('#dialog').dialog('isOpen')) {
            $('#dialog').dialog('open');
         }
      }
   });
}

$(document).ready(function() {
   $("table td a.delete").click(function(){
      rpc_object_delete($(this));
   });
   $("table td a.archive").click(function(){
      rpc_object_archive($(this));
   });
   $("table td a.preview").click(function(){
      show_preview($(this));
   });
   /*$("table td a.clone").click(function(){
      obj_clone($(this));
   });
   $("table td div a.toggle-off, table td div a.toggle-on").click(function(){
      obj_toggle_status($(this));
   });
   */
   $('img.change_to').hover(
      function() {
         $(this).css('cursor','pointer');
      },
      function() {
         $(this).css('cursor','auto');
      }
   );
   //load_menu();
});
