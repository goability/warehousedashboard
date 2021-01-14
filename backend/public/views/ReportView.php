<?php
namespace Ability\Warehouse;

/*
 Reports View - called from menuAdminRouter

 Vars defined in menuAdminRouter.php:
  $currentResource could be 'StorageFacility' OR the name field of the resource
  $recordID = 0 (resource type) or > 0 (if a record is loaded)


*/
//Not looking at a specific resource, show the dashboard
if (empty($currentResource)){

  if (isset($_GET['reportID'])){
    $reportID = $_GET['reportID'];
    require('./config/resources/reports/reportViewer.php');

  }
  else{
    require('./config/resources/reports/reportDashboard.php');
  }
}
