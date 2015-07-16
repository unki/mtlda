function rpc_object_delete(element, target, idx)
{
   var del_id = element.attr("id");

   if(del_id == undefined || del_id == "") {
      alert('no attribute "id" found!');
      return;
   }

   if(!confirm("Are you sure you want to delete this object? There is NO undo!")) {
      return false;
   }

   $.ajax({
      type: "POST",
      url: "rpc.html",
      data: ({type : 'rpc', action : 'delete', id : del_id }),
      beforeSend: function() {
         // change row color to red
         element.parent().parent().animate({backgroundColor: "#fbc7c7" }, "fast");
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
         alert('Failed to contact server! ' + textStatus);
      },
      success: function(data){
         if(data == "ok") {
            element.parent().parent().animate({ opacity: "hide" }, "fast");
            return;
         }
         // change row color back to white
         element.parent().parent().animate({backgroundColor: "#ffffff" }, "fast");
         alert('Server returned: ' + data + ', length ' + data.length);
         return;
      }
   });

   return true;

} // obj_delete()
