<?php
namespace Ability\Warehouse;

$IsAdmin = SessionManager::IsAdministrator();

if (is_null($this)){
  echo "No record ";
  Log::error("Resource was null");
  exit();
}

//Get the current tab

$currentTab = isset($_GET['tabid']) ? $_GET['tabid'] : 'actions' ;

?>

<ul class="nav nav-tabs">

  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#contentprofile" id="navprofile">Profile</a>
  </li>
  <?php if (!$IsAdmin){?>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#contentactions" id="navactions">Action needed</a>
  </li>

  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#contentclients" id="navclients">Clients</a>
  </li>

  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#contentemployees" id="navemployees">Employees</a>
  </li>

  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#contenthistory" id="navhistory">History</a>
  </li>
<?php } ?>
</ul>

<div class="tab-content">

  <div class="tab-pane container-content fade" id="contentprofile" style="margin:0px;">
        <?php
         require("formItemRecordGeneric.php");?>
  </div>

    <?php if (!$IsAdmin){?>
  <div class="tab-pane container-content" id="contentactions">
    <?php
      require("config/resources/resourceTab_Provider_actions.php")
    ?>
  </div>
  <div class="tab-pane container-content fade" id="contentclients">
    <?php
      require("config/resources/resourceTab_Provider_clients.php")
    ?>
  </div>
  <div class="tab-pane container-content fade" id="contentemployees">
    <?php
      require("config/resources/resourceTab_Provider_employees.php")
    ?>
  </div>

  <div class="tab-pane container-content fade" id="contenthistory">
    <?php
      require("config/resources/resourceTab_Provider_history.php")
    ?>
  </div>
<?php } ?>

</div>
<script>
  $("#<?php echo "nav$currentTab"; ?>").addClass("active");
  $("#<?php echo "content$currentTab"; ?>").removeClass("fade");
  $("#<?php echo "content$currentTab"; ?>").addClass("active");

  <?php
    if ($currenTab="profile"){
      ?>
      var current = $("#formSelect").attr("action");
      var tabID = "&tabid=profile";
      $("#formSelect").attr("action", current+tabID);
    <?php
    }
    ?>

</script>
