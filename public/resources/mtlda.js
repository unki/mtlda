$(document).ready(function() {
   $("table td a.delete").click(function(){
      rpc_object_delete($(this));
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
