<?php
namespace Ability\Warehouse;
/**
 *  Simple wrapper for session management
 */
class SessionManager
{
  public static function ResumeSession(){
    session_start();
  }
  /*
  * Start a session, and load the configuration a resource
  */
  public static function StartSession($userID, $accessToken, $expiresUnixTime){
    $sessionStatus = session_status();

    if ($sessionStatus === PHP_SESSION_DISABLED){
      Log::error("Sessions are disabled.  This is a server configuration");
      echo "ERROR";
      die();
    }
    else if ($sessionStatus!==PHP_SESSION_ACTIVE)
    {
      Log::info("SESSION - STARTING SESSION -Starting session for user $userID and accessToken $accessToken");
      session_start();
    }

    //Load the configuration into session
    $loaded = self::_loadConfigIntoSession(true);

    if (!$loaded){
      session_destroy();
      Log::debug("CONFIGURATION SETUP ERROR - There are no resources loaded.");
    }
    else{
      $_SESSION[Constants\SessionVariableNames::USER_ID]       = $userID;
      $_SESSION[Constants\SessionVariableNames::ACCESS_TOKEN]  = $accessToken;
      $_SESSION[Constants\SessionVariableNames::IS_ADMIN]      = DataProvider::IsUserAdmin($userID);
      $_SESSION[Constants\SessionVariableNames::EXPIRE_TIME]   = $expiresUnixTime;
      Log::debug("SESSION STARTED success  Expires was setup as $expiresUnixTime ");
    }
  }
  public static function EndSession(){
    unset($_SESSION[Constants\SessionVariableNames::USER_ID]);
    unset($_SESSION[Constants\SessionVariableNames::ACCESS_TOKEN]);
    unset($_SESSION[Constants\SessionVariableNames::IS_ADMIN]);
    unset($_SESSION[Constants\SessionVariableNames::EXPIRE_TIME]);
    unset($_SESSION[Constants\SessionVariableNames::CONFIG_SITE]);
    unset($_SESSION[Constants\SessionVariableNames::IS_MASQUERADING]);
  }
  public static function SetParameter($name, $value){
    if (gettype($value)!='array'){
      Log::debug("SESSION - SETTING $name to $value");
    }
    $_SESSION[$name] = $value;
  }
  public static function GetParameter($name){
    return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
  }
  public static function DeleteParameter($name){
    unset($_SESSION[$name]);
  }
  public static function GetCurrentUserID(){
    return self::GetParameter(Constants\SessionVariableNames::USER_ID);
  }
  public static function GetCurrentUsername(){
    return self::GetParameter(Constants\SessionVariableNames::CURRENT_USER);
  }
  public static function GetCurrentUserFullName(){
    return self::GetParameter(Constants\SessionVariableNames::CURRENT_USER_FULL_NAME);
  }
  public static function GetCurrentUserNickName(){
    return self::GetParameter(Constants\SessionVariableNames::CURRENT_USER_NICK_NAME);
  }

  public static function GetCurrentEmailAddress(){
    return self::GetParameter(Constants\SessionVariableNames::CURRENT_EMAIL);
  }
  public static function GetAccessToken(){
    return self::GetParameter(Constants\SessionVariableNames::ACCESS_TOKEN);
  }
  /*
  * List of clientIDs (UserIDs associated with this provider)
  */
  public static function GetClientIDs(){
    //If empty, set it by accessing via DataProvider
    $clients = self::GetClients();
    return !is_null($clients) ? array_keys($clients) : null;
  }
  /*
  * Get Clients (If user is employee, then get all clients for providers they work for )
  */
  public static function GetClients($userID = null){

    $clients = null;

    if (is_null($userID))
    {
      $userID = self::GetCurrentUserID();
    }
    if (!self::IsAdministrator()) {
      if (self::IsEmployee($userID)) {
        $providerIDs = self::GetProvidersForEmployee($userID);
        foreach ($providerIDs as $providerID) {
          $clients = DataProvider::GetClientsForProvider($providerID);
        }
      } else {
        $clients = DataProvider::GetClients($userID);
      }
    }

    return $clients;
  }
  public static function GetReceivers($userID = null){

    if (is_null($userID))
    {
      $userID = self::GetCurrentUserID();
    }
    return DataProvider::GetReceivers($userID);
  }

