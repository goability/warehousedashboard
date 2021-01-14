<?php
namespace Ability\Warehouse;

class ReportManager
{

  static function getAccessibleReportsForUser($userID){

    $reportACL = array();

    $isAdmin    = SessionManager::IsAdministrator();
    $isClient   = SessionManager::IsClient();
    $isProvider = SessionManager::IsProvider();
    $isEmployee = SessionManager::IsEmployee();

    $reportACL[Constants\ReportCategories::INVENTORY] = array();

    //Add the inventory and requests reports
    if ($isAdmin || $isProvider){
      $reportACL[Constants\ReportCategories::INVENTORY][Constants\ReportNames::INVEN_PAL] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::INVEN_PAL];
      $reportACL[Constants\ReportCategories::INVENTORY][Constants\ReportNames::INVEN_PAL_PENDING] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::INVEN_PAL_PENDING];
      $reportACL[Constants\ReportCategories::INVENTORY][Constants\ReportNames::INVEN_BIN] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::INVEN_BIN];
      $reportACL[Constants\ReportCategories::INVENTORY][Constants\ReportNames::INVEN_CLIENT] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::INVEN_CLIENT];

      $reportACL[Constants\ReportCategories::REQUESTS][Constants\ReportNames::REQUESTS_STORAGE] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::REQUESTS_STORAGE];
      $reportACL[Constants\ReportCategories::REQUESTS][Constants\ReportNames::REQUESTS_SHIPMENT] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::REQUESTS_SHIPMENT];

    }
    if ($isAdmin || $isClient){
      $reportACL[Constants\ReportCategories::INVENTORY][Constants\ReportNames::INVEN_LOT] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::INVEN_LOT];
      $reportACL[Constants\ReportCategories::INVENTORY][Constants\ReportNames::INVEN_PROD] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::INVEN_PROD];
    }

    //Add the transaction reports
    if ($isAdmin || $isProvider){
      $reportACL[Constants\ReportCategories::TRANSACTIONS][Constants\ReportNames::TRANS_CLIENT] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::TRANS_CLIENT];
      $reportACL[Constants\ReportCategories::TRANSACTIONS][Constants\ReportNames::TRANS_EMPLOYEE] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::TRANS_EMPLOYEE];
    }
    $reportACL[Constants\ReportCategories::TRANSACTIONS][Constants\ReportNames::TRANS_RECEIVER] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::TRANS_RECEIVER];
    $reportACL[Constants\ReportCategories::TRANSACTIONS][Constants\ReportNames::TRANS_USER] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::TRANS_USER];

    //Add the people reports

    if ($isAdmin || $isProvider){
      $reportACL[Constants\ReportCategories::PEOPLE][Constants\ReportNames::PEOPLE_CLIENT] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::PEOPLE_CLIENT];
      $reportACL[Constants\ReportCategories::PEOPLE][Constants\ReportNames::PEOPLE_EMPLOYEE] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::PEOPLE_EMPLOYEE];
      $reportACL[Constants\ReportCategories::PEOPLE][Constants\ReportNames::PEOPLE_RECEIVER] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::PEOPLE_RECEIVER];

    }
    if ($isAdmin){
      $reportACL[Constants\ReportCategories::PEOPLE][Constants\ReportNames::PEOPLE_PROVIDER] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::PEOPLE_PROVIDER];
    }
    if ($isClient){
      $reportACL[Constants\ReportCategories::PEOPLE][Constants\ReportNames::PEOPLE_RECEIVER] = Constants\ReportNames::MENU_NAMES[Constants\ReportNames::PEOPLE_RECEIVER];
    }
    return $reportACL;
  }
  /*
  @param: $reportType - Constants\ReportType::
  */
  static function showReportNavbar($resource, $recordID, $reportType){

    $accessToken = SessionManager::GetParameter(Constants\SessionVariableNames::ACCESS_TOKEN);
    echo "<ul class=\"nav nav-pills\">";

    foreach ($resource->ReportConfig[$reportType] as $reportName=>$reportConfig) {

      echo "<li class=\"nav-item\">
            <a class=\"nav-link active\" href=\"/Report/$resource->Name/$recordID/$reportName?accessToken=$accessToken\">$reportName</a>
          </li>";
      }
      echo "</ul>";
  }
  /*
    @param: $associationCollection - array[name][foreignResourcesName] = displayText
  */
  static function showRecordAssociations($associationCollection){
    echo "<ul>";
    foreach ($associationCollection as $associativeCollectionName=>$foreignResources) {
      echo "<li><b>$associativeCollectionName</b></li>";
      echo "<ul>";
      foreach ($foreignResources as $foreignResourceName=>$associatedRecords){
        echo "<li>$foreignResourceName</li>";
        echo "<ul>";
        foreach ($associatedRecords as $displayText) {
            echo "<li>$displayText</li>";
        }
        echo "</ul>";
      }
      echo "</ul>";
    }
    echo "</ul>";
  }
  /*
  * Get report Data from data provider
  */
  static function GetReportData($reportID, $queryData, $userID, $keyFieldName=null, $groupByFieldName=null, $filterByData=null, $offsetValue, $limit){

    $reportData = null;
    $queryParams  = null;

    switch($reportID){
      case Constants\ReportNames::INVEN_PAL:

        $palletIDs     = isset($queryData['palletids']) ? $queryData['palletids'] : null;

        if (is_null($palletIDs))
        {
          return null;
        }

        $lotnumber        = isset($queryData['lotnumber']) ? $queryData['lotnumber'] : null;
        $tag              = isset($queryData['tag']) ? $queryData['tag'] : null;
        $includeEmpty     = isset($queryData['onlyShowEmpty']) ? $queryData['onlyShowEmpty'] : null;

        $inventory = InventoryManager::GetPalletInventory(null, $userID, $lotnumber, $tag, $includeEmpty);
        $reportData[""]   = $inventory;
        $reportData['maxRowCount'] = count($inventory);
        break;
      case Constants\ReportNames::INVEN_PAL_PENDING:

        $palletIDs     = isset($queryData['palletids']) ? $queryData['palletids'] : null;

        if (is_null($palletIDs))
        {
          return null;
        }

        $lotnumber        = isset($queryData['lotnumber']) ? $queryData['lotnumber'] : null;
        $tag              = isset($queryData['tag']) ? $queryData['tag'] : null;
        $includeEmpty     = isset($queryData['onlyShowEmpty']) ? $queryData['onlyShowEmpty'] : null;

        $inventory = InventoryManager::GetPendingPalletInventory(null, $userID, $lotnumber, $tag);
        $reportData[""]   = $inventory;
        $reportData['maxRowCount'] = count($inventory);
        break;
      case Constants\ReportNames::INVEN_BIN:

        $binIDs     = isset($queryData['binids']) ? $queryData['binids'] : null;
        $inventory = InventoryManager::GetBinInventory();
        $reportData[""] = $inventory;
        $reportData['maxRowCount'] = count($inventory);

        break;
      case Constants\ReportNames::INVEN_CLIENT:

        $inventory = InventoryManager::GetClientInventory();

        if (!empty($inventory)){

          $reportData[""] = $inventory;
          $reportData['maxRowCount'] = count($inventory);
        }
        break;
      case Constants\ReportNames::INVEN_PROD:

        $userID       = SessionManager::GetCurrentUserID();
        $inventory    = InventoryManager::GetClientInventory();

        foreach (array_values($inventory) as $storageItemID => $itemRowData) {
            $data[] = $itemRowData;
        }

        $reportData[""] = $data;
        $reportData['maxRowCount'] = count($data);

        break;
      case Constants\ReportNames::INVEN_LOT:

        $lotnumbers = isset($queryData['lotnumbers']) ?
                                  $queryData['lotnumbers'] : null;


        $userID      = SessionManager::GetCurrentUserID();
        $inventory   = InventoryManager::GetLotInventory($userID);

        foreach (array_values($inventory) as $storageItemID => $itemRowData) {
            $reportData[""] = $itemRowData;
        }

        $reportData['maxRowCount'] = count($reportData[""]);

        break;
      case Constants\ReportNames::PEOPLE_RECEIVER:

        $receivers = DataProvider::GetReceivers($userID);

        if(null!=$receivers && !empty($receivers)){
          $num_receivers = 0;
          $data = null;

          foreach ($receivers as $receiver) {
            $key              = $receiver['id'];
            $data[$key]       = $receiver;
            $num_receivers    += 1;
          }
          $reportData['maxRowCount'] = $num_receivers;
          $reportData[""] = $data;
        }

        break;
      case Constants\ReportNames::PEOPLE_CLIENT:

        $clientData = DataProvider::GetClients(SessionManager::GetCurrentUserID());
        $reportData['maxRowCount'] = count($clientData);
        $reportData[""] = $clientData;

        break;
      case Constants\ReportNames::PEOPLE_EMPLOYEE:

        $employees = DataProvider::GetEmployees(SessionManager::GetCurrentUserID());
        $num_employees = 0;
        //Need to remove the keys from each record for this usage
        foreach ($employees as $key => $value) {
          $reportData[$key] = array_values($value);
          $num_employees += count($reportData[$key]);
        }

        $reportData['maxRowCount'] = $num_employees;

        break;


      case Constants\ReportNames::TRANS_USER:
      case Constants\ReportNames::TRANS_RECEIVER:
      case Constants\ReportNames::TRANS_CLIENT:
      case Constants\ReportNames::TRANS_EMPLOYEE:
        $data = DataProvider::GetTransactionData($reportID, $userID, $keyFieldName, $groupByFieldName, $filterByData, $offsetValue, $limit);
        $reportData = $data;
        break;

      case Constants\ReportNames::TRANS_PROVIDER:
        // TODO:
        break;

      case Constants\ReportNames::TRANS_BIN:
        // TODO:
        break;
      case Constants\ReportNames::TRANS_PAL:
        // TODO:
        break;
      case Constants\ReportNames::REQUESTS_STORAGE:
        $reportData[""] = DataProvider::GetStorageRequestsByStocker();
        break;
      case Constants\ReportNames::REQUESTS_SHIPMENT:
        $reportData[""] = DataProvider::GetShippingRequestsByStocker();
        break;
      default:
        break;
    }

    return $reportData;
  }

  /*
  * @param $filterData [0]resourceName [1]selectedID null OR recordID
  */
  static function GetReportFilterSubMenu($reportID, $filterByResourceName, $selectedID=null, $componentID="recordFilterID", $offsetValue){

    $perPageInterval        = isset($_POST['resultsMaxPerPageInterval']) ? $_POST['resultsMaxPerPageInterval'] : DataProvider::$PerPageDefault;

    $html = '';
    $currentUserID  = SessionManager::GetCurrentUserID();
    $isProvider     = SessionManager::IsProvider();
    $isClient       = SessionManager::IsClient();
    $isEmployee     = SessionManager::IsEmployee();

    if ($isProvider){
      $aclProviders     = SessionManager::GetAccessibleRecordIDs("Provider");//get providers which this user has full access
      $providerIDs      = array();
      foreach ($aclProviders as $provider) {
        $providerIDs[] = intval($provider["id"]);
      }
    }

    switch($reportID){
      case Constants\ReportNames::INVEN_CLIENT:
        $accessibleRecordIDs  = DataProvider::GetClients($providerIDs);
        break;
      case Constants\ReportNames::INVEN_BIN:
        $accessibleRecordIDs  = DataProvider::GetOwnedBins($providerIDs);
        break;
      case Constants\ReportNames::TRANS_EMPLOYEE:
        $accessibleRecordIDs = DataProvider::GetEmployees()["provider"];
        break;
      case Constants\ReportNames::TRANS_PROVIDER:
        $accessibleRecordIDs = DataProvider::GetClients();
        break;
      case Constants\ReportNames::TRANS_CLIENT:
        $accessibleRecordIDs = DataProvider::GetClients($currentUserID);
        break;
      case Constants\ReportNames::TRANS_BIN:
        $accessibleRecordIDs  = DataProvider::GetOwnedBins($providerIDs);
        break;
      case Constants\ReportNames::TRANS_PAL:
        $accessibleRecordIDs = DataProvider::GetOwnedPallets($providerIDs);
        break;
      case Constants\ReportNames::TRANS_RECEIVER:
        $receiverIDs = DataProvider::GetReceivers($currentUserID);
        $accessibleRecordIDs = is_null($receiverIDs) || empty($recieverIDs) ? null : array_values($receiverIDs)[0];
        break;
      case Constants\ReportNames::TRANS_USER:
        $accessibleRecordIDs = array(array("id"=>$currentUserID, "name" => SessionManager::GetCurrentUserName()));
        break;
      }

      $filterByResourceName = NAME_SPACE . "\\" . $filterByResourceName;
      $resource = new $filterByResourceName(0);
      $accessToken = SessionManager::GetAccessToken();
      $currentURL = '?' . $_SERVER['QUERY_STRING'];
      $formID = 'formRecordFilterSelect';
        $html .= "<form style='display:inline;' class='none' id='$formID' action='$currentURL' method='POST'>";

        $html .= $resource->buildSelectCombo(true, $accessibleRecordIDs, $selectedID, $componentID, null, null, null, $formID);
        $html .= "<input type='hidden' id='resultsMaxPerPageInterval' name='resultsMaxPerPageInterval' value=$perPageInterval>";
        $html .= "<input type='hidden' id='offsetValue' name='offsetValue' value=$offsetValue>";

        $html .= "<span></span>";
        $html .= "</form>";

    return $html;
  }
}
