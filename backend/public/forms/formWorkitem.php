<?php

namespace Ability\Warehouse;

if (is_null($this)){
  echo "No record ";
  Log::error("Resource was null");
  exit();
}
?>

<ul class="nav nav-tabs">

  <li class="nav-item">
    <a class="nav-link active" data-toggle="tab" href="#actions">Open Requests</a>
  </li>

  <li class="nav-item">
    <a class="nav-link active" data-toggle="tab" href="#inprocess">In Process</a>
  </li>

  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#history">History</a>
  </li>
</ul>

<div class="tab-content">

  <div class="tab-pane container-content active" id="actions">
    <?php
        require("config/resources/resourceTab_Workitem_actions.php");
    ?>
  </div>
  <div class="tab-pane container-content active" id="inprocess">
    <?php
      require("config/resources/resourceTab_Workitem_inprocess.php");
    ?>
  </div>
  <div class="tab-pane container-content fade" id="history">
    <?php
      require("config/resources/resourceTab_Workitem_history.php")
    ?>
  </div>
</div>
