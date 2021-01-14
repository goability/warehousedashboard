<?php
namespace Ability\Warehouse;

if (is_null($this)){
  echo "No record ";
  Log::error("Resource was null");
  exit();
}
?>

<ul class="nav nav-tabs">
  <li class="nav-item active">
    <a class="nav-link" data-toggle="tab" href="#manage">Manage</a>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane container-content active" id="manage" style="margin:0px;">
        <?php
         require("formItemRecordStoragebin.php");?>
  </div>
</div>
