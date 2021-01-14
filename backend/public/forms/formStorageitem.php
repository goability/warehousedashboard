<?php

namespace Ability\Warehouse;

if (is_null($this)){
  echo "No record ";
  Log::error("Resource was null");
  exit();
}
?>

<ul class="nav nav-tabs">
  <li class="nav-item ">
    <a class="nav-link active" data-toggle="tab" href="#manage">My Items</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#items">In Storage</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#staged">In Process</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#history">History</a>
  </li>
</ul>

<div class="tab-content">
  <div class="tab-pane container-content fade" id="items"><?php require("config/resources/resourceTab_Storageitem_stored.php");?></div>
  <div class="tab-pane container-content fade" id="staged"><?php require("config/resources/resourceTab_Storageitem_staged.php");?></div>
  <div class="tab-pane container-content fade" id="history"><?php require("config/resources/resourceTab_Storageitem_history.php");?></div>
  <div class="tab-pane container-content active" id="manage" style="margin:0px;">
        <?php
         require("formItemRecordStorageitem.php");?>
  </div>
</div>