  public static function GetClientName($clientID){
    return self::GetClients()[$clientID]['name'];
  }
  public static function IsProviderForClient($clientID){

    $clients = self::GetClients();
    foreach ($clients as $client) {
      if ($client["id"]==$clientID){
        return true;
      }
    }
    return false;
  }
  public static function GetEmployees($userID=null){

    if (is_null($userID))
    {
      $userID = self::GetCurrentUserID();
    }
    return DataProvider::GetEmployees($userID);
  }
  public static function GetProvider($clientID=null){

    $provider = null;

    if (self::IsMasquerading() || is_null(self::GetParameter(Constants\SessionVariableNames::CURRENT_PROVIDER_ID))){

      if (is_null($clientID)){
          $clientID = self::GetCurrentUserID();
      }

      $providerID = DataProvider::GetProviderForClient($clientID);
      $provider = new Provider ($providerID);
      $providerName = $provider->GetDisplayText();
      $providerLogoPath = $provider->GetLogoPath();

      self::SetParameter(Constants\SessionVariableNames::CURRENT_PROVIDER_ID, $providerID);
      self::SetParameter(Constants\SessionVariableNames::CURRENT_PROVIDER_NAME, $providerName);
      self::SetParameter(Constants\SessionVariableNames::CURRENT_PROVIDER_LOGO_PATH, $providerLogoPath);
    }

    return $provider;
  }
  public static function GetProvidersForEmployee($employeeID=null){

    if (is_null($employeeID))
    {
      $employeeID = self::GetCurrentUserID();
    }

     return  DataProvider::GetProvidersForEmployee($employeeID);
  }
  public static function GetFacilitiesForEmployee($employeeID=null){
    if (is_null($employeeID))
    {
      $employeeID = self::GetCurrentUserID();
    }
    return DataProvider::GetFacilitiesForEmployee($employeeID);
  }
  public static function GetCurrentFacility($employeeID){
     return self::GetParameter(Constants\SessionVariableNames::CURRENT_FACILITYID);
  }
  public static function SetCurrentFacility($facilityID){
     return self::SetParameter(Constants\SessionVariableNames::CURRENT_FACILITYID, $facilityID);
  }
  public static function IsEmployee($userID=null){
    if (is_null($userID))
    {
      $userID = self::GetCurrentUserID();
    }
    $isEmployee = self::GetParameter(Constants\SessionVariableNames::IS_EMPLOYEE);
    if (is_null($isEmployee)){
      $isEmployee = DataProvider::IsEmployee($userID);
      self::SetParameter(Constants\SessionVariableNames::IS_EMPLOYEE, $isEmployee);
    }
    else{
      Log::debug("Is Employee was already set to [$isEmployee]");
    }
    return $isEmployee;
  }
  public static function IsAdministrator($userID=null){

    if (is_null($userID)){
      $userID = self::GetCurrentUserID();
    }
    $isAdmin = self::GetParameter(Constants\SessionVariableNames::IS_ADMIN);
    if (!isset($isAdmin) || is_null($isAdmin)){
      if (is_null($userID)){
        throw new \Exception("Error Processing Request", 1);

      }
      $isAdmin = DataProvider::IsUserAdmin($userID);
      self::SetParameter(Constants\SessionVariableNames::IS_ADMIN, $isAdmin);
    }

    return $isAdmin;
  }
  public static function IsProvider($userID=null){

    if (is_null($userID))
    {
      $userID = self::GetCurrentUserID();
    }
    $isProvider = self::GetParameter(Constants\SessionVariableNames::IS_PROVIDER);
    if (!isset($isClient) || is_null($isClient)){
      $isProvider = DataProvider::IsProvider($userID);
      self::SetParameter(Constants\SessionVariableNames::IS_PROVIDER, $isProvider);
    }
    Log::debug("PROVIDER IS [$isProvider]");

    return $isProvider;
  }
  public static function IsClient($userID=null){

    if (is_null($userID))
    {
      $userID = self::GetCurrentUserID();
    }
    $isClient = self::GetParameter(Constants\SessionVariableNames::IS_CLIENT);
    if (!isset($isClient) || is_null($isClient)){
      $isClient = DataProvider::IsClient($userID);
      self::SetParameter(Constants\SessionVariableNames::IS_CLIENT, $isClient);
    }
    Log::debug("IS CLIENT IS [$isClient]");

    return $isClient;
  }
  /*
  *  If key does not exist, user has no CRUD at all to this resource
  */
  public static function GetAccessibleResourceNames($forceReload=false){

    $activeResourceNames = self::GetParameter(Constants\SessionVariableNames::ACCESSIBLE_RESOURCES);
    if (is_null($activeResourceNames) || $forceReload){
      $activeResourceNames = ConfigurationManager::GetLoadedResourceNames();

      self::SetParameter(Constants\SessionVariableNames::ACCESSIBLE_RESOURCES, $activeResourceNames);
    }
    return $activeResourceNames;
  }
  public static function GetLoadedResourceNames(){

    $loadedResourceNames = self::GetParameter(Constants\SessionVariableNames::LOADED_RESOURCES);
    if (is_null($loadedResourceNames)){
      $loadedResourceNames = ConfigurationManager::GetLoadedResourceNames();
      self::SetParameter(Constants\SessionVariableNames::LOADED_RESOURCES, $loadedResourceNames);
    }
    return $loadedResourceNames;
  }
  /*
  *  Get a string array of currently loaded resources with navigation
  *    parameters used to describe the resource
  *
  */
  public static function GetNavigationResources(){

    $resourceNavConfigItems = array();
    $resourceConfigs = self::GetAccessibleResourceNames();

    $allowedNavs = array();

    if (SessionManager::IsProvider()){
      $allowedNavs = ConfigurationManager::GetParameter("resourceNavigationsByRole")->Provider;
    }
    else if (SessionManager::IsEmployee()){
      $allowedNavs = ConfigurationManager::GetParameter("resourceNavigationsByRole")->Employee;
    }
    else if (SessionManager::IsClient()){
      $allowedNavs = ConfigurationManager::GetParameter("resourceNavigationsByRole")->Client;
    }

    foreach ($resourceConfigs as $resourceName ) {

      if (SessionManager::IsAdministrator() || in_array($resourceName, array_values($allowedNavs))){
        $resourceConfigItem = ConfigurationManager::GetResourceConfig($resourceName);

        $resourceNavConfigItems[$resourceName] =
                  array(  "resourceName"  => $resourceName,
                          "url"           => $resourceConfigItem["navigationMenuURL"],
                          "displayText"   => $resourceConfigItem["navigationMenuText"],
                          "resourceImageLarge" => $resourceConfigItem["resourceImageLarge"]
                        );
        Log::debug("ADDED ---- $resourceName");
      }
      else{
        Log::debug("Navigation - skipping $resourceName because this user is in a role that says to not show it.  resourceNavigationsByRole");
      }

    }
    if (!SessionManager::IsAdministrator()){
      unset($resourceNavConfigItems['Storagefacility']);
      unset($resourceNavConfigItems['Shipment']);
      unset($resourceNavConfigItems['Storage']);
    }

    return $resourceNavConfigItems;
  }

