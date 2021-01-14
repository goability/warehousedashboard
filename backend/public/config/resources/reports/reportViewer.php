<?php
namespace Ability\Warehouse;

require("navReports.php");


$currentUserID = SessionManager::GetCurrentUserID();

if (SessionManager::IsProvider()){
  $aclProviders     = SessionManager::GetAccessibleRecordIDs("Provider");//get providers which this user has full access
  $providerIDs      = array();
  foreach ($aclProviders as $provider) {
    $providerIDs[] = intval($provider["id"]);
  }
}

$queryData = array();
$reportStartTime = microtime(true);
$reportQueryTime = 0;
$recordIDs = null;
$reportFound = true;
$resourceName = '';

//Get report title and column headers
$reportConfigData = ConfigurationManager::GetReportConfig($reportID);
$isGrouped        = $reportConfigData['isGrouped'];
$groupByFieldName = isset($reportConfigData['groupByFieldName']) ?
                      $reportConfigData['groupByFieldName'] : null;


//THis logic has three back to back switches, one for each type (inv, trans, people)
//First handle the Inventory cases, set flag if satisfied, skips second switch

// INVENTORY Report data
switch ($reportID) {

  case Constants\ReportNames::INVEN_PAL:
    $getParamName = 'palletid';
    $resourceName = 'Storagepallet';
    break;
  case Constants\ReportNames::INVEN_PAL_PENDING:
    $getParamName = 'palletid';
    $resourceName = 'Storagepallet';
    break;

  case Constants\ReportNames::INVEN_BIN:
    $getParamName = 'binid';
    $resourceName = 'Storagebin';
    $filterByResourceName = 'Storagebin';
    $filterByTableName = 'storagebin';
    $filterByFieldName  = 'id';
    break;
  case Constants\ReportNames::INVEN_CLIENT:
    $getParamName = 'clientid';
    $resourceName = 'User';
    break;
  case Constants\ReportNames::INVEN_PROD:
    $getParamName = 'itemid';
    $resourceName = 'Storageitem';
    // TODO: this is not used as designed, see ReportManager, it gets all the owned ones there anyway
    //    original design was to pass recordIds from this layer to the next, but it's now mixed bag
    break;
  case Constants\ReportNames::INVEN_LOT:
    $getParamName = 'lotnumber';
    $aclData = '';//Creating this variable prevents un-necessary query for owned objects, which is done at next level anyway
    break;
  case Constants\ReportNames::PEOPLE_RECEIVER:
    $getParamName = 'clientid';
    $resourceName = 'User';
    break;
  case Constants\ReportNames::PEOPLE_CLIENT:
    $getParamName = 'clientid';
    $resourceName = 'User';
    break;
  case Constants\ReportNames::PEOPLE_EMPLOYEE:
    $getParamName = 'employeeid';
    $resourceName = 'User';

    break;
  case Constants\ReportNames::TRANS_CLIENT:
    $getParamName         = 'clientid';
    $resourceName         = 'User';
    $filterByResourceName = 'User';
    $filterByTableName    = 'clients';
    $filterByFieldName    = 'id';
    break;
  case Constants\ReportNames::TRANS_USER:
    $getParamName = 'userid';
    $resourceName = 'User';
    $filterByResourceName = 'User';
    $filterByTableName = 'transactions';
    $filterByFieldName  = 'userid';
    break;
  case Constants\ReportNames::TRANS_RECEIVER:
    $getParamName = 'userid';
    $resourceName = 'User';
    $filterByResourceName = 'User';
    $filterByTableName = 'receivers';//derived in custom GetTransactionData query
    $filterByFieldName  = 'id';
    break;
  case Constants\ReportNames::TRANS_PROVIDER:
    $getParamName = 'providerid';
    $resourceName = 'Provider';
    $filterByResourceName = 'User';
    $filterByTableName = 'clients';
    $filterByFieldName  = 'id';
    break;
  case Constants\ReportNames::TRANS_EMPLOYEE:
    $getParamName = 'userid';
    $resourceName = 'User';
    $filterByResourceName = 'User';
    $filterByTableName = 'employees';//derived in custom getpendingItems query
    $filterByFieldName  = 'id';
    break;

  case Constants\ReportNames::TRANS_PAL:
    $getParamName = 'palletid';
    $resourceName = 'Storagepallet';
    $filterByResourceName = 'Storagepallet';
    $filterByTableName = 'storagepallet';//derived in custom getpendingItems query
    $filterByFieldName  = 'id';
    break;
  case Constants\ReportNames::TRANS_BIN:
    $getParamName = 'binid';
    $resourceName = 'Storagebin';
    $filterByResourceName = 'Storagebin';
    $filterByTableName = 'storagebin';//derived in custom getpendingItems query
    $filterByFieldName  = 'id';
    break;
  case Constants\ReportNames::REQUESTS_STORAGE:
    $getParamName = 'palletid';
    $resourceName = 'Storagepallet';
    break;
    case Constants\ReportNames::REQUESTS_SHIPMENT:
      $getParamName = 'palletid';
      $resourceName = 'Storagepallet';
      break;


  default:
    $reportFound = false;
    break;

}

if ($reportFound){
  $queryDataIndexName = $getParamName . "s";
  //one item is requested (instead of everything this user has access to)
  if (isset($_GET[$getParamName])){
    $queryData[$queryDataIndexName] = $_GET[$getParamName];
  }
  if ( !isset($aclData) ){

    //Either way, things are always restricted by the records this user has access to
    if (empty($queryData)){

      $aclData = SessionManager::GetAccessibleRecordIDs($resourceName);

      if (!empty($aclData)){
        foreach ($aclData as $dataitem) {
          $recordIDs[] = intval($dataitem["id"]);
        }
        $queryData[$queryDataIndexName] = $recordIDs;
      }
    }
  }
}
//Report Query data has been collected

