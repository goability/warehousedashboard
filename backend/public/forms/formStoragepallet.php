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
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#content">Inventory</a>
  </li>

  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#history">History</a>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane container-content fade" id="content"><?php require("config/resources/resourceTab_Pallet_content.php");?></div>
  <div class="tab-pane container-content fade" id="history"><?php require("config/resources/resourceTab_Pallet_history.php");?></div>
  <div class="tab-pane container-content active" id="manage" style="margin:0px;">
        <?php
         require("formItemRecordStoragepallet.php");?>
  </div>
</div>
