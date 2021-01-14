<?php
namespace Ability\Warehouse;

$topNavMenuItems  = SessionManager::GetNavigationResources();
$apiURL           = ConfigurationManager::GetParameter("APIURL");
$siteURL          = ConfigurationManager::GetParameter("SiteURL");
//Not showing User on the dashboard

unset($topNavMenuItems["User"]);

// TODO: this is dirty, push to config or lower level ACL READ ref
//   also repeated in MenuTop
$userIsAdmin = SessionManager::IsAdministrator();

if (!$userIsAdmin){

  if (SessionManager::GetParameter(Constants\SessionVariableNames::SINGLE_PROVIDER)){
    unset($topNavMenuItems['Provider']);
  }

}
?>
<div class="content-container">
  <div class="row">

      <div class="card-deck">
        <?php
        if ($userIsAdmin && false){
          ?>
            <div class="col-6" onclick="window.location='<?php echo $siteURL . "/Utility" . "?accessToken=$accessToken";?>'">
              <?php
                require("config/resources/summaryCard_Utility.php");
              ?>
            </div>

            <div class="col-6" onclick="window.location='<?php echo $siteURL . "/Configuration" . "?accessToken=$accessToken";?>'">
              <?php
                require("config/resources/summaryCard_Configuration.php");
              ?>
            </div>
        <?php }


        if (SessionManager::IsProvider()){
          ?>
            <div class="col-6" onclick="window.location='<?php echo $siteURL . "/Provider" . "?accessToken=$accessToken&tabid=clients";?>'">
              <?php
                require("config/resources/summaryCard_Clients.php");
              ?>
            </div>

            <div class="col-6" onclick="window.location='<?php echo $siteURL . "/Report" . "?accessToken=$accessToken";?>'">
              <?php
                require("config/resources/summaryCard_Report.php");
              ?>
            </div>
        <?php }

          foreach ($topNavMenuItems as $menuItemResource => $menuItemConfig) {

              $menuItemResourceName = $menuItemConfig["resourceName"];

              $menuItemURL          = $menuItemConfig["url"];
              $displayText          = $menuItemConfig["displayText"];
              $resourceImageHeader  = $menuItemConfig["resourceImageLarge"];

              $resourceSummaryCard  = "config/resources/summaryCard_Generic.php";
              $f = "config/resources/summaryCard_$menuItemResourceName.php";
              if(file_exists($f)){
                $resourceSummaryCard  = "config/resources/summaryCard_$menuItemResourceName.php";
              }
              $resourceURL = $siteURL . $menuItemURL . "?accessToken=$accessToken";
          ?>
              <div class="col-6" onclick="window.location='<?php echo $resourceURL;?>'">
                <?php require($resourceSummaryCard);?>
              </div>


          <?php }?>
          <?php
          if (!SessionManager::IsProvider()){
          ?>

        <div class="col-6" onclick="window.location='<?php echo $siteURL . "/Report" . "?accessToken=$accessToken";?>'">
          <?php
            require("config/resources/summaryCard_Report.php");
          ?>
        </div>
              <?php }?>

    </div>
  </div>
</div>