$queryData['onlyShowEmpty'] = 0;
$filterByData = null;


$perPageInterval  = isset($_POST['resultsMaxPerPageInterval']) ? $_POST['resultsMaxPerPageInterval'] : DataProvider::$PerPageDefault;
$offsetValue      = isset($_POST['offsetValue']) ? $_POST['offsetValue'] : 0;

//If this resource needs a filter, it should have one set above
if (isset($filterByResourceName)){

  $selectedFilterRecordID = isset($_POST['recordFilterID']) && $_POST['recordFilterID']>0 ? $_POST['recordFilterID'] : null;
  $filterByData           = array($filterByTableName, $filterByFieldName, $selectedFilterRecordID);

  if (is_null($selectedFilterRecordID)){
    //Do not allow to show all resources, force an initial selection
    $queryData=null;//Force an empty report
    $filterByData = null;
  }
  $recordFilterSelectElement = ReportManager::GetReportFilterSubMenu($reportID, $filterByResourceName, $selectedFilterRecordID, "recordFilterID", $offsetValue, $perPageInterval);
}
else{
  $recordFilterSelectElement = "";
}

$reportData = ReportManager::GetReportData($reportID, $queryData, $userID, $getParamName, $groupByFieldName, $filterByData, $offsetValue, $perPageInterval);
$reportQueryTime = microtime(true) - $reportStartTime;
$reportQueryTime = round($reportQueryTime,2);
$maxRowCount = (isset($reportData['maxRowCount'])) ? $reportData['maxRowCount'] : -1;

//------------------------------
// Ready to show the data
//------------------------------

// Pagination and Per-Page Logic
// Build extra Page-Nav menus if max results is more than minimum configured
$resultsPerPageHTML = $paginationHTML = "";
if ($maxRowCount>$perPageInterval){

  // Max Per page Logic
  //  Show ranges up to maximum records returned, interval is same as per-page for now

  $maxPerPageLimits   = DataProvider::$MaxSqlLimits;
  $perPageMin         = DataProvider::$PerPageMin;
  $resultsPerPageHTML = "<b>results/page:";

  for ($i=$perPageMin; $i < $maxRowCount; $i+=$perPageMin) {
      if ($i>$maxPerPageLimits){
        break;
      }
      if (($maxRowCount-$i)>0){
        $resultsPerPageHTML .= "<button class='btn btn-sm";
        if ($i==$perPageInterval){
          $resultsPerPageHTML .= " btn-primary";
        } else{
          $resultsPerPageHTML .= " btn-link";
        }

        $resultsPerPageHTML .= "' id='rangePerPageButton$i' onClick='PWH_UIService.reportNavigationPerPageIntervalChange($perPageInterval, $i)'>$i</button>";
      }
  }

  // Pagination Logic
  // divide into view ranges

  $paginationHTML = "  <b>show</b>:";

  for ($i=1; $i < $maxRowCount; $i+=$perPageInterval) {
      if (($maxRowCount-$i)>0){
        $nextIndex = (($maxRowCount-$i)>$perPageInterval) ? ($i + $perPageInterval) : $maxRowCount;
        $paginationHTML .= "<button class='btn btn-sm";
        if ($i==$offsetValue){
          $paginationHTML .= " btn-primary";
        }
        else{
          $paginationHTML .= " btn-link";
        }

        $paginationHTML .= "' id='indexStartButton$i' onClick='PWH_UIService.reportNavigationPageIndexStart($offsetValue, $i)'>$i - $nextIndex</button>";
      }
  }
}

echo "<table><tr><td>";

echo "<H4>" . $reportConfigData['title'] . "</H4>";
echo "</td>";
if ($maxRowCount>0){
  echo "<td style='background:Silver; border-radius:5px; padding: 2px;'>";

    if ($recordFilterSelectElement!=''){
      echo " <b>Filter By</b>: " . $recordFilterSelectElement;//Show only if filtering
    }

    echo "<span class='fa fa-print' onclick='window.print();'><b>Found</b>: $maxRowCount </span>";
    echo "<span class='fas fa-stopwatch' style='padding-left:5px;'>&nbsp;${reportQueryTime}s</span>";
    echo "</td>";
}
if ($maxRowCount>$perPageInterval){
  echo "<td style='background:Tan; border-radius:5px; padding: 2px;'>";
  echo $resultsPerPageHTML .$paginationHTML;
  echo "</td>";
}
echo "</tr></table>";

if (empty($reportData)){
  echo "<br>No report data";
}
else{
?>

<table class='table'>
  <tbody>
    <?php
    foreach ($reportData as $rowKey=>$reportDataRow) {
        if($rowKey=='maxRowCount'){
          continue;
        }
        if ($isGrouped){
          $rowHeaderData = $rowKey;
          $numColumns = count($reportConfigData["col_headers"]);
          echo "<tr><td colspan=$numColumns><h4>$rowHeaderData</h4></td></tr>";
        }
        ?>
        <tr>
        <?php
            foreach ($reportConfigData["col_headers"] as $colName) {
              echo "<th scope='col'>$colName[0]</th>";
            }
        ?>
        </tr>
        <?php
        if (!empty($reportDataRow)){
          foreach ($reportDataRow as $rowData) {
             ?>
              <tr>
                  <?php
                  foreach ($reportConfigData["col_headers"] as $colName) {
                    echo "<td>";
                    $colFieldName = $colName[1];
                    echo isset($rowData[$colFieldName]) ? $rowData[$colFieldName] : "";
                    echo "</td>";
                   }
                  ?>
              </tr>
              <?php
            }
          }
    }
    ?>
  </tbody>
</table>
<?php
}
?>
