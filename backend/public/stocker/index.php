<?php
namespace Ability\Warehouse;

try
{
  set_include_path(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."includes".DIRECTORY_SEPARATOR);
  require_once("autoload-handheld.php");
}
catch (Exception $e){
  error_log("Error occured during class autoloading for HAND-HELD...");
  die();
}

try {
    Log::init('Handheld');
} catch (\Exception $e) {
  $msg = "Error with logger setup for Handheld";
  error_log($msg . $e->getMessage());
  echo $msg;
  die();

}

  //Publicwarehouse Handheld view for employees and inventory movement
  /*
  * Design :  SPA
  *
  *  Routes:  / - defaults to login
  *           /scan - something is scanned - bin or pallet
  *           /search - not implemented
  *           /store  - store a storagerequest onto a pallet
  *           /ship   - Ship a shipmentRequest FROM a pallet
  *
  *  HANDHELD/SMALL DEVICE - MAX WIDTH 202px
  */

  SessionManager::ResumeSession();


  $userName       = "";
  $loggedin       = false;
  $statusMessage  = '';
  $searchType     = '';
  $currentUserID = SessionManager::GetCurrentUserID();
  $actionType = '';
  $clientScanMode = 0;
  $url_logout = '';
  $currentPalletItemName = $currentPalletLotNumber = $currentPalletBinName = '';
  $currentPalletItemID = $currentPalletOwnerID = $currentPalletIsBatched = $currentPalletStorageRequestID = $qtyRemaining = 0;
  $currentFacilityName = '';



  if (isset($_GET['searchType'])){
     $searchType = $_GET['searchType'];
     $statusMessage = "Search by $searchType";
   }

   //Load all of the configurations
   ConfigurationManager::LoadAllResourceConfigs();
   //Load all prepare statements for loaded resources
   DataProvider::LoadPrepareStatements();

  $deployedInSubdir = ConfigurationManager::GetParameter('StockerDeployInSubdir');
  $url_base         = ConfigurationManager::GetParameter('StockerDeployInSubdir') ? "/stocker" : "";
  $enforcePalletScanOnStore   = ConfigurationManager::GetParameter('ScanningModes')->enforcePalletScanOnStore;
  $enforceBinScanOnStore      = ConfigurationManager::GetParameter('ScanningModes')->enforceBinScanOnStore;
  $enforcePalletScanOnShip    = ConfigurationManager::GetParameter('ScanningModes')->enforcePalletScanOnShip;
  $enforceBinScanOnShip       = ConfigurationManager::GetParameter('ScanningModes')->enforceBinScanOnShip;


  $route_params = Util::get_parameters($deployedInSubdir);
  $resource   = $route_params["resource"];
  $resourceID = $route_params["resourceID"];



  //Grab any authCodes or AccessTokens from Get or Post, these are compared against any session tokens
  if (!SecurityManager::SetTokens()){
    Log::error("SECURE FAILURE while trying to get tokens from post or get");
    echo "APPLICATION SECURITY FAILURE WITH TOKENS";
    return;
  }

  $accessToken  = SecurityManager::$AccessToken;
  $authCode     = SecurityManager::$AuthCode;

  $query_param_access_token = "accessToken=$accessToken";

  if (!is_null($accessToken)){

    $loggedin = SessionManager::IsActive($accessToken);
      Log::debug("===== SESSION WAS ACTIVE checking $accessToken ");
  }
  if (!$loggedin && isset($accessToken)){
    Log::debug("===== SESSION NOT ACTIVE checking $accessToken ");
    $statusMessage = "Session expired";
    $resource = "Login";
    $currentUserID = SessionManager::GetCurrentUserID();
    DataProvider::DELETE_AUTHORIZATIONS($currentUserID);
    SessionManager::DeleteParameter(Constants\SessionVariableNames::CLIENT_PALLET_SCAN_MODE);
    SessionManager::DeleteParameter(Constants\SessionVariableNames::CURRENT_FACILITYID);
    SessionManager::DeleteParameter(Constants\SessionVariableNames::CURRENT_FACILITYNAME);

  }
  else if ($loggedin){

    $userName = SessionManager::GetCurrentUsername();

    $clientIDs = SessionManager::GetClientIDs();//gets clients of this worker's employer

  }

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Stocker Console</title>
    <script type="text/javascript" src="<?php echo ConfigurationManager::GetParameter("SiteURL") . "/scripts/General.js" ?>"></script>
  </head>
  <body>
    <?php

    if (!$loggedin){
      $viewToInclude = "login.inc";
      $statusMessage = "Please login";

      switch ($resource) {
        case "login":

          $loggedin = false;
          if ( isset($_POST['username']) ){
          //  Log::debug("Post data was set");

            $username     = $_POST['username'];
            $rawpassword  = $_POST['password'];

            $authCodeData = DataProvider::LOGIN($username, $rawpassword);

            //If an authcode is received, go ahead an request the access token
            //  Note that for the public facing site, this is verified one more time
            //    by forwarding back to the /login URL which then checks the authCode
            //    this logic is in the menu_admin_router for the site

            if (!empty($authCodeData)){

              $userID   = $authCodeData[0];
              $authCode = $authCodeData[1];

              //Create a resource for this user
              $userRecord = new User($userID);
              $userName = $userRecord->GetDisplayText();


              //User must be an employee to use these features
              if (!DataProvider::IsEmployee($userID)){
                Log::debug("This user $userID is NOT an employee.");

                $statusMessage = "$userName is not an employee.";
              }
              else{
                Log::debug("User is an employee");

                //Now request an accessToken using the userID and auth
                $timeoutSecs = ConfigurationManager::GetParameter("Sessioning")->HandheldSessionTimeout;

                $accessTokenData = DataProvider::SET_ACCESS_TOKEN($userID, $authCode, $timeoutSecs);
                $accessToken      = $accessTokenData["accessToken"];
                $expiresUnixTime  = $accessTokenData["expires_unix_time"];

                // Start the session and set some data that should be good for the session
                SessionManager::StartSession($userID, $accessToken, $expiresUnixTime);

                $currentUserID = $userID;
                Log::debug("Handheld Session started [$accessToken] expiretime is [$expiresUnixTime]");

                SessionManager::SetParameter(Constants\SessionVariableNames::IS_ADMIN, $userRecord->IsAdministrator);
                SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_EMAIL, $userRecord->DB_Fields["emailaddress"]);
                SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_USER, $userRecord->DB_Fields["profilename"]);
                SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_USER_FULL_NAME, $userRecord->GetDisplayText());
                SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_USER_NICK_NAME, $userRecord->GetField('firstname'));

                //If there is only one facility, just set it now
                $facilities = SessionManager::GetFacilitiesForEmployee($currentUserID);

                if (!empty($facilities) && count($facilities)==1){
                  $currentFacilityID   = $facilities[0]['id'];
                  $currentFacilityName = $facilities[0]['shortcode'];
                  $showFacilityCodeInFooter = false;

                  SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_FACILITYID, $currentFacilityID);
                  SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_FACILITYNAME, $currentFacilityName);
                }

                //// TODO: Grab some other data that most likely will not change and will save DB hits

                $loggedin = true;
                Transaction::Login($currentUserID);

              }

            }
            // Now forward the page to the main / without a route, defaults to dashboard
            if (!$loggedin){
                $statusMessage = "<span style='color:Red;'>Login error.<br>$statusMessage</span>";
            }
            else{
              echo "<script type='text/javascript'>
              window.location.assign('dashboard?accessToken=$accessToken&firstLogin=1');
              </script>";
              //Script ends here, window is forward back with the accessToekn
            }
          }
          break;
      }
    }
    else{  // User is logged in

      $viewToInclude = "search_results.inc";
      $storageRequests  = DataProvider::GetStorageRequestsByStocker();
      $shippingRequests = DataProvider::GetShippingRequestsByStocker();
      $totalcountStoreTickets = DataProvider::GetOpenStorageRequests();
      $totalcountShipTickets = DataProvider::GetOpenShippingRequests();
      $openRequestsText = "Ship:$totalcountShipTickets Store:$totalcountStoreTickets";
      $allowCloseTicket = false;
      $currentFacilityName = '';

      //load the session params
      $last_item_name   = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_ITEM);
      $storageRequestId = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_STORAGE_REQUEST_ID);
      $last_qty         = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_QTY);
      $last_binName     = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_BIN);


      //IF this comes in, it is a toggle
      if (isset($_POST['clientScanMode'])){
        $clientScanMode = !$_POST['clientScanMode'];
        SessionManager::SetParameter(Constants\SessionVariableNames::CLIENT_PALLET_SCAN_MODE, $clientScanMode);
        //if (!$clientScanMode){
          $viewToInclude  = 'dashboard.inc';
      //  }
      }
      else{
        $clientScanMode  = SessionManager::GetParameter(Constants\SessionVariableNames::CLIENT_PALLET_SCAN_MODE);

      }

      $currentFacilityID    = SessionManager::GetCurrentFacility($currentUserID);
      $currentFacilityName  = SessionManager::GetParameter(Constants\SessionVariableNames::CURRENT_FACILITYNAME);

      if ($resource!="logout"){
        require('menuTop.inc');
      }
      switch($resource){

        case "clienttoggle":
          $displayMode = "INFO";

          //Setting from a form, change the current facility
          if (isset($_POST['facilityid-scanned'])){
            $currentFacilityID = $_POST['facilityid-scanned'];
            SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_FACILITYID, $currentFacilityID);
            $facility = new Storagefacility($currentFacilityID);
            $currentFacilityName = $facility->GetField('shortcode');
            SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_FACILITYNAME, $currentFacilityName);

            $viewToInclude = "dashboard.inc";
            //echo "set: [$currentFacilityName]";
          }

          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_STORAGE_REQUEST_ID);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_QTY);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_BIN);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_ITEM);




          break;
        case "scan":
          $displayMode = "INFO";

          $searchString = isset($_POST['searchString']) ? $_POST['searchString'] : null;

          //Something is there, identify it as BIN or PID
          if (!is_null($searchString) && !empty($searchString)){

            //try to get the pallet first
            $foundPalletObject = DataProvider::GetPalletByName($searchString, $currentUserID);
            $countPallets = count($foundPalletObject);

            if ($countPallets>1){

              $firstPalletID = $foundPalletObject[0]["id"];
              $currentPallet          = new StoragePallet($firstPalletID);


              $currentPalletOwnerID   = 0;//Bug $currentPallet->GetField('providerid');
              $currentPalletName      = $currentPallet->GetField('name');
              $currentPalletIsBatched = $currentPallet->GetField('repeatedbatch');
              $currentBinName         = isset($_POST['currentPalletBinName']) ? $_POST['currentPalletBinName'] : '';
              if ($currentPalletIsBatched){
                $statusMessage .= "<br>$searchString is batched";

              }
              else{

                $statusMessage .= "  Found $countPallets pallets with that name that are not batched together.";
                Log::error("Pallet $searchString IS NOT Batched but has multiple instances in DB");
              }
            }

            //Client scan mode - pallets are being created
            if ($clientScanMode && $currentFacilityID){

              //Adding a new pallet, make sure it doesn't exist.

              if (!empty($foundPalletObject)){
                $statusMessage = "Pallet already exists.  You must exit client mode to scan this pallet.";
                $foundPalletObject = null;
                $viewToInclude = 'dashboard.inc';
              }
              else{

                //DO NOT Create the Pallet in the DB yet, this will come with the Store request
                //  Just set the name

                $currentProviderID  = SessionManager::GetProvidersForEmployee($currentUserID)[0];

                $foundPalletObject       = null;//setting to null to allow drop down of auto-show store form
                $currentPalletID      = 0;
                $currentPalletName    = $searchString;
                $viewToInclude        = "action.inc";//drop down into an immediate action
                $displayMode          = "ACTION";
                $actionType           = "STORE";
                $currentPalletQty     = 0;
                $currentPalletBinID   = 0;//Not associated yet


                $currentPalletStorageRequestID = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_STORAGE_REQUEST_ID);
                $next_pallet_scanQty          = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_QTY);
                $currentPalletBinName       = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_BIN);
              //  $currentPalletItemName  = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_ITEM);

              }
            }

            if (!empty($foundPalletObject)){
                  $palletID_scanned=$foundPalletObject[0]["id"];


                  //It is a valid pallet
                  $emptyPallet = true;
                  $viewToInclude = 'info.inc';
                  $scannedPalletName    = $currentPalletName = $searchString;
                  $currentPalletID     = $palletID_scanned;
                  $currentPalletBinName = $currentPalletLotNumber = $currentPalletItemName = null;

                  $searchType = "PID";

                  $binAssigned        = DataProvider::GetBinForPallet($palletID_scanned);
                  $currentPalletBinID =  (empty($binAssigned)) ? 0 : $binAssigned[0]['binid'];
                  if ($currentPalletBinID>0){
                    $currentPalletBin = new Storagebin($currentPalletBinID);
                    $currentPalletBinName = $currentPalletBin->GetField('name');
                  }

                  $palletInventory = InventoryManager::GetPalletInventoryByName(trim($scannedPalletName), $currentUserID);

                  $currentPalletQty = 0;
                  if (null!=$palletInventory && count($palletInventory)>0){
                    $emptyPallet = false;

                    $currentPalletOwnerID     = $palletInventory[0]['itemownerid'];
                    $currentPalletBinName     = $palletInventory[0]['binname'];
                    $currentPalletClientName  = $palletInventory[0]['clientname'];
                    $currentPalletLotNumber   = $palletInventory[0]['lotnumber'];
                    $currentPalletTag         = $palletInventory[0]['tag'];
                    $currentPalletUoM         = $palletInventory[0]['uom'];
                    $currentPalletItemName    = $palletInventory[0]['itemname'];
                    $currentPalletItemID      = $palletInventory[0]['itemid'];
                    $currentPalletQty         = $palletInventory[0]['item_qty'];
                    $currentPalletStorageRequestID     = $palletInventory[0]['id'];

                    $currentStorage = new Storage($currentPalletStorageRequestID);
                    $currentStorageRequestIsClosed = $currentStorage->IsClosed;
                  }
                  else{
                    //No inventory, still need to initialize vars used on info form
                    $currentPalletStorageRequestID = $currentPalletOwnerID = $currentPalletItemID = 0;
                  }
                }
                else{

                  $pallets = InventoryManager::GetBinInventoryByName(trim($searchString));

                  if (null!=$pallets && count($pallets)>0){
                    $currentBinName = trim($searchString);
                    $searchType = "BIN";
                    $binInventory = array();
                    foreach ($pallets as $pallet) {
                      $palletid   = $pallet["palletid"];
                      $palletName = $pallet["palletname"];
                      if (!is_null($palletid)){
                        $inv = InventoryManager::GetPalletInventory([$palletid], $currentUserID, null, null, true);
                        $binInventory[$palletName] = $inv;
                      }
                    }
                  }
                }

          }
          break;
        case "action":

            $currentPalletItemName = $currentPalletBinName = $currentPalletLotNumber = '';
            $currentPalletStorageRequestID = $currentPalletShipmentRequestID = $currentStorageRequestQty = 0;
            // An action assumes a pallet has been selected, and should come with the post
            $viewToInclude  = "action.inc";
            $displayMode    = "ACTION";
            $searchString = $currentPalletName = $_POST['currentPalletName'];
          //  echo "SET [$currentPalletName]";
            $currentPalletBinName   = isset($_POST['currentPalletBinName']) ? $_POST['currentPalletBinName'] : null;


            $actionType             = $_POST['action'];//SHIP or STORE button was clicked on a pallet
            $currentPalletID        = $_POST['currentPalletID'] ?? 0;
            $currentPalletQty       = isset($_POST['currentPalletQty']) ? $_POST['currentPalletQty'] : 0;

            $currentPalletOwnerID   = isset($_POST['currentPalletOwnerID']) ? $_POST['currentPalletOwnerID'] : 0;
            $currentPalletItemName  = isset($_POST['currentPalletItemName']) ? $_POST['currentPalletItemName'] : null;
            $currentPalletItemID    = isset($_POST['currentPalletItemID']) ? $_POST['currentPalletItemID'] : 0;

            if ($actionType=='CANCEL'){
              echo "<script type='text/javascript'>
              window.location.assign('dashboard?accessToken=$accessToken&firstLogin=1');
              </script>";
            }
            else{
              if ($actionType=="COMPLETE REQUEST"){


                if (isset($_POST['currentPalletShipmentRequestID'])){
                    $currentPalletShipmentRequestID = isset($_POST['currentPalletShipmentRequestID']) ? $_POST['currentPalletShipmentRequestID'] : 0;

                    if ($currentPalletShipmentRequestID==0){
                    $statusMessage = "<span style='color:red;'>No ticket selected to close</span>";
                    $viewToInclude = "dashboard.inc";
                  }
                  else{
                    $currentShipment = new Shipment($currentPalletShipmentRequestID);
                    $shipmentRequestQty = is_numeric($currentShipment->GetField('qty')) ? $currentShipment->GetField('qty') : 0;
                    $shipmentConfirmedPullQty = $currentShipment->GetField('confirmed_pulled_qty');

                    $showConfirmCompleteButton = true;
                    $shipmentName = $currentShipment->GetDisplayText();
                    $confirmCompleteMessage = "Shipment #$shipmentName - ($shipmentConfirmedPullQty/$shipmentRequestQty)";
                  }
                }
                else if (isset($_POST['currentPalletStorageRequestID'])){
                  $currentPalletStorageRequestID = $_POST['currentPalletStorageRequestID'];
                  $showConfirmCompleteButton = true;
                  $currentStorageRequest          = new Storage($currentPalletStorageRequestID);
                  $storageRequestName             = $currentStorageRequest->GetDisplayText();

                  $currentInventory               = InventoryManager::GetStorageRequestInventory($currentPalletStorageRequestID);
                  $storageRequestCountInStorage   = intval($currentInventory[0]['current_qty']);
                  $currentStorageRequestQty       = intval($currentStorageRequest->GetField('qty'));
                  $qtyRemaining                   = $currentStorageRequestQty - $storageRequestCountInStorage;

                  $confirmCompleteMessage         = "Storage #$storageRequestName - ($qtyRemaining)";

                }


              }
              else if ($actionType=="CONFIRM COMPLETE"){

                  $success = false;

                  if (isset($_POST['currentPalletShipmentRequestID'])){
                    $currentPalletShipmentRequestID = intval($_POST['currentPalletShipmentRequestID']);
                    if ( $currentPalletShipmentRequestID > 0 ){
                      $shipment = new Shipment($currentPalletShipmentRequestID);
                      $shipment->closeShipment();
                      $success = true;
                    }
                  }
                  else if ( isset($_POST['currentPalletStorageRequestID'])){
                    $currentPalletStorageRequestID = $_POST['currentPalletStorageRequestID'];
                    $storage = new Storage($currentPalletStorageRequestID);
                    $storage->closeStorage( $currentUserID);
                    $success = true;
                  }

                  if ($success){
                    echo "<script type='text/javascript'>
                    window.location.assign('dashboard?accessToken=$accessToken&firstLogin=1');
                    </script>";
                  }
                  else{
                    $statusMessage = "Error closing request";
                    $viewToInclude = "dashboard.inc";
                  }

              }
              else if ($actionType=="STORE" || $actionType=="SHIP"){


                // User clicked SHIP OR STORE from a pallet, grab the form data first

                //  Scenario 1 - Pallet was already assigned a storage ticket
                $currentPalletStorageRequestID = isset($_POST['currentPalletStorageRequestID']) ?
                                    $_POST['currentPalletStorageRequestID'] : 0;

                // Scenario 2 - Stocker put a ticket on an empty pallet and Selected a ticket
                if (!$currentPalletStorageRequestID){
                  $currentPalletStorageRequestID = isset($_POST['storage-requestid-scanned']) ?
                                      $_POST['storage-requestid-scanned'] : 0;
                  $currentPalletLotNumber = isset($_POST['lot-number-scanned']) ?
                                      $_POST['lot-number-scanned'] : null;


                }
                else{
                  $currentPalletLotNumber = isset($_POST['currentPalletLotNumber']) ?
                                      $_POST['currentPalletLotNumber'] : '';
                }
                //There should be an assoicated ticket at this point
                if (!$currentPalletStorageRequestID){
                  $statusMessage = "No storage request associated with pallet";
                }
                else{


                  //Form was submitted, grab the data
                  if(isset($_POST['palletName-scanned'])){

                    $palletName_scanned   = $_POST['palletName-scanned'];
                    $binName_scanned      = $_POST['binName-scanned'];
                    $qty_scanned          = $_POST['qty-scanned'];
                    $currentBinName        = $_POST['currentPalletBinName'];
                  }

                  //Client scan mode - pallets are being created
                  if ($clientScanMode){

                    //  1.) INSERT the pallet and get the palletID

                      $currentProviderID  = SessionManager::GetProvidersForEmployee($currentUserID)[0];

                      $statusMessage = "New Client PalletID Created";

                      $newPallet = new StoragePallet();

                      $fieldData = array(
                        "name"=>$searchString,
                        "providerid"=>$currentProviderID,
                        "facilityid"=>$currentFacilityID,
                        "description"=>'Client Pallet',
                        "sizexinches"=>1,
                        "sizeyinches"=>2,
                        "sizezinches"=>3,
                        "full"=>0,
                        "empty"=>0,
                        "usable"=>1,
                        "tag"=>'',
                        "repeatedbatch"=>0);

                      $newPalletID = $newPallet->InsertRecord($fieldData);


                      $foundPalletObject       = null;//setting to null to allow drop down of auto-show store form
                      $currentPalletID      = $newPalletID;
                      $currentPalletName    = $searchString;
                      $viewToInclude        = "action.inc";//drop down into an immediate action
                      $displayMode          = "ACTION";
                      $actionType           = "STORE";
                      $currentPalletQty     = 0;
                      $currentPalletBinID   = 0;//Not associated yet
                      $currentPalletBinName = '';
                  }

                  $currentPallet          = new Storagepallet($currentPalletID);
                  $currentPalletName      = $currentPallet->GetField('name');
                  $currentPalletIsBatched = $currentPallet->GetField('repeatedbatch');
                  $storageRequestStarted  = 0;//flag indicating that something has been stored for this ticket

                  if ($actionType=='STORE'){

                    // Load original storage request ticket and Pallet INVENTORY

                    $currentStorageRequest          = new Storage($currentPalletStorageRequestID);
                    $currentInventory               = InventoryManager::GetStorageRequestInventory($currentPalletStorageRequestID);
                    $currentStorageRequestItemID    = $currentStorageRequest->GetField('itemid');
                    $currentStorageRequestIsClosed  = $currentStorageRequest->IsClosed;
                    $currentStorageRequestQty       = $currentStorageRequest->GetField('qty');

                    if (!$currentStorageRequestIsClosed){

                      $storageRequestCountInStorage = $currentInventory[0]['current_qty'];
                      $qtyRemaining = $currentStorageRequestQty - $storageRequestCountInStorage;
                      if ($qtyRemaining<$currentStorageRequestQty){
                        $allowCloseTicket =  true;
                      }
                      $storageRequestStarted = ($qtyRemaining==$currentStorageRequestQty) ? 0 : 1;
                    //  $statusMessage .= "<br>started = ($qtyRemaining==$currentStorageRequestQty) - [$storageRequestStarted]";

                      //Default status message
                      //$statusMessage .= "<br>$qtyRemaining items remain to complete store request.";

                      if ($currentPalletIsBatched){
                        $foundPalletObject = DataProvider::GetPalletByName($searchString, $currentUserID);

                        $countBatchedPallets = count($foundPalletObject);
                        $statusMessage .= "<br>$countBatchedPallets pallet";
                        if ($countBatchedPallets>1){
                          $statusMessage .= "s";
                        }
                        $statusMessage .= " currently in batch";
                      }

                    }

                    //FORM was submitted, use the data to complete the request
                    if ( isset($palletName_scanned)){

                      $concerns           = '';
                      $notes = null;//this will be created if there are location/qty changes


                      //FORM VERIFICATION
                      //Attempting storage of an item onto a pallet:

                      if ($palletName_scanned!=$currentPalletName){
                          $concerns .= "<Br>Pallet Scan mismatch";
                      }
                      else{

                          if (intval($qty_scanned)<1){
                            $concerns .= "<br>No quantity";
                          }

                          //BIN scan mismatches and empty are only allowed for special modes
                          if (  $enforceBinScanOnStore && empty($binName_scanned) ||
                                (
                                    ($binName_scanned!=$currentBinName) &&
                                    (!$clientScanMode && !$currentPalletIsBatched)
                                )
                              ){
                              $concerns .= "<br>Bin Error";
                            }
                            else{ // Special Flows - client creates pallets or batched pallets
                              if(!empty($binName_scanned)){
                                //Make sure bin exists

                                $currentPalletBinID = DataProvider::GetResourceIDByName('Storagebin', 'name', $binName_scanned);

                                if ($currentPalletBinID<1){
                                  $concerns .= "<Br>Bin [$binName_scanned] does not exist.";
                                  $currentPalletLotNumber = '';
                                }
                                else{
                                  //Bin exists.  Use this one

                                  // If a new pallet, create it first

                                  if ($currentPalletIsBatched){

                                    //If pallet is batched, create a new pallet with same id
                                    $currentProviderID  = SessionManager::GetProvidersForEmployee($currentUserID)[0];

                                    $fieldData = array(
                                      "name"=>$currentPalletName,
                                      "providerid"=>$currentProviderID,
                                      "facilityid"=>$currentFacilityID,
                                      "description"=>'Batched Pallet',
                                      "sizexinches"=>1,
                                      "sizeyinches"=>2,
                                      "sizezinches"=>3,
                                      "full"=>0,
                                      "empty"=>0,
                                      "usable"=>1,
                                      "tag"=>"qty$qty_scanned",
                                      "repeatedbatch"=>1);

                                      //if current (first) pallet is confirmed, add a new pallet
                                      if ($storageRequestStarted==1){

                                        $newPallet = new StoragePallet();
                                        $newPalletID = $newPallet->InsertRecord($fieldData);
                                        $currentPalletID = $newPalletID;
                                        $statusMessage .= "<br>New Batched Pallet Created";
                                      }
                                      else{
                                        //$statusMessage .= "<br>Order not started, updating current pallet ";
                                        $currentPallet->UpdateRecord($fieldData);
                                        $statusMessage .= "<Br>First Batched Pallet Created";
                                      }
                                  }

                                  $fieldData  = array("palletid"=>$currentPalletID);
                                  $newBin = new Storagebin($currentPalletBinID);
                                  $currentPalletBinName = $newBin->GetField('name');

                                  Log::debug("Associating client palletid [$currentPalletName] with bin id: [$currentPalletBinID] name: [$currentPalletBinName].  SCANNED WAS [$binName_scanned]");

                                  $newBin->associate('binitems', 'storagepallet',$fieldData);
                                  $providerid = SessionManager::GetProvidersForEmployee($currentUserID)[0];
                                  Transaction::PalletAssignBin($currentUserID,
                                                                $currentPalletID,
                                                                $currentPalletBinID,
                                                                $currentPalletName,
                                                                $currentPalletBinName,
                                                                $providerid);
                                }
                              }
                            }

                          if (  !$concerns && (!$currentPalletIsBatched &&
                                            $currentStorageRequestQty>0 &&
                                            ($qty_scanned >= $currentStorageRequestQty))
                              ){
                              $statusMessage .= "<br>Additional Items Scanned [$qty_scanned] > Requested [$currentStorageRequestQty]";
                          }
                      }
                      if ($concerns){
                        $statusMessage .= "<div><span style='color:Red'>Storage Failure</span>$concerns</div>";
                        Log::warning("FATAL CONCERNS WITH STORAGE $concerns");
                        Log::warning("Original request:  bin $currentBinName pallet $currentPalletName toal storage request: qty $currentStorageRequestQty");
                        Log::warning("Scanned request:  bin $binName_scanned pallet $palletName_scanned qty $qty_scanned");
                      }
                      else {
                         //NO CONCERNS, UPDATE and CONFIRM QTY FOR THIS PALLET and Open up Ticket Closure button

                          $currentPalletBinID = DataProvider::GetResourceIDByName('Storagebin', 'name', $currentBinName);

                          //1.) Store the item
                          //2.) Compare active storage qty vs ticket
                          //3.) - If items remaining, show how many
                          //    - If completed, show completed
                          //    - OFFER manual close ticket at any-time

                          // NOTE:  Tickets will remain open until QTY is matched
                          //  OR a manual close is offered with a required note

                          $notes = "$qty_scanned $currentPalletItemName(s) stored on $currentPalletName.";

                          $success = $currentStorageRequest->store($currentUserID, $currentPalletID, $qty_scanned);

                          if($success){

                            if ($clientScanMode){

                              $storageItem = InventoryManager::GetStorageItemForRequest($currentPalletStorageRequestID);


                              if (!is_null($storageItem)){
                                $currentPalletItemName = $storageItem->GetDisplayText();
                              }
                              else{
                                $currentPalletItemName = '';
                              }

                              SessionManager::SetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_STORAGE_REQUEST_ID, $currentStorageRequest->ID);
                              SessionManager::SetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_QTY, $qty_scanned);
                              SessionManager::SetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_BIN, $currentPalletBinName);
                              SessionManager::SetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_ITEM, $currentPalletItemName);


                              $viewToInclude = 'dashboard.inc';
                            }
                            $currentPalletItemID = $currentStorageRequestItemID;//set this for client assigned pallets flow
                            $allowCloseTicket =  true;
                            $storageRequestCountInStorage += $qty_scanned;
                            $currentPalletQty             += $qty_scanned;

                            $statusMessage      .= "<br>" . $qty_scanned . " item";
                            $statusMessage      .= ($qty_scanned>1) ? "s" : "";
                            $statusMessage      .= " STORED";

                            $qtyRemaining = $currentStorageRequestQty - $storageRequestCountInStorage;

                            if ($qtyRemaining>0){
                              $statusMessage .= "<br>$qtyRemaining items awaiting storage";

                              $next_pallet_scanQty = ($qty_scanned<$qtyRemaining) ? $qty_scanned : $qtyRemaining;
                            }
                            else{
                              $statusMessage .= "<br>[ORDER COMPLETED]";
                            }

                            $providerid = SessionManager::GetProvidersForEmployee($currentUserID)[0];
                            Transaction::StorageFulfilled($currentUserID, $providerid, $currentPalletOwnerID, $currentPalletItemID, $currentPalletID, $currentPalletBinID, $notes);

                            $statusMessage = "<div><span style='color:Blue'>$statusMessage</span></div>";
                          }
                          else{
                            Log::error("Store failure for storage $currentPalletItemName");
                            $statusMessage = "<div><span style='color:Red'>Store Failure for $currentPalletItemName</span></div>";
                          }
                      }
                    }

                    $storageRequests = DataProvider::GetStorageRequestsByStocker();
                  }
                  else if ($actionType=='SHIP'){
                    //Always allow to close the ticket for a shipment for now
                    $allowCloseTicket = true;

                    //FORM was submitted, use the data to complete the request
                    if ( isset($palletName_scanned)){

                      $currentPalletShipmentRequestID = isset($_POST['currentPalletShipmentRequestID']) ? $_POST['currentPalletShipmentRequestID'] : 0;
                      $concerns           = '';
                        $shipmentRequestQty = 0;
                      if ($currentPalletShipmentRequestID>0){
                        $shipment = new Shipment($currentPalletShipmentRequestID);
                        $shipmentRequestQty = is_numeric($shipment->GetField('qty')) ? $shipment->GetField('qty') : 0;
                        $shipmentConfirmedPullQty = $shipment->GetField('confirmed_pulled_qty');
                      }
                      else{
                        $concerns .= "<br>No shipping ticket selected";
                      }
                      $notes = null;//this will be created if there are location/qty changes

                      //Attempting pull (shipment) of an item from a pallet:

                      if ($palletName_scanned!=$currentPalletName){
                          $concerns .= "<Br>Pallet Scan mismatch";
                      }
                      if ($binName_scanned!=$currentBinName){
                          $concerns .= "<br>Bin Scan mismatch";
                      }
                      if ($qty_scanned >= $shipmentRequestQty){
                          $statusMessage .= "<br>Additional Items Scanned [$qty_scanned] > Requested [$shipmentRequestQty]";
                      }
                      if ($qty_scanned>$currentPalletQty){
                        $concerns .= "<Br>Too many items requested";
                      }
                      if (intval($qty_scanned)<1){
                        $concerns .= "<br>No quantity";
                      }



                      if ($concerns){
                        $statusMessage = "<div><span style='color:Red'>Ship Pull Failure</span>$concerns</div>";
                        Log::warning("FATAL CONCERNS WITH SHIPMENT $concerns");
                        Log::warning("Original request:  bin $currentBinName pallet $currentPalletName toal shipment request: qty $shipmentRequestQty");
                        Log::warning("Scanned request:  bin $binName_scanned pallet $palletName_scanned qty $qty_scanned");
                      }
                      else {
                         //NO CONCERNS, UPDATE QTY FOR THIS PALLET and Open up Ticket Closure button

                          $currentPalletBinID = DataProvider::GetResourceIDByName('Storagebin', 'name', $currentBinName);

                          //1.) Pull an item
                          //2.) Compare active storage qty vs ticket request
                          //3.) - If items remaining, show how many
                          //    - If completed, show completed
                          //    - OFFER manual close ticket at any-time

                          // NOTE:  Tickets will remain open until QTY is matched
                          //  OR a manual close is offered with a required note

                          $notes = "$qty_scanned $currentPalletItemName(s) pulled from $currentPalletName.";

                          $success = $shipment->pull($currentUserID, $currentPalletID, $qty_scanned, $notes);


                          if($success){
                            // TODO: track this in backend, if user switches screens or pallets, this data is lost

                            $shipmentConfirmedPullQty += $qty_scanned;
                            $qtyRemaining = $shipmentRequestQty - $shipmentConfirmedPullQty;

                            $notes      = "$qty_scanned $currentPalletItemName(s) items pulled from $currentPalletName";
                            Log::debug("===== PULLING SUCCESS $notes");

                            $currentPalletQty   -= $qty_scanned;
                            $statusMessage       = $qty_scanned . " item";
                            $statusMessage      .= ($qty_scanned>1) ? "s" : "";
                            $statusMessage      .= " PULLED";


                            if ($qtyRemaining>0){
                              $statusMessage .= "<br>$qtyRemaining more items to complete request.";
                            }
                            else{
                              $statusMessage .= "<br>[ORDER COMPLETED]";
                            }

                            $providerid = SessionManager::GetProvidersForEmployee($currentUserID)[0];
                            Transaction::ShipmentFulfilled($currentUserID, $providerid, $currentPalletOwnerID, $currentPalletItemID, $notes);

                            $statusMessage = "<div><span style='color:Blue'>$statusMessage</span></div>";
                          }
                          else{
                            Log::error("Pull failure for shipment [$currentPalletShipmentRequestID] of $currentPalletItemName from $currentPalletName");
                            $statusMessage = "<div><span style='color:Red'>Pull Failure for [$qty_scanned] $currentPalletItemName</span></div>";
                          }
                      }//end no concerns, pulled item
                    }//end pallet was scanned
                    //Always grab new shipping requests and update receiver list
                    $shippingRequests = DataProvider::GetShippingRequestsByStocker();
                    $receivers = DataProvider::GetReceivers($currentPalletOwnerID);
                  }//end ship
                }//end storageRequestID existed with this pallet
              }//end ship or store
              else if ($actionType=='MOVE'){
                $error = false;

                $viewToInclude = "movepallet.inc";

                $currentPalletID        = $_POST['currentPalletID'] ?? 0;

                if ($currentPalletID>0){

                if (isset($_POST['currentPalletBinName'])){
                   if (!empty($_POST['currentPalletBinName'])){
                     $currentPalletBinName = $_POST['currentPalletBinName'];
                     $currentPalletBinID = DataProvider::GetBinIDByName($currentPalletBinName);
                   }
                   else{
                     $currentPalletBinname = '';
                     $currentPalletBinID = 0;
                   }
                }
                else if (isset($_POST['currentPalletBinID'])){
                  $currentPalletBinID = $_POST['currentPalletBinID'];
                }
                //Form was submitted with a scanned bin
                if (isset($_POST['binName-scanned'])){

                  $binName_scanned = $_POST['binName-scanned'];
                  $newBinID = DataProvider::GetBinIDByName($binName_scanned);


                  if ($newBinID>0){

                      $currentPalletName = $_POST['currentPalletName'];
                      //reassociate
                      $error = false;
                      $fieldData  = array("palletid"=>$currentPalletID);
                      try {
                        if ($currentPalletBinID>0){
                          $currentBin = new Storagebin($currentPalletBinID);
                          if (!$currentBin->disassociate('binitems', 'storagepallet',$fieldData)){
                              $error = true;
                              $statusMessage = "Error disassociating previous pallet from bin";
                          }
                        }

                      } catch (\Exception $e) {
                        $statusMessage = "Error during bin disassociate" . $e->getMessage();
                        Log::error($statusMessage);
                        $error = true;
                      }
                      if (!$error){
                        //ASSOCIATE
                        $newBin = new Storagebin($newBinID);
                        //echo "Assocating bin: " . $newBin->ID;
                        if ($newBin->associate('binitems', 'storagepallet',$fieldData)){
                          $providerid = SessionManager::GetProvidersForEmployee($currentUserID)[0];
                          Transaction::PalletChangeBin($currentUserID, $currentPalletID,
                                                        $currentPalletBinID,
                                                        $currentPalletBinName, $binName_scanned, $providerid);
                          $statusMessage = "<div><span style='color:Blue'>SUCCESS</span><br>Pallet $currentPalletName moved to $binName_scanned</div>";
                          $viewToInclude = 'dashboard.inc';
                        }
                        else{
                          $error = true;
                          $statusMessage = "Error associating bin to pallet";
                        }
                      }
                  }
                  else{
                    $error = true;
                    $statusMessage = "Bin '$binName_scanned' not found.";
                  }
                }
              }
              else{
                $error = true;
                $statusMessage = "PalletID is not provided";
              }

              if ($error){
                $statusMessage = "<div><span style='color:Red'>MOVE FAILURE</span><Br>$statusMessage</div>";
              }
            }

            }//end action != CANCEL

              break; //end of ACTION HANDLER

        case "logout":
          $currentUserID = SessionManager::GetCurrentUserID();
          DataProvider::DELETE_AUTHORIZATIONS($currentUserID);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::CLIENT_PALLET_SCAN_MODE);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::CURRENT_FACILITYID);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::CURRENT_FACILITYNAME);

          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_STORAGE_REQUEST_ID);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_QTY);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_BIN);
          SessionManager::DeleteParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_ITEM);


          Util::DestroySiteSession();
          $viewToInclude = "login.inc";
          $statusMessage = "You are logged out";
          $loggedin = false;
          Transaction::Logout($currentUserID);
          break;

        case "dashboard":
          if (isset($_GET['firstLogin'])){
              //  $fullName = SessionManager::GetCurrentUserFullName();
                $nickName = SessionManager::GetCurrentUserNickName();
                $statusMessage = "Welcome back $nickName";
          }
          //$statusMessage = "<br><h4>Scan a Pallet or Bin</h4>";
          $viewToInclude = "dashboard.inc";
          break;
        default:
          if (!$loggedin){
            $statusMessage = "Please login";
          }
          break;
      }
    }

