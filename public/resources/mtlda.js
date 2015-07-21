function show_preview(element)
{
    var obj_id = element.attr("id");

    if(obj_id == undefined || obj_id == "") {
        alert('no attribute "id" found!');
        return;
    }

    open_preview_dialog(obj_id);
}

function open_preview_dialog(obj_id)
{
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
                width:      900,
                height:     450,
                position:   {
                    my:      "center top+5%",
                    at:      "center top+5%",
                    of:      "#content"
                },
                buttons:    {
                    Ok: function() {
                        $(this).dialog("close");
                    }
                },
                open: function() {
                    var previewimg = $("#dialog img[name=previewimg]").attr("load");
                    if(previewimg == undefined || previewimg == '') {
                        alert('found no image to load:' + previewimg);
                        return;
                    }
                    var preview = new Image;
                    preview.src = previewimg;
                    if(preview.complete) {
                        $("#dialog img[name=previewimg]").attr('src', previewimg);
                        preview.onload=function(){};
                    } else{
                        preview.onload = function() {
                            $("#dialog img[name=previewimg]").attr('src', this.src);
                            //    clear onLoad, IE behaves irratically with animated gifs otherwise
                            preview.onload=function(){};
                        }
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

function change_preview(direction)
{
    var dialog = $('#dialog');

    if(!dialog.dialog('isOpen')) {
        return false;
    }

    var img = $('#dialog img[name=previewimg]');

    if(img == undefined || img == "") {
        return false;
    }

    var imgid = img.attr('imgid');

    if(!imgid) {
        return false;
    }

    $.ajax({
        type: "POST",
        url: "rpc.html",
        data: ({
            type      : 'rpc',
            action    : 'find-prev-next',
            model     : 'queueitem',
            id        : imgid,
            direction : direction
        }),
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert('Failed to contact server! ' + textStatus);
        },
        success: function(obj_id) {
            open_preview_dialog(obj_id);
            var previewimg = $("#dialog img[name=previewimg]").attr("load");
            if(previewimg == undefined || previewimg == '') {
                alert('found no image to load:' + previewimg);
                return;
            }
            var preview = new Image;
            preview.src = previewimg;
            if(preview.complete) {
                $("#dialog img[name=previewimg]").attr('src', previewimg);
                preview.onload=function(){};
            } else{
                preview.onload = function() {
                    $("#dialog img[name=previewimg]").attr('src', this.src);
                    //    clear onLoad, IE behaves irratically with animated gifs otherwise
                    preview.onload=function(){};
                }
            }
        }
    });

    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
