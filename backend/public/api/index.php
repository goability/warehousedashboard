<?php
/*
API Service:
  CRUD for all standard resources deriving from ResourceBaseType
  Associations and Disassiations
  Login
  Password Reset Request and Password Reset
  Autocomplete
  Signup
*/
namespace Ability\Warehouse;

try
{
  set_include_path(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."includes".DIRECTORY_SEPARATOR);
  require_once("autoload-api.php");
}
catch (Exception $e){
  $errorMsg = "Error occured during class autoloading for API...";
  error_log($errorMsg);
  $reply = Util::build_error_reply(401, $errorMsg);
  header($reply);
  exit();
}

try {
    Log::init('API');
} catch (\Exception $e) {
  $errorMsg = "Error with logger setup for API";
  error_log($msg . $e->getMessage());
  $reply = Util::build_error_reply(401, $errorMsg);
  header($reply);
  exit();
}

Log::debug('========== INCOMING API REQUEST =========');

$resource = "";
$reourceID = 0;
$errCode = 404;//default to unknown

//TODO PUT SECURE BACK IN FOR THE REST API !!
/*
if (!Util::verify_request()){
  $errorCode = 403;
  $message = "PWH Error";
  $reply = Util::build_error_reply($errorCode, $message);
  header($reply);
}*/
Log::debug("Loading configuration for API");

//Load all of the configurations
ConfigurationManager::LoadAllResourceConfigs();
//Load all prepare statements for loaded resources
DataProvider::LoadPrepareStatements();


$requestType      = $_SERVER['REQUEST_METHOD'];
$deployedInSubdir = ConfigurationManager::GetParameter('APIDeployInSubdir');
$route_params     = Util::get_parameters($deployedInSubdir);

$resource       = $route_params["resource"];
$resourceID     = empty($route_params["resourceID"]) ? 0 : $route_params["resourceID"];
$resourceName   = trim($resource);// substr(ucwords($resource),0,-1);//stripo off the plural
$resourceAction = isset($route_params["resourceAction"]) ? $route_params["resourceAction"] : null; //  /user/1/associate/{associationTableName}/{foreignRecordID}

//Look for tokens in GET and Post, validate, and set them
SecurityManager::SetTokens();
$accessToken    = SecurityManager::$AccessToken;
$authCode       = SecurityManager::$AuthCode;
$statusMessage  = "";
$currentUserID  = !empty($accessToken) ? SecurityManager::GetUserID($accessToken) : 0;

//Handle non-resource requests:  Authenticate, Signup
Log::debug("ROUTER Resource REQUEST is $resourceName");

