<?php
namespace Ability\Warehouse;

/*
  Determine the route and resource
  Call get_parameters()
  Setup Context object that can be referenced in form
  Include requested resource
*/
class Router
{

  function __construct()
  {
    // code...
  }


  public function handle_route()
  {
  //Extract Routing parameters from URL and place in array
  // Standard format:  /Resource/ID/ResourceAction/ResourceActionItem

  // TODO: currently get_parameters spits out common names, with no knowledge of route
  //   this causes problem below with each case pulling out strangly named params

  $route_params = Util::get_parameters(false);

  $resource   = $route_params["resource"];
  $resourceID = $route_params["resourceID"];
  $formMode   = $route_params["formProperties"]["MODE"];
  $formData   = (isset($_POST)) ? $_POST : null;
  $showForm   = false;
  $activeReport = "";// TODO: default this to a favorite/last, etc

  //this is the default view to show when no sessioning is valid
  $viewToInclude = "LoginView.php";

  $accessTokenRequired = true;//set to false on signup route

  //Grab any authCodes or AccessTokens from Get or Post, these are compared against any session tokens
  if (!SecurityManager::SetTokens()){
    Log::error("SECURE FAILURE while trying to get tokens from post or get");
    echo "APPLICATION SECURITY FAILURE WITH TOKENS";
    return;
  }
  $accessToken  = SecurityManager::$AccessToken;
  $authCode     = SecurityManager::$AuthCode;

  Log::debug("SITE ACCESS - AccessToken is $accessToken " . strlen($accessToken));
  Log::debug("SITE ACCESS - AuthCode is $authCode " . strlen($authCode));

  SessionManager::ResumeSession();

  // Load configurations from SessionManager, which will call ConfigurationManager as needed
  SessionManager::LoadConfigurations();
  //Load all of the configurations
  ConfigurationManager::LoadAllResourceConfigs();
  //Load all prepare statements for loaded resources
  DataProvider::LoadPrepareStatements();

  //$url = ConfigurationManager::GetParameter("EmailFromUser");
  $apiURL   = ConfigurationManager::GetParameter("APIURL");
  $siteURL  = ConfigurationManager::GetParameter("SiteURL");
  $statusMessage = "";

  Util::CreateCookie('APIURL', $apiURL);
  Util::CreateCookie('SiteURL', $siteURL);


  $showTopMenu = !in_array( $resource,
                          explode(",", ConfigurationManager::GetParameter('HideTopMenuResources'))
                        );


 if ($showTopMenu){
    include "MenuTop.php";
  }


  switch($resource)
  {

    case 'Forgot': //forgot password

      $d = getcwd() . DIRECTORY_SEPARATOR .
                      "forms" .
                      DIRECTORY_SEPARATOR .
                      "formForgotPassword.php";

      if (file_exists($d)){
        include ($d);
      }
      return;

    case 'Reset': //Show the Reset Password form

      //User is NOT logged in, but they have an accessToken.
      //  UserID is NOT passed on the URL, so request it from DB
      //  It will be set as hidden parameter on form
      $accessTokenData = DataProvider::GET_ACCESS_TOKEN_DATA($accessToken);

      if (empty($accessTokenData))
      {
        Log::error("During password reset, access token not provided or found for provided token - [$accessToken]");
        $msg = "Security issue with AccessToken";
        $statusMessage = $msg;

      }
      else{
        $userID = $accessTokenData["userID"];

        $passwordResetForm= getcwd() . DIRECTORY_SEPARATOR .
                        "forms" .
                        DIRECTORY_SEPARATOR .
                        "formPasswordReset.php";

        if (file_exists($passwordResetForm)){
          include ($passwordResetForm);
        }
        else{
          echo "could not find include file [$passwordResetForm]";
        }
        return;
      }
      break;
    case 'Logout': //Just expire the sessions and redirect home

      $userID = SessionManager::GetCurrentUserID();
      Transaction::Logout($userID);
      DataProvider::DELETE_AUTHORIZATIONS();
      Util::DestroySiteSession();
      Util::DeleteAllCookies();
      Util::SetWindowLocation("Login");

      break;
    case 'Login':

      session_destroy();
      //If an AuthCode is being passed, we are in Login Flow STEP 2
              // 1.) Request an accessToken
              // 2.) Start Session
              // 3.) Set location to home page
              // TODO: allow setting of desired homepage, and session timeout per user
      if (isset($authCode))
      {
        // TODO: Don't pass userID on URL , move to POST
        $userID = isset($_GET['userID']) ? $_GET['userID'] : null;

        if (is_nan($userID)){
          Log::error("LOGIN: UserID was not a valid number at all. ");
        }
        else if (!SecurityManager::ValidateAuthCode($authCode, $userID)){
          Log::error("LOGIN - authCode invalid");
        }
        else {
          $c = ConfigurationManager::GetLoadedResourceCount();
          $d = count(DataProvider::$PreparedStatementStrings);

          Log::debug("ROUTER -- Resources are now loaded - count: $c");
          Log::debug("ROUTER -- DATABASE PREPARES are now loaded - count: $d");

          //Now request an accessToken using the userID and auth

          $timeoutSecs = ConfigurationManager::GetParameter("Sessioning")->SessionTimeoutSecs;


          $accessTokenData = DataProvider::SET_ACCESS_TOKEN($userID, $authCode, $timeoutSecs);
          $accessToken      = $accessTokenData["accessToken"];
          $expiresUnixTime  = $accessTokenData["expires_unix_time"];

          // Start the session and set some data that should be good for the session
          SessionManager::StartSession($userID, $accessToken, $expiresUnixTime);

          Util::SetSessionParams($userID);

          Transaction::Login($userID);

          // LOGIN COMPLETE, Set some data in cookies and FORWARD TO THE DASHBOARD

          //FORWARD TO THE DASHBOARD
          //NOTE You can comment out this next line to prevent it forwarding and
          //  to perform any startup debugging messages
          Util::CreateCookie('accessToken', $accessToken);
          Util::SetWindowLocation('Dashboard', $accessToken);

        }
      }
      else{  //AuthCode was not passed, clean everything up if there are old $sessions
            //  If a /Login route is requested on site, user is logged out !
            Util::DestroySiteSession();
      }

      break;

    case 'Signup':
      $viewToInclude = "SignupView.php";
      $accessTokenRequired = false;
      break;
    case 'Masquerade':
      $clientID = isset($route_params['queryParameters']['clientID']) ?
                      $route_params['queryParameters']['clientID'] : "notSet";

      $currentUserID = SessionManager::GetCurrentUserID();
      if (isset($route_params['queryParameters']['start'])){

        if (!SessionManager::IsProviderForClient($clientID)){
          echo "<br>";
          Log::debug("User $currentUserID attempted to masquerade for $clientID which they are not a provider");
        }
        else{
          Util::StartMasquerade($currentUserID, $clientID);
        }
      }
      else if (isset($route_params['queryParameters']['end'])){

        if (!SessionManager::IsMasquerading()){
          Log::debug("User $currentUserID attempted to exit masquerade however they are not masquerading");
          Util::Logout();
        }
        else {
          Util::EndMasquerade();
        }
      }
      else{
        Log::error("User [$currentUserID] attempted to masquerade for [$clientID] which they are not a provider");
        Util::Logout();
      }
      break;

    default://These things require user to be logged in

      if (SessionManager::IsActive($accessToken)){

        $userID = $currentUserID = SessionManager::GetCurrentUserID();

        Log::info("==== SESSION IS Active .. Requested Resource: $resource");
        switch($resource)
        {
          case 'Configuration':
            if (SessionManager::IsAdministrator()){
              $viewToInclude = "ConfigurationView.php";
            }
            break;
          case 'Utility':
            if (SessionManager::IsAdministrator()){

                $action = isset($_POST['action']) ? $_POST['action'] : null;
                switch ($action) {
                  case 'import-data':
                    $sourceTableName = $_POST['source-table-name'];
                    $importType = $_POST['import-type'];
                    switch ($importType) {
                      case 'User':
                        $statusMessage = DataImportManager::ImportUsers($sourceTableName);
                        break;
                    }
                    break;
                }
                $viewToInclude = "UtilityView.php";
            }
            else{
              echo "ACCESS ERROR";
              Log::error("Non admin attempting to access utility view!");
              Util::SetWindowLocation("Logout");
            }
            break;
          case 'Dashboard':
            $viewToInclude = "DashboardView.php";
            break;
          case 'Inventory':
            $viewToInclude = "InventoryView.php";
            break;
          case 'Report':
            // // NOTE: Using same format as for Resource,
            //    but reporting params in path are shifted:

            //   /Resource  /resourceID /ResourceAction  /ResourceActionItem
            //   /Report    /User       /0               /ReportName

            //Create a resource object that has reports available for:
            //  Resource Type - At the Type level - all users, ...
            //        :: /Reports/User/0
            //  Resource Record -  At row level - Associations/Linked - Locations@Facility
            //        :: /Reports/User/1

            $resourceClassName = NAME_SPACE . "\\" . $resourceID;

            if (class_exists($resourceClassName)){
              $recordID = 0; //always default to 0, which will give type-level
              if (array_key_exists("resourceAction", $route_params)){
                $recordID = $route_params["resourceAction"] > 0 ? $route_params["resourceAction"] : 0;
              }
              if (array_key_exists("resourceActionItemData", $route_params) &&
                  !empty($route_params["resourceActionItemData"])
                ){
                $activeReport = $route_params["resourceActionItemData"][0];
                // NOTE:  URL path params 3 and after are packaged into this array
              }
              Log::debug("Creating resource $resourceClassName with recordID $recordID");

              $currentResource = new $resourceClassName($recordID);
            }
            //Now include the report form, which will reference the $currentResource
            $viewToInclude = "ReportView.php";
            break;

          case 'Search':
            $viewToInclude = "SearchView.php";
            break;

          default: // DYNAMIC RESOURCE - Getting a record
          // ====== GETTING A RECORD USING $resourceID passed in

            //Handle User resource for non-admins
            if (  $resource==="User" && $userID!=$resourceID &&
                  !SessionManager::IsAdministrator()
                )
            {
              Log::error("SECURITY - This user is not an admin,
                            but they requested another user OR CREATE NEW User record!!
                            Should not have happened.  Forcing back to this user id in READ mode");
              $resourceID = SessionManager::GetCurrentUserID();
              $formMode = "READ";
            }
            else if ($resource==="User" && isset($_POST['change-password'])){

              $newPassword = $_POST['new-password'];

              if (!empty($newPassword) && DataProvider::RESET_PASSWORD($resourceID, $accessToken, $newPassword, true)){
                $statusMessage = "Password Reset Success";
              }
              else{
                $statusMessage = "Password Reset Failure";
              }

            }

            $viewToInclude = null;
            $resourceID = ($resourceID>0) ? $resourceID : 0;
            $resourceClassName = NAME_SPACE . "\\" . $resource;
            if (class_exists($resourceClassName)){

              if( isset($route_params['queryParameters']['search'])){
                $searchString = $_POST['searchString'];
                $nameField = ConfigurationManager::GetResourceConfigParameter($resource,'displayFieldName')[0];
                $records = DataProvider::GetResourceIDByName($resource, $nameField, $searchString);

                if (!is_null($records)){
                  $resourceID = $records;
                }
              }
              $showForm = true;
              $class =  NAME_SPACE . "\\" . $resource;
              $currentRecord = new $class($resourceID);//Users[1], Storagefacility[100]
            }
            break;
        }
      }

  }//end of switch

  if (  !$accessTokenRequired ||
        ($accessTokenRequired && SessionManager::IsActive($accessToken))
      ){

    if (!empty($viewToInclude)){
      include $viewToInclude;
    }

    // When a request comes in, the mode of the form is passed from the previous
    //  request.  If it is empty, it is assumed that the form is in create mode

    //Form values are always quoted, remove quotes for non-string types
    if (  ($formMode === 'CREATE' || $formMode === 'UPDATE') &&
          isset($formData) &&
          isset($currentRecord)
        ){
      $formData = $currentRecord->PrepareFormData($formData);
    }

    switch($formMode)
    {
      case "CREATE":

        $id = $currentRecord->InsertRecord($formData);

        //Now that record is inserted, reset record forcing form to reload
        // and ready to insert another one
        $currentRecord = $currentRecord->GetNewInstance();

        $route_params["formProperties"]["MODE"] = "CREATE";

        break;

      case "READ":
        $currentRecord->GET($ID);
        break;

      case "UPDATE":
        $currentRecord->UpdateRecord($formData);
        break;
      case "DELETE":

        $currentRecord->DeleteRecord($resourceID);
        //setup this form ready to insert another one
        $currentRecord = $currentRecord->GetNewInstance();
        $route_params["formProperties"]["MODE"] = "CREATE";

        break;

      default:

        //set mode for form below
        if ( isset($resourceID) && $resourceID>0 ){
          $route_params["formProperties"]["MODE"] = "UPDATE";
        }
        else if ($showForm){
          Log::debug("Mode was default.  setting form mode to create");
          $route_params["formProperties"]["MODE"] = "CREATE";
        }
        break;
    }//end CRUD SWITCH

    //SHOW THE RECORD FORM
    if ($showForm)
    {
      $currentRecord->SetFormProperties($route_params["formProperties"]);
      include "DefaultView.php";
    }
  } else {
    if (!is_null($accessToken)){
      $loginMessage = "Session expired, please login again.";
      Util::DestroySiteSession();
      $accessToken = $authCode = null;
    }
    include "LoginView.php";
  }
}
}
