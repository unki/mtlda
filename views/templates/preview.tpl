<!-- first line -->
<div style="float: left; width: 20%; vertical-align: top">
 <a href="#" onclick="change_preview('prev');"><img src="{$image_arrow_left}" alt="left arrow icon" /></a>
</div>
<div style="float: left; width: 60%; vertical-align: middle; text-align: center">
 {$img_name}
</div>
<div style="float: left; width: 20%; vertical-align: top">
 <a href="#" onclick="change_preview('next');"><img src="{$image_arrow_right}" alt="right arrow icon" /></a>
</div>
<!-- /first line -->

<!-- second line -->
<div style="float: left; width: 20%; vertical-align: middle; clear: left">
 Details:
</div>
<!-- preview image -->
<div style="float: left; width: 60%; vertical-align: top; text-align: center; padding-top: 20px">
 <img name="previewimg" load="{$img_url}" src="{$img_load}" imgid="{$img_id}" />
</div>
<!-- /preview image -->
<div style="float: left; width: 20%; vertical-align: middle">
  <div style="clear: left;">
   Signing options:
  </div>
  <div style="float: left; clear: left;">
   <a><img src="{$document_left_top}" alt="left top document icon" /></a>
  </div>
  <div style="float: left;">
   <a><img src="{$document_center_top}" alt="center top document icon" /></a>
  </div>
  <div style="float: left;">
   <a><img src="{$document_right_top}" alt="right top document icon" /></a>
  </div>
   <div style="float: left; clear: left;">
   <a><img src="{$document_left_center}" alt="left center document icon" /></a>
  </div>
  <div style="float: left; width: 32px;">&nbsp;</div>
  <div style="float: left;">
   <a><img src="{$document_right_center}" alt="right center document icon" /></a>
  </div>
  <div style="float: left; clear: left;">
   <a><img src="{$document_left_bottom}" alt="left bottom document icon" /></a>
  </div>
  <div style="float: left;">
   <a><img src="{$document_center_bottom}" alt="center bottom document icon" /></a>
  </div>
  <div style="float: left;">
   <a><img src="{$document_right_bottom}" alt="right bottom document icon" /></a>
  </div>
</div>
