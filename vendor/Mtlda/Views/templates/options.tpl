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
<h1 class="ui header"><i class="options icon"></i>Options</h1>
<a id="truncate_link" href='#' data-url="{get_url page=options mode=truncate}">Reset data</a>
<script type="text/javascript"><!--

$(document).ready(function() {

   $('#truncate_link').click(function() {

      show_modal('confirm', {
         closable : false,
         header : 'Truncate Data',
         icon : 'warning icon',
         content : 'This will delete all data and settings from database!<br />\nAlso all archived files in their derivates will be removed!<br /><br />\n\nAre you sure? There is NO undo!',
         onDeny : function() {
            return true;
         },
         onApprove : function() {
            if(!$('#truncate_link').attr('data-url')) {
               return true;
            }
            window.location = $('#truncate_link').attr('data-url');
            return true;
         }
      });
   });
});
--></script>
