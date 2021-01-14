<?php
namespace Ability\Warehouse;

/*
General Utility functions
*/
class Util
{

  /*  Extract input parameters from $_POST, $_GET, and $URL

    // TWO TYPES OF PARSING depending on deployment/setup:
    //  TYPE 1:  Path based - /api  or /stocker ..

    // API: /api/storagefacility/3/associate/facilityowners/user?userid=2
    // STOCKER: /stocker/login

    //  TYPE 2: domain based - api.warehousedashboard.com / stocker.whd.com etc
    // API: /storagefacility/3/associate/facilityowners/user?userid=2
    // STOCKER: stocker.warehousedashboard.com/login

        SITE and Reporting is always domain based
    // SITE: /Storagefacility/3
    // REPORTING: /Reports/Storagefacility?{minid;maxid;sortfield;sortorder;recordids}

    // RESOURCES and RecordIDs
    $Resource - User, Facility, ...
    $ResourceID - One specific record  (Can come from path OR $POST.  If both exist, they must match (for security))


    @param $subdir - null;
    @returns array["resource", "resourceID", "formProperties"['showCancel, 'Mode']]

  */
  public static function get_parameters($subDir)
  {
    Log::info("Util Getting the parameters using subdir = [$subDir]");
    //Extract Resource and ResoureceID (OPT) from URL
    $URI = explode('/', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

    //Callers will indicate if their services exists in a subdirectory
    //    i.e. /api or /stocker vs on the base domain api.domain
    //  Only one deep supported

    $resourceIndex = $subDir==true ? 2 : 1;

    //Resource is ALWAYS obtained from the path
    $resource = $URI[$resourceIndex];

    Log::debug("resource is: $resource subdir is [$subDir] resourceIndex is [$resourceIndex]");

    //Resource ID and Action can come from path OR from $_POST form, post takes priority
    $resourceID = (count($URI)>$resourceIndex+1) ? $URI[$resourceIndex+1] : null;
    $resourceAction = (count($URI)>$resourceIndex+2) ? $URI[$resourceIndex+2] : null;

    //Now package the remaining parameters into an array.  Each call will pull out orde
    $resourceActionItemData = (count($URI)>$resourceIndex+3) ? array($URI[$resourceIndex+3]) : null;

    if (count($URI)>$resourceIndex+4){
      $resourceActionItemData[] = $URI[$resourceIndex+4];
    }

    if (isset($_POST))
    {
      $Mode = isset($_POST['MODE']) ? $_POST['MODE'] : null;
      //see if the resourceID came from a form
      $resourceID = (isset($_POST['ID'])) ? $_POST['ID'] : $resourceID;

      //No Mode was set, so this didn't come in from a data form
      if (!isset($Mode))
      {
        if ( isset($_POST["add"]))
        {
          $Mode = "ADDNEW";
          $resourceID = 0;
        }
        else if ( isset($_POST["delete"]))
        {
          $Mode = "DELETE";
        }
      }
    }

    // TODO, what other properties could be set on a form (AFTER the input has been scanned at this point)
    $formProperties = array("MODE"=>$Mode);

    parse_str($_SERVER['QUERY_STRING'], $queryParameters);

    return array("resource"=>$resource,
                  "resourceID"=>$resourceID,
                  "resourceAction"=>$resourceAction,
                  "resourceActionItemData"=>$resourceActionItemData,
                  "formProperties"=>$formProperties,
                  "queryParameters"=>$queryParameters);

  }
  /*
    Verify inbound request, extract headers.
    Must contain: Authorization, AccessToken
    returns bool success
  */
  public static function verify_request()
  {

    $valid = false;

    foreach ($_SERVER as $name => $value)
    {
       if (substr($name, 0, 5) == 'HTTP_')
       {
           $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
           $headers[$name] = $value;
       } else if ($name == "CONTENT_TYPE") {
           $headers["Content-Type"] = $value;
       } else if ($name == "CONTENT_LENGTH") {
           $headers["Content-Length"] = $value;
       }
    }

    if ($headers["Authorization"]){
      if ($headers["Accesstoken"]){
        $valid = true;
        Log::info("Authorization passes for remote client: " . $_SERVER["REMOTE_ADDR"]);
      }
      else{
        Log::error("Access token missing from header: " . $_SERVER["REMOTE_ADDR"]);
      }
    }
    else{
      Log::error("Authorization missing in header: "  . $_SERVER["REMOTE_ADDR"]);
      Log::error($headers);
    }
    return $valid;
  }
  /* Build an HTTP Error response
    @param httpCode - which code to send in reply, 404, 500, etc
    @param message - Text message to send in reply
  */
  public static function build_error_reply($httpCode, $message){
    return "HTTP/1.1 " . $httpCode . " " . Constants\Http::RESPONSE_CODES[$httpCode] . " " . $message;

  }
  /*
    Convert an array into CSV.  Uses in-memory array
    https://stackoverflow.com/questions/7362322/get-return-value-of-fputcsv
    @param $ary - associative array [colName]=value
    @param $dbLabels - What is shown
  */
  public static function Array2csv($ary, $dbLabels, $addquotes=false)
  {
    if ($addquotes)
    {
      foreach ($ary as $key => $value) {
        try {
          //TODO - this was added to skip ID field, look at better way
          if (key_exists($key, $dbLabels))
          {
            if ($dbLabels[$key]["dataType"]=="string")
            {
                $ary[$key] .= "@# "; //add this to force escaping by fputcsv below
            }
          }
        } catch (\Exception $e) {
          Log::error("ERROR: " . $e->getMessage());
        }
      }
    }
    $buffer = fopen('php://temp', 'r+');
    fputcsv($buffer, $ary);
    rewind($buffer);
    $csv = fgets($buffer);
    fclose($buffer);

    if ($addquotes)
      $csv = str_replace("@# ", "", $csv);

    return $csv;
  }
  public static function guidv4()
  {
      //https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
      $data = openssl_random_pseudo_bytes(16);

      assert(strlen($data) == 16);

      $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
      $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

      return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }
  public static function GenerateAuthorizationCode(){
    return Util::guidv4();
  }
  public static function GenerateAccessToken(){
    return Util::guidv4();
  }
  /*
  * Return a formatted date:  -- Y-m-d H:i:s
  */
  public static function GetFormattedDate($unixTimeStamp){
    if (!is_nan($unixTimeStamp)){
      $date = new \DateTime("@$unixTimeStamp");
      return $date->format('Y-m-d H:i:s');
    } else {
        return null;
    }
  }
  public static function GetFormattedDateMySQL($mysqldate, $formatter=null){

    if (empty($mysqldate)){
      return "";
    }
    if (is_null($formatter)){
      $formatter = 'm/d/y';
    }
    $phpdate = strtotime( $mysqldate );
    return date( $formatter, $phpdate );
  }
  /*
  *  Return Minutes remaining in string form
  * @param long : unixTimeEnd - Unix timestamp
  */
  public static function GetFormattedMinutesRemaining($unixTimeEnd){

    if (!is_nan($unixTimeEnd)){
      $minutesleft = round( (($unixTimeEnd - time())/60), 2);
      return strval($minutesleft);
    }
    else {
      return "0";
    }
  }
  /*
    For any sessioning saved for a user:
      Delete the Database authorization entries
      Delete all sessions
  */
  public static function DestroySiteSession(){
    Log::debug("DestroySiteSession()");
    DataProvider::DELETE_AUTHORIZATIONS(SessionManager::GetCurrentUserID());
    SessionManager::EndSession();
  }
  /*
  * echo <script>window.setlocation
  */
  public static function SetWindowLocation($resource, $accessToken=null, $queryParameters=null){
    echo "<script>";
    $queryString = "";
    if (!is_null($accessToken)){
      $queryString = "?accessToken=$accessToken$queryString";
    }

    if(!empty($queryParameters)){
      foreach ($queryParameters as $key=>$value) {
        $queryString .= "&$key=$value";
      }
    }
    $apiURL   = ConfigurationManager::GetParameter("APIURL");
    $siteURL  = ConfigurationManager::GetParameter("SiteURL");

    echo "General.setWindowURL('" . $siteURL . "/$resource$queryString');";
    echo "</script>";
  }
  public static function Logout(){
    self::SetWindowLocation("Logout");
  }
  public static function WindowReload(){
    echo "<script>General.reload();</script>";
  }
  public static function DeleteAllCookies(){
    echo "<script>General.deleteAllCookies();</script>";
  }
  public static function DeleteCookie($name){
    echo "<script>General.deleteCookie($name);</script>";
  }
  public static function CreateCookie($name, $value, $expiryInSeconds=null){
    echo "<script>
          General.createCookie('$name', `$value`);
    </script>";
  }
  public static function StartMasquerade($currentUserID, $clientID){

    SessionManager::SetParameter(Constants\SessionVariableNames::MASQUERADING_USER_ID, $currentUserID);
    SessionManager::SetParameter(Constants\SessionVariableNames::IS_MASQUERADING, true);
    SessionManager::SetParameter(Constants\SessionVariableNames::USER_ID, $clientID);

    //Set CurrentProvider Session params for this client
    SessionManager::GetProvider();//getting will set the params

    Transaction::StartMasquerade($currentUserID, $clientID);

    self::CreateCookie('userID', $clientID);

    Util::SetSessionParams($clientID);
    Util::SetWindowLocation("Dashboard",SessionManager::GetParameter(Constants\SessionVariableNames::ACCESS_TOKEN));
  }
  public static function EndMasquerade(){

    $clientID = SessionManager::GetCurrentUserID();
    $accessToken = SessionManager::GetAccessToken();
    $providerUserID = SessionManager::GetParameter(Constants\SessionVariableNames::MASQUERADING_USER_ID);
    SessionManager::SetParameter(Constants\SessionVariableNames::USER_ID, $providerUserID);

    //Delete the parameters that were driving the masquerade, and reset the original one back
    SessionManager::DeleteParameter(Constants\SessionVariableNames::MASQUERADING_USER_ID);
    SessionManager::DeleteParameter(Constants\SessionVariableNames::IS_MASQUERADING);

    self::CreateCookie('userID', $providerUserID);

    Transaction::EndMasquerade($providerUserID, $clientID);
    Util::SetSessionParams($providerUserID);

    //reset the current provider params
    SessionManager::GetProvider();

    $queryParameters = array("tabid"=>"clients");

    Util::SetWindowLocation("Provider",$accessToken,$queryParameters);
  }
  /*
  *  Set/Reset Session Parameters
  */
  public static function SetSessionParams($userID){

    $userRecord = new User($userID);
    $isAdministrator  = $userRecord->IsAdministrator;//avoid db hit because it is already loaded onto User
    $isProvider       = DataProvider::IsProvider($userID);
    $isEmployee       = DataProvider::IsEmployee($userID);
    $isClient         = DataProvider::IsClient($userID);


    SessionManager::SetParameter(Constants\SessionVariableNames::IS_ADMIN, $isAdministrator);
    SessionManager::SetParameter(Constants\SessionVariableNames::IS_PROVIDER, $isProvider);
    SessionManager::SetParameter(Constants\SessionVariableNames::IS_EMPLOYEE, $isEmployee);
    SessionManager::SetParameter(Constants\SessionVariableNames::IS_CLIENT, $isClient);

    //Some user related info
    SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_EMAIL, $userRecord->DB_Fields["emailaddress"]);
    SessionManager::SetParameter(Constants\SessionVariableNames::CURRENT_USER, $userRecord->DB_Fields["profilename"]);

    //These will lazy load
    SessionManager::GetLoadedResourceNames();
    SessionManager::GetAccessibleResourceNames();


    //currently this removes the provider dashboard card
    $enforceSingleProvider = ConfigurationManager::GetParameter(Constants\SessionVariableNames::SINGLE_PROVIDER);
    SessionManager::SetParameter(Constants\SessionVariableNames::SINGLE_PROVIDER, $enforceSingleProvider);

    //Client specific

    if ($isClient){
      SessionManager::GetProvider();//getting triggers lazy load
    }
  }

  /*
  * internal polyfill for PHP 7.3 capability
  */
  public static function array_key_first(array $array) {
    foreach ($array as $key => $value) { return $key; }
  }

  public static function ResourceClassExists($className){

    //first reference calls upon the autoloader, second verifies
    if (  !class_exists(NAME_SPACE . "\\" . $className) &&
          !class_exists(NAME_SPACE . "\\" . ucfirst($className))
        ){
          return class_exists($className,false) || class_exists(ucfirst($className));
      }
      else{
        return true;
      }
  }
}