  /*
  * // Called when building drop-downs and also determining total ownership count
  *
  * @returns:  array or owned records [id,name]
  */
  public static function GetOwnedRecordIDs($resourceName, $userID=null, $minID=0, $maxID=0){

    if (is_null($userID)){
      $userID = self::GetCurrentUserID();
    }
    return DataProvider::GetAccessibleRecords($resourceName, $userID);
  }
  public static function GetAccessibleRecordIDs($resourceName, $userID = null){

    if (is_null($userID)){
      $userID = self::GetCurrentUserID();
    }

    return DataProvider::GetAccessibleRecords($resourceName, $userID);
  }
  public static function GetAssignableResources($resourceName, $userID = null){

    if (is_null($userID)){
      $userID = self::GetCurrentUserID();
    }

    return DataProvider::GetAssignableResources($resourceName, $userID);
  }

  public static function GetCountOfAccessibleRecords($resourceName, $userID=null){
    if (is_null($userID)){
      $userID = self::GetCurrentUserID();
    }
    $records = DataProvider::GetAccessibleRecords($resourceName, $userID);

    return is_null($records) ? 0 : count($records);
  }
  /**
  * Verify if a Session is active:  isset(accessToken) && is correct
  * @param $accessToken - if supplied, must match, if empty simply look at session
  * @return bool
  */
  public static function IsActive($accessToken=null)
  {
    if ($accessToken===null){
      //If not provided, try to pull from GET
      $accessToken = $_GET[Constants\QueryParameterNames::ACCESS_TOKEN] ?? 0;
    }
    else{
      Log::debug("Checking token $accessToken");
    }

    return  !self::IsExpired($accessToken) &&
             self::IsStarted() &&
            isset($_SESSION[Constants\SessionVariableNames::ACCESS_TOKEN]) &&
            $_SESSION[Constants\SessionVariableNames::ACCESS_TOKEN]===$accessToken;
  }
  /*
  * Returns true if a session is expired
  *  Extends time if not expired
  */
  public static function IsExpired($accessToken){


    $expiresUnixTime = isset($_SESSION[Constants\SessionVariableNames::EXPIRE_TIME]) ?
                              $_SESSION[Constants\SessionVariableNames::EXPIRE_TIME] : 0;

    $expired = time() > $expiresUnixTime;

    if (!$expired){
      $timeoutSecs = ConfigurationManager::GetParameter("Sessioning")->SessionTimeoutSecs;

      $_SESSION[Constants\SessionVariableNames::EXPIRE_TIME]  = time() + $timeoutSecs;
    }
    else {
      Log::info("SESSION TIMED OUT for accessToken [$accessToken]");
    }

    return $expired;
  }
  /*
    Determine if session has been started.  Does not look at any data
    @return: true/false based on session_status
  */
  public static function IsStarted(){
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            if (session_status() === PHP_SESSION_ACTIVE){
              return true;
            }
        } else {
            return session_id() === '';
        }
    }
    return FALSE;
  }
  public static function IsMasquerading(){

    return !is_null(self::GetParameter(Constants\SessionVariableNames::IS_MASQUERADING)) ?
        self::GetParameter(Constants\SessionVariableNames::IS_MASQUERADING) : false;
  }
  // CONFIGURATION
  //  Load from Configuration Manager into Session object
  private static function _loadConfigIntoSession($forceReload=false){

    $resourcesExist = ConfigurationManager::HasResources();

    if ( $forceReload || !$resourcesExist){
      $c = ConfigurationManager::GetLoadedResourceCount();
      Log::debug("===== SESSION SET ===== $c Resources were loaded from ConfigManager into session ");
      $_SESSION[Constants\SessionVariableNames::CONFIG_SITE] = ConfigurationManager::GetConfigurationObject();
      return true;
    }
    else{
      return ConfigurationManager::GetLoadedResourceCount() > 1;
    }
  }
  /*
    Returns true/false if configuration has already been loaded
  */
  public static function ConfigurationIsLoaded(){
    return isset($_SESSION[Constants\SessionVariableNames::CONFIG_SITE]);
  }
  /*
  * Load the configurations if they do not exist already
  */
  public static function LoadConfigurations(){

    $sessionInConfig = isset($_SESSION[Constants\SessionVariableNames::CONFIG_SITE]) ?
        $_SESSION[Constants\SessionVariableNames::CONFIG_SITE] : null;

    if (is_null($sessionInConfig)){
      Log::warning("======SESSION DISK IO --- Configuration was not loaded, asking ConfigurationManager to load a new one");

      ConfigurationManager::LoadStaticConfigurations();
      self::_loadConfigIntoSession(true);
    }
    else{
      return $sessionInConfig;
    }
  }
  /*
  *  Get the count of currently loaded resources
  */
  public static function GetLoadedResourceCount(){

    $configuration = $_SESSION[Constants\SessionVariableNames::CONFIG_SITE];
    return count($configuration->Resources);
  }
}