if (!Util::ResourceClassExists($resourceName)){

  $error = true;
  $errMsg = "";

  switch ($resourceName) {

    // ?searchString=%%
    case 'Autocomplete': // return records and displaytext


      $fieldData    = $_GET;//$route_params['queryParameters'];

      if (empty($fieldData)){
        echo 0;
        return;
      }
      $resourceName = $fieldData['resourceName'];
      $searchString = $_GET['term'];
      $resourceConfig = ConfigurationManager::getResourceConfig($resourceName);
      $tableName = $resourceConfig['tableName'];
      $searchFieldName = $resourceConfig['displayFieldName'];
      $indexFieldName = $resourceConfig['indexFieldName'];

      $rows = DataProvider::FindRecordsLike($tableName, $indexFieldName, $searchFieldName, $searchString);

      $searchResults = new \stdClass();
      $searchResults->totalRows = count($rows);
      $searchResults->page = 1;
      $searchResults->results = $rows;

      echo json_encode($rows, JSON_PRETTY_PRINT);

      $error = false;

      break;
    case 'Authenticate'://Auth only returns a userId

      //Get the username and password from the post data

      //Send into dataprovider to see if there is a record matching uname and hash
      $userID = DataProvider::AUTHENTICATE($_POST['username'], $_POST['password']) > 0 ? true : false;

      //Return UserID on success, 401 on failure
      if ($userID > 0) {
        $error = false;
        echo $userID;
      }
      else{
        // NOTE: never tell why an auth fails
        $errMsg = "Authentication failure";
      }

      break;
    case 'Login': //will authenticate AND create a new session

      $userLoginData = DataProvider::LOGIN($_POST['username'], $_POST['password']);

      if (null!=$userLoginData){
        Log::info("API::Login - Sending back userid and temp auth code");
        $error = false;
        echo $userLoginData[0] . ',' . $userLoginData[1];
      }
      else {
        $errCode = 401;
        $errMsg = "Login failure";
        Log::info("login failure");
      }
      break;

    case 'Signup':

      $emailaddress = $_POST['EmailAddress'];
      $username     = $_POST['Username'];
      $password     = $_POST['Password'];
      $firstname    = $_POST['Firstname'];
      $lastname     = $_POST['Lastname'];
      $city         = $_POST['City'];
      $state        = $_POST['State'];
      $zip          = $_POST['Zipcode'];

      $results      = DataProvider::ADD_USER( $emailaddress, $username,
                                              $password, $firstname,
                                              $lastname, $city, $state, $zip);


      $userID       = $results[0];
      $success      = $userID > 0;

      $errMsg       = $results[1];

      if (!$success){
        Log::error("Add user failed for $emailaddress");
      }
      else{
        $error = false;
        echo $userID;

        // TODO: Now add any requested associations (from the signup page)
        /*
                $primaryType      = "provider";
                $foreignType      = "client";
                $primaryRecordID  = 0;
                $foreignRecordID  = 0;

                DataProvider::AddAssociationRequest(  $userID,
                                                      $primaryType,
                                                      $primaryRecordID,
                                                      $foreignType,
                                                      $foreignRecordID);
          */
          Transaction::UserSignup($userID, $firstname . " " . $lastname);

      }
      break;
    case 'Reset': //Reset a Password
        //RESET has two steps, driven by $_POST['resetStep']
        //  1.) request -  request a link to be sent, this data comes in from form and includes an already
        //        generated and ticking authCode
        //  2.) reset  - Actually change the password

        $resetStep = isset($_POST["resetStep"]) ? $_POST["resetStep"] : null;
        Log::info("RESETTING A PASSWORD");
        /// ResetStep is Mandatory.  If not present, transaction fails
        if (is_null($resetStep)){
          $errMsg = "RESET STEP EMPTY";
          Log::error("SECURITY - Password RESET Bad request - resetStep '$resetStep'! ");
          return null;
        }
        else{

          switch($resetStep)
          {

            case 'request':
              //validate authcode AND emailaddress together

              //ERROR is ALWAYS FALSE FOR A REQUEST:
              //   This is on purpose to avoid letting a bot know if emailaddress actually existed
              $error = false;

              $emailaddress = isset($_POST["emailaddress"]) ? $_POST["emailaddress"] : null;
              if  (   !SecurityManager::ValidateAuthCode($authCode) ||
                      is_null($emailaddress)
                  ){
                  Log::error("SECURITY error with authCode or emailaddress input - '$emailAddress'");
              }
              else {
                $apiURL   = ConfigurationManager::GetParameter("APIURL");
                $siteURL  = ConfigurationManager::GetParameter("SiteURL");

                $userID = DataProvider::VERIFY_USER_EXISTS($emailaddress, null, $authCode);
                if ($userID > 0){
                  //Found a userID, grant them a very short lived AccessToken
                  //  Send email with clickable link - ONLY with the AccessToken
                  // The router will verify the request, generate a new Accesstoken and build the reset form

                  $timeoutSecs = ConfigurationManager::GetParameter("Sessioning")->PasswordRecoveryTimeoutSecs;

                  $accessTokenData = DataProvider::SET_ACCESS_TOKEN($userID, $authCode, $timeoutSecs);

                  $accessToken = $accessTokenData["accessToken"];
                  $expiresUnixTime = $accessTokenData["expires_unix_time"];

                  $resetLink = $siteURL . "/Reset?accessToken=$accessToken";
                  Log::debug("API::Reset - Sending generated password reset to user $emailaddress - $resetLink");
                  $expiryFormatted = Util::GetFormattedDate($expiresUnixTime);
                  Log::debug("The password reset access token will expire at $expiryFormatted");
                  $message = EmailService::GeneratePasswordResetMessage($resetLink);

                  EmailService::SendMail($emailaddress, "PASSWORD RESET", $message);

                  Log::debug($message);
                  echo "$userID,1";

                  }
                  else{
                    Log::error("PASSWORD RESET REQUEST - Failed because $emailaddress did not exist in the system.");
                    echo "$emailaddress,0";
                  }
                }
                break;
            case 'reset':


              //Reset the password
              $passwordRaw  = isset($_POST['password_raw']) ? $_POST['password_raw'] : null;
              $userID       = isset($_POST['userid']) ? $_POST['userid'] : null;
              $accessToken  = isset($_POST['accessToken']) ? $_POST['accessToken'] : null;

              $user         = new User($userID);//create an object to get their name
              $name         = $user->GetDisplayText();
              //Log::debug("REset password [$passwordRaw] [$userID] [$accessToken]");
              if  (   is_null($passwordRaw) || is_null($userID) ||
                      !SecurityManager::ValidateAccessToken($accessToken, $userID)

                  ){
                  Log::error("SECURITY error with userid [$userID] or password [$passwordRaw] accessToken [$accessToken]");
                  echo "$userID,0";
                  return;
              }
              else {
                  $error = DataProvider::RESET_PASSWORD($userID, $accessToken, $passwordRaw);

                  if(!$error){

                    echo "$userID,1";
                    Transaction::UserChangePassword($userID, $name);
                    Log::debug("PASSWORD RESET success, returning ($userID,1)");

                  }
                  else{
                    echo "$userID,0";
                    $errMsg = "PASSWORD RESET failure, returning ($userID,0)";
                    Log::error($errMsg);
                    return;

                  }
              }
              Log::info("REMOVING ALL authorizations for [$name] userID=[$userID]");
              //ALWAYS REMOVE all authtokens for this userID, force a new flow
              DataProvider::DELETE_AUTHORIZATIONS($userID);

              break;
            default:
              Log::error("LOGIC ERROR with Unknown Reset Password step $resetStep");

            }
          }

      break;

      case 'Search':

        // take the text string and look at name in storage items
        $foundItems = DataProvider::SearchForItem($_POST['searchString']);
        if (!empty($foundItems))
        {
          $error = false;
          $jsonItems = json_encode($foundItems);
          echo $jsonItems;

        }
        else{
          echo "{}";
        }
        break;

      default:

        $errMsg = "Resource not found " . $resourceName;
        break;
  }

  if ($error){
      $reply = Util::build_error_reply($errCode, $errMsg);
      header($reply);
      echo "0, $errMsg";
  }
}
else{
//CLASS EXISTS THIS IS A RESOURCE REQUEST

  Log::info("RESOURCE REQUEST -
          Incoming $requestType request for resource $resourceName . ID=$resourceID
          ResourceAction=$resourceAction");
  //Create the database connection
  try
  {
    //ResourceID is always required unless POSTING (creating)
    if ($resourceID==0 && $requestType!="POST"){
      $errorCode = 400;
      $message = "Malformed Request";
      $reply = Util::build_error_reply($errorCode, $message);
      header($reply);
      echo $message;
      exit();
    }

    Log::debug("======= LOADING RESOURCE $resourceName");

    $fqClassName = NAME_SPACE . "\\" . $resourceName;
    $currentRecord = new $fqClassName($resourceID);

    if ($currentRecord == null || ($requestType!="POST" && $currentRecord->ID==0))
    {
      //This should never happen
      $errorMsg = "Resource not found " . $resourceName;
      $reply = Util::build_error_reply(401, $errorMsg);
      header($reply);
      exit();
    }
    else
    {

      $post_vars = !empty($_POST) ? $_POST : null;
      $errMsg = null;

      if (!empty($resourceAction)){
        //https://lornajane.net/posts/2008/accessing-incoming-put-data-from-php
        //parse_str(file_get_contents("php://input"),$post_vars);
        switch ($resourceAction)
        {
          case "associate":
          case "disassociate":

            $fieldData                  = $route_params['queryParameters'];
            if (isset($_POST)){
              $qty        = isset($post_vars['qty']) ? $post_vars['qty'] : 0;
              $tag        = isset($post_vars['tag']) ? $post_vars['tag'] : '';
              $lotnumber  = isset($post_vars['lotnumber']) ? $post_vars['lotnumber'] : '';
              $itemid     = isset($post_vars['itemid']) ? $post_vars['itemid'] : null;
              Log::debug("POST EXISTS $qty $tag $lotnumber $itemid");
            }
            $associativeCollectionName  = $route_params['resourceActionItemData'][0];
            $foreignResourceName        = $route_params['resourceActionItemData'][1];
            break;
          case "approve":
            $approverID = $route_params['resourceActionItemData'][0];
            Log::info("Approving a request approverID=$approverID");
            if ($resourceName=='shipment'){
              $palletIDs  = $route_params['queryParameters']['palletIDs'];
              Log::info("TAGGING PALLETS FOR SHIP - palletIDs = $palletIDs with shipment request $resourceID");
            }
            break;

          case "claim":
            $employeeID = $route_params['resourceActionItemData'][0];
            Log::info("WORKER IS CLAIMING A REQUEST - userid=$employeeID ");
            break;
          case "ship":
          case "store":
            $fieldData  = $route_params['queryParameters'];
            $employeeID = $route_params['resourceActionItemData'][0];
            Log::info("$resourceAction ing a request userid=$employeeID ");
            break;
          default:
            break;
        }
      }
      switch ($requestType) {
        case "POST":

          switch ($resourceAction)
          {
            case "associate":
              //some associations might carry post data, just add that into
              //  the ordered array
              if (!empty($post_vars)){
                $fieldData = $fieldData + $post_vars;
              }
              Log::debug("Calling $resourceName associate method with data:");
              foreach ($fieldData as $key => $value) {
                Log::debug("FieldData[$key]=$value");
              }

              $associatedPalletID =$currentRecord->associate($associativeCollectionName, $foreignResourceName, $fieldData);

              Log::debug("API - New pallet associated [$associatedPalletID]");
              if ($associatedPalletID>0){
                Log::debug("Association Complete.  Passing data back");
                //echo $newPalletID;
                echo reset($fieldData);//// TODO: PASSING THIS RIGHT BACK BAD DESIGN ?
              }
              else{
                Log::debug("Zero was associated pallet");
                $errMsg = "Pallet not created";
                $errCode = 409;//Service error
              }
              break;
            case "cancelapproval":

              $isApproved = $currentRecord->IsApproved();
              Log::debug("Cancelling a $resourceName request approverID=$currentUserID");

              //If it is not even approved yet, delete it
              if(!$isApproved){
                Log::debug("Deleting $resourceName because it is not approved and was requested cancelled by [$currentUserID]" );
                $currentRecord->DeleteRecord($currentRecord->ID);
              }
              else{
                Log::debug("APPROVED DATE IS = " . $currentRecord->GetField("date_approved"));
                if ($resourceName==='storage'){

                  $cancelData = array("date_approved"=> "1970-01-01",
                                      "userid_approver"=>0,
                                      "date_stored"=> "1970-01-01",
                                      "userid_stocker"=>0);
                  $currentRecord->UpdateRecord($cancelData);

                  //Now disassociate any pallets that were assigned
                  DataProvider::RemoveStorageItemFromPallets($currentRecord->ID);
                }
                else if ($resourceName==='shipment'){
                  $cancelData = array("date_approved"=> "1970-01-01",
                                      "userid_approver"=>0,
                                      "date_shipped"=> "1970-01-01",
                                      "userid_puller"=>0);

                  $currentRecord->UpdateRecord($cancelData);
                  DataProvider::UnTagPalletsForShipment($currentRecord->ID);
                }
              }

              if ($resourceName==='storage'){
                Transaction::StorageRequestCancel($currentUserID, $currentRecord->ID, $isApproved);
              }
              else if ($resourceName==='shipment'){
                Transaction::ShipRequestCancel($userid, $currentRecord->ID, $isApproved);
              }
              break;
            default:
              Log::debug("Inserting a new $resourceName record");

              $createdResourceID = $currentRecord->InsertRecord($post_vars);
              echo "Newly created resourceID is $createdResourceID";
              break;
          }

          break;
        case "PUT":
          switch ($resourceAction)
          {
            case "approve"://{storage;shipment}/{recordid}/approve/{userid}
              Log::debug("Approving a $resourceName");
              $currentRecord->approve($approverID);//If record has approval flow

              $clientID = $currentRecord->GetField('userid_requestor');
              $providerID = DataProvider::GetProviderForClient($clientID);
              // TODO: A user approving this might be owner of more than one provider
              //    and also, this client might be member of both !
              //    When approving (and requesting), need to know for which provider
            //  $providerID = null;//DataProvider::GetProvidersForEmployee

              $itemid     = $currentRecord->GetField('itemid');
              $item       = new Storageitem($itemid);
              $qty        = $currentRecord->GetField('qty');
              $name       = $currentRecord->GetDisplayText();
              $notes      = "$qty $name(s)";
              $palletID   = null;
              if ($resourceName=='shipment'){

                $palletIDsArray = explode(',', $palletIDs);
                for ($i=0; $i < count($palletIDsArray); $i++) {
                  $palletID   = $palletIDsArray[$i];
                  $pallet     = new Storagepallet($palletID);
                  $palletname = $pallet->GetDisplayText();
                  $notes      .= " [$palletname]";
                }
                Log::debug("Approving shipment with approverid [$approverID]");
                DataProvider::AssignPalletForPull($resourceID, $palletIDs);
                Transaction::ShipmentApproval($approverID, $providerID, $clientID, $itemid, $notes);
              }
              else{
                Log::debug("Approving storageitem [$itemid] with approverid [$approverID] providerID is [$providerID] pallet is $palletID");
                Transaction::StorageApproval($approverID, $providerID, $clientID, $itemid, $palletID, $notes);
              }

              break;
            case "claim": //{storage;shipment}/{recordid}/claim/{userid}
              $currentRecord->claim($employeeID);
              break;
            case "store": //storage/{recordid}/store/{userid}?qty=val&palletID=val
              $qty = $fieldData["qty"];
              $palletID = $fieldData["palletID"];

              Log::info("================== STORE ITEM ON PALLET $palletID ");
              $data = $fieldData;
              Log::info("OTHER $data[0]");

              $currentRecord->store($employeeID, $palletID, $qty);
              Log::info("Storing $qty on pallet $palletID for storage request $currentRecord->ID");

              //// BUG: This is nto working,had to log form /inventory code
              // TODO: Again, a design issue, worker can only be with one unless this was tagged all the way through
              $providerid = DataProvider::GetProvidersForEmployee($employeeID)[0];
              $clientid   = $currentRecord->GetField('userid_requestor');
              $itemid     = $currentRecord->GetField('itemid');
              $pallet     = new Storagepallet($palletID);
              $palletName = $pallet->GetDisplayText();
              $item       = new Storageitem($itemid);
              $itemname   = $item->GetDisplayText();
              $notes      = "$qty $itemname items stored on $palletName";
              Log::info("notes $notes");
              //Transaction::StorageFulfilled($employeeID, $providerid, $clientid, $palletid, $binid, $itemid, $notes);
              break;
            case "ship": //shipment/{recordid}/ship/{userid}?qty=val&palletID=val
              $qty      = $fieldData["qty"];
              $palletID = $fieldData["palletID"];

              Log::info("================== SHIP ITEM FROM PALLET $palletID ");
              $data = $fieldData;
              Log::info("OTHER $data[0]");
              Log::info("Storing $qty on pallet $palletID for storage request $currentRecord->ID");

              $currentRecord->ship($employeeID, $palletID, $qty);
              break;
          }
        case "GET":
          echo $currentRecord->toJSON();
          break;

        break;

        case "DELETE":
          //grab the ID and pass it to delete
          ///api/storagefacility/3/disassociate/facilityowners/user?userid=4

          switch ($resourceAction)
          {
            case "disassociate":
              $currentRecord->disassociate($associativeCollectionName, $foreignResourceName, $fieldData);
              echo reset($fieldData);//// TODO: PASSING THIS RIGHT BACK BAD DESIGN ?
              break;
            default:
              break;
          }

          break;

        default:
          // code...
          break;
      }

      //Somethign was done with the resource, build reply
      if (!is_null($errMsg)){
        $reply = Util::build_error_reply($errCode, $errMsg);
        header($reply);
        exit();
      }

    }//end resource exists

  }
  catch (Exception $e)
  {
    Log::error($e->getMessage());
  }
}



function build_reply($msg){
  echo $msg;
}
/*
buid an error reply
*/
function build_error_reply($msg){

      Log::error($errorMsg);
      $reply = Util::build_error_reply(401, $errorMsg);

}


?>
