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
?>


  <nav class="navbar navbar-expand-lg navbar-light bg-light">

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarReportBar" aria-controls="navbarReportBar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <?php if (!empty($inventoryReports)){ ?>
    <div class="collapse navbar-collapse" id="navbarReportBar">
      <b>Inventory</b>
      <div class="navbar-nav">

        <?php
        foreach ($inventoryReports as $key => $value) {

           echo "<a class='nav-item nav-link'
               href='$reportURL" . "$key' id=nav-item-report-$key>$value</a>";

          }?>
      </div>
      <?php } ?>
          <?php if (!empty($transactionReports)){ ?>
      <b>Transactions</b>
      <div class="navbar-nav">

        <?php
        foreach ($transactionReports as $key => $value) {

           echo "<a class='nav-item nav-link'
               href='$reportURL" . "$key' id=nav-item-report-$key>$value</a>";

          }?>
      </div>
    <?php } ?>
        <?php if (!empty($peopleReports)){ ?>
      <b>People</b>
      <div class="navbar-nav">

        <?php
        foreach ($peopleReports as $key => $value) {

           echo "<a class='nav-item nav-link'
               href='$reportURL" . "$key' id=nav-item-report-$key>$value</a>";

          }?>
      </div>
    <?php } ?>
    </div>
  </nav>
  <script type="text/javascript">

  $("#nav-item-report-<?php echo $reportID; ?>").addClass('nav-report-active');

  </script>

  <?php
  ?>