//Ready to show the view now, scan the open tickets and add requestID to current pallet
//
// SYNC current pallet data with active storage request
// TODO: OMG please refactor this, data is already queried above

if (isset($currentPalletID)){
$countShipTickets = $countStoreTickets = 0;
  //assume tickets are closed for this pallet
  $currentStorageRequestIsClosed = true;

    if ($totalcountShipTickets>0){

      $lotNumbersShip = array();
      $countShipTickets = 0;
      $currentPalletShipmentRequests = array();

      //Iterate the shipping requests and attached, recommended pallet pulls
      //  If the current pallet is found, flag that pallet's current inventory as current

      foreach ($shippingRequests as $item) {
        $targetPalletNames = explode(',', $item['targetpalletNames']);
        foreach ($targetPalletNames as $palName) {
            $name = explode(';', $palName);

              //This is current pallet
            if ($name[0]===$currentPalletName){

              $shipRequestLotNumber = $item['lotnumber'];
              //$receiverName = $item['receiver'];
              $receiverName = $item['receiver'];
              $receiverID   = $item['receiverID'];
              $currentPalletOwnerID           = $item['clientID'];
              $currentOwner                   = new User($currentPalletOwnerID);
              $currentOwnerDisplayCode        = $currentOwner->GetField('displaycode');
              $currentPalletItemName          = $item['productName'];
              $currentPalletItemID            = $item['itemID'];
              $confirmed_pulled_qty           = $item['confirmed_pulled_qty'];
              $requestedQty                   = $item['qty'];

              //build a unique ticket title for this shipping request FOR THIS PALLET

              $shippingRequestID = $item['shipmentid'];
              if (empty($shipRequestLotNumber) || ($currentPalletLotNumber===$shipRequestLotNumber)){
                            $countShipTickets += 1;
                            $ticketText = substr($currentPalletItemName,0,5) . "-" . $currentOwnerDisplayCode;
                if(!empty($shipRequestLotNumber)){
                  $ticketText .= "-$shipRequestLotNumber";
                }
                $ticketText .= "-[$confirmed_pulled_qty / $requestedQty]";

                $currentPalletShipmentRequests[$shippingRequestID] = $ticketText;

                if (!in_array($shipRequestLotNumber, $lotNumbersShip)){
                    $lotNumbersShip[] = $shipRequestLotNumber;
                }
                //echo "here";
              }
              else{
                Log::error("Shipment was requested $shippingRequestID for lot [$shipRequestLotNumber] that did not exist on pallet $currentPalletName which has lot [$currentPalletLotNumber]");
              }
            }
        }
      }
    }

    if ($clientScanMode){

      //$next_pallet_scanQty

    }
    else if ($totalcountStoreTickets>0){

      $currentPalletStorageRequestID = $currentPalletStorageRequestID>0 ? $currentPalletStorageRequestID : 0;
      $countStoreTickets = 0;

      //Look through current storage request and grab data for current pallet
      //
      foreach ($storageRequests as $item) {
        if ($currentPalletName == $item['palletname']){

          //Storage tickets should never be more than one
          $countStoreTickets = $currentPalletIsBatched  ? 1 : $countStoreTickets + 1;

          $lotNumber                      = $item['lotnumber'];
          $currentPalletStorageRequestID  = $item['storageid'];
          $currentPalletItemName          = $item['name'];
          $currentPalletItemID            = $item['id'];
          $currentPalletBinName           = $item['binname'];
          $currentPalletLotNumber         = $item['lotnumber'];
          $currentStorageRequestQty       = $item['qty'];
          $currentPalletOwnerID           = $item['ownerid'];

          $currentStorageRequest  = new Storage($currentPalletStorageRequestID);
          $currentStorageRequestIsClosed = $currentStorageRequest->IsClosed;

          //todo ONLY SUPPORT ONE Storage Item and Lot per pallet, DRIVEN BY CONFIG but hard-coded here
        }
        else if ($currentPalletStorageRequestID == $item['storageid'] && ($item['confirmed']) ){

          //Different pallet, same storage request.  Get their qty to pre-populate qty
          $next_pallet_scanQty = ($item['item_qty']<$qtyRemaining) ? $item['item_qty'] : $qtyRemaining;
          //echo "set next_pallet_scanQty to : [$qtyRemaining]";
        //  echo "llllll [ " . $item['item_qty'] . " ]";
        }

      }
    }

    $openRequestsText = "Ship:$countShipTickets Store:$countStoreTickets";
}

    ?>
    <table width="202px" style="display: inline; min-width: 202px; max-width: 204px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC';">
      <tr>
        <td>
          <div align="left">
            <?php

            if (file_exists($viewToInclude)){
                require($viewToInclude);
                if (!empty($openRequestsText)){
                  //Always show the open tickets for ths pallet if scanning or info
                  echo "<table width='204px'>
                    <tr>
                      <td style='background:Navy; color:Yellow'>Tickets</td>
                      <td style='background:antiquewhite;'>$openRequestsText</td>
                    </tr>
                  </table>";
                }
              }
            ?>
          </div>
        </td>

      </tr>
    </table>
  <?php
  require('footer.inc');
  ?>
  </body>
</html>
