<?php

namespace Ability\Warehouse;

$apiURL   = ConfigurationManager::GetParameter("APIURL");
$siteURL  = ConfigurationManager::GetParameter("SiteURL");
$reportURL = $siteURL . "/Report?accessToken=$accessToken&reportID=";
$reports = ReportManager::getAccessibleReportsForUser(SessionManager::GetCurrentUserID());

$inventoryReports   = array_intersect_key($reports, $keys = array(Constants\ReportCategories::INVENTORY => 1))[Constants\ReportCategories::INVENTORY];
$transactionReports = array_intersect_key($reports, $keys = array(Constants\ReportCategories::TRANSACTIONS => 1))[Constants\ReportCategories::TRANSACTIONS];

if(array_key_exists(Constants\ReportCategories::PEOPLE, $reports)){
  $peopleReports      = array_intersect_key($reports, $keys = array(Constants\ReportCategories::PEOPLE => 1))[Constants\ReportCategories::PEOPLE];
}
if (array_key_exists(Constants\ReportCategories::REQUESTS, $reports)){
  $requestReports     = array_intersect_key($reports, $keys = array(Constants\ReportCategories::REQUESTS => 1))[Constants\ReportCategories::REQUESTS];
}
?>
<h1>Reports</h1>
<div class="container-content">

  <div class="row">
    <div class="col-sm-6">
      <?php if (!empty($inventoryReports)) {
        echo "<h3>Inventory Listings</h3>";

      } ?>
      <ul class='list-group list-group-item-action'>
        <?php
        foreach ($inventoryReports as $key => $value) {
          echo "<a href='$reportURL" . "$key'
           class='list-group-item list-group-item-action'>$value</a>";
          }?>
      </ul>
    </div>
    <div class="col-sm-6">
      <?php
      if (!empty($transactionReports)){

       echo "<h3>Transactions</h3>";
      }?>
      <ul class='list-group list-group-item-action'>
        <?php
        foreach ($transactionReports as $key => $value) {
          echo "<a href='$reportURL" . "$key'
           class='list-group-item list-group-item-action'>$value</a>";
          }?>
      </ul>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6">
      <?php
      if (!empty($peopleReports)){
        echo "<h3>People</h3>";
      ?>
      <ul class='list-group list-group-item-action'>
        <?php
        foreach ($peopleReports as $key => $value) {
          echo "<a href='$reportURL" . "$key'
           class='list-group-item list-group-item-action'>$value</a>";
          }?>
      </ul>
    </div>
    <?php
  }
    if (!empty($requestReports)){
      ?>
    <div class="col-sm-6">
      <?php

        echo "<h3>Requests</h3>";
      ?>
      <ul class='list-group list-group-item-action'>
        <?php
        foreach ($requestReports as $key => $value) {
          echo "<a href='$reportURL" . "$key'
           class='list-group-item list-group-item-action'>$value</a>";
          }?>
      </ul>
    </div>
  <?php } ?>
  </div>


</div>
