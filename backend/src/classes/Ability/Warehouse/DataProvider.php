<?php
/*
Class DataProvider provides common CRUD operations to multiple DB types (mysql/pgsql)
 used for  managing data in a DB (mysql or postgresql)
*/

namespace Ability\Warehouse;

class DataProvider {

  function __construct($configuration) {

    $databaseName   = $configuration->databasename;
    $host           = $configuration->host;
    $port           = $configuration->port;
    $user           = $configuration->username;
    $password       = $configuration->password;
    $dbType         = self::$DBType;

    self::$PerPageDefault = $configuration->defaultPerPage;
    self::$PerPageMax = $configuration->maxPerPage;
    self::$PerPageMin = $configuration->minPerPage;
    self::$MaxSqlLimits = $configuration->queryLimits;

    try {
      $this->handler = new \PDO("$dbType:host=$host;port=$port;dbname=$databaseName", $user, $password);
      $this->handler->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
      if (!$this->handler){
        throw new \Exception("Error: Unable to connect to Database!" . PHP_EOL);
      }
    } catch (\PDOException $e) {
        $error = "Error with Database Connection for PDO type - $dbType";
        $error .= "---" . $e->getMessage();
        error_log($error);
        error_log($e);
        error_log("$dbType:host=$host;dbname=$databaseName, $user, $password");
        exit;
    }



  }

  // STATIC MEMBERS



  // Dictionary of Parametized strings used for DB prepare statements, keyed
  //  by Constants
  public static $PreparedStatementStrings = array();
  public static $DBType;
  public static $MaxSqlLimits   = 2000;
  public static $PerPageDefault = 50;
  public static $PerPageMin     = 50;
  public static $PerPageMax     = 100;

  // Instance of the child class, lazy loaded
  public static $DBProviderInstance;

  /*
    Get Singleton instance of the current DataProvider
    Lazy loaded, references Configuration object
  */
  public static function GetInstance(){

    if (is_null(self::$DBType)){
      $dbType = self::$DBType = ConfigurationManager::GetParameter("Database")->type;
    }
    if (empty(self::$DBProviderInstance)){
      Log::debug("Initializing DataProvider instance of type: " . static::$DBType);

      try {
        if (self::$DBType==Constants\ConfigurationParameterNames::DATABASE_TYPE_MYSQL){
            $dbConfig = ConfigurationManager::GetParameter("Database")->mysql;
            self::$DBProviderInstance = new MySQLDataProvider($dbConfig);

        } elseif (self::$DBType==Constants\ConfigurationParameterNames::DATABASE_TYPE_POSTGRES){
            $dbConfig = ConfigurationManager::GetParameter("Database")->postgres;
            self::$DBProviderInstance = new PostgreSQLDataProvider($dbConfig);
        }
      } catch (Exception $e) {
          Log::error($e->getMessage());
      }
    }
    return self::$DBProviderInstance;
  }
  /*
    Add common prepared statements for all Active resources
    Uses PDO prepare :varname format
  */
  public static function LoadPrepareStatements(){


    if (is_null(self::$DBType)){
      self::$DBType = ConfigurationManager::GetParameter("Database")->type;
    }
    $databaseConfig = ConfigurationManager::GetParameter("Database");
    $dbType         = self::$DBType;


    //Add non-resource prepares:  Auth
    self::$PreparedStatementStrings["AUTH"] = "SELECT * FROM user WHERE profilename = :profilename OR emailaddress = :emailaddress";

    $loadedResourceNames    = ConfigurationManager::GetLoadedResourceNames();
    $loadedResourceNames[]  = "User";//always add user

    foreach ($loadedResourceNames as $resourceName) {
      $fqResourceName = NAME_SPACE."\\".$resourceName;

      $tableName = $fqResourceName::$TableName;

      $databaseName   = $databaseConfig->$dbType->databasename;
      $full_tableName = $databaseName . "." . $tableName;
      Log::debug("Setting up PREPARE Statements for [$resourceName] using tablename [$tableName] as the key.");

      $oneInstanceKey = Constants\SqlPrepareTypes::SQL_SELECT_ONE . $tableName;

      if (array_key_exists($oneInstanceKey, self::$PreparedStatementStrings)){
        Log::warning("Resource [$resourceName] was already prepared.  Should not have happened, logic issue, no impact");
        return;
      }

      //Normally each table has 'name', however User has firstname, lastname
      $displayFieldStr = $fqResourceName::GetDisplayFieldsCSV($resourceName);

      $indexFieldName   = $fqResourceName::$IndexFieldName;
      $orderByFieldName = $fqResourceName::$OrderByFieldName;
      $orderByDirection = $fqResourceName::$OrderByDirection;

      $resourceConfig   = ConfigurationManager::GetResourceConfig($resourceName);
      $dbLabels         = $resourceConfig["fields"];

      if (array_key_exists("dependentCollections", $resourceConfig)){
        $dependentCollections = $resourceConfig["dependentCollections"];
      }
      if (array_key_exists("associativeCollections", $resourceConfig)){
        $associativeCollections = $resourceConfig["associativeCollections"];
      }

      //Common SQL Templates
      $selectBase     = "SELECT * FROM "      . $full_tableName;
      $deleteBase     = "DELETE FROM "        . $full_tableName;
      $insertBase     = "INSERT INTO "        . $full_tableName;
      $updateBase     = "UPDATE "             . $full_tableName;
      $whereBase      = " WHERE " . $indexFieldName;

      //Build SQL statements that are used in INSERT and UPDATE statements

      $insertSetClause = ""; //field1, field2
      $updateSetClause = ""; //field1=:field1, field2=:field2

      foreach (array_keys($dbLabels) as $fieldName) {
        $fieldTag = ":$fieldName";
        $insertSetClause .= $fieldName . ",";
        if ( !isset($dbLabels[$fieldName]['read-only'])){
          $updateSetClause .= $fieldName . "=$fieldTag,";
        }
        else{
          Log::debug("Skipping $fieldName");
        }
      }
      //remove the trailing ,
      $insertSetClause = substr($updateSetClause, 0, -1);
      $updateSetClause = substr($updateSetClause, 0, -1);

      //Get the user defined ownershipInfo, which helps to build ownership relative queries

      $ownedByFieldName                 = User::$ResourceOwnerships[$resourceName]->OwnedByFieldName;
      $ownerResourceTableName           = User::$ResourceOwnerships[$resourceName]->OwnerResourceTableName;
      $ownedByResourceOwnedByFieldName  = User::$ResourceOwnerships[$resourceName]->OwnerResourceOwnedByFieldName;
      $ownerResourceTableIndexFieldName = User::$ResourceOwnerships[$resourceName]->OwnerResourceIndexFieldName;
      $coOwnerResourceTableName         = User::$ResourceOwnerships[$resourceName]->CoOwnerResourceTableName;
      $coOwnerResourceOwnedByFieldName  = User::$ResourceOwnerships[$resourceName]->CoOwnerResourceOwnedByFieldName;
      $coOwnerResourceFieldName         = User::$ResourceOwnerships[$resourceName]->CoOwnerResourceFieldName;

      self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_SELECT_NAV_OWN . $tableName] =
           "SELECT $indexFieldName, $displayFieldStr FROM $full_tableName WHERE $ownedByFieldName" .
              " IN (SELECT $ownerResourceTableIndexFieldName
              FROM $ownerResourceTableName
              WHERE $ownerResourceTableName.$ownedByResourceOwnedByFieldName = :searchField)";

      // This resource has co-ownership, meaning that we need to also look in association tables
      if (ConfigurationManager::IsResourceCoOwned($resourceName)){
          $coOwnerResourceFullTableName = "$databaseName.$coOwnerResourceTableName";

          self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_SELECT_NAV_OWN . $tableName] .=
          " UNION SELECT $indexFieldName, $displayFieldStr FROM $full_tableName" .
          " INNER JOIN $coOwnerResourceFullTableName
            ON $coOwnerResourceFullTableName.$coOwnerResourceOwnedByFieldName =
            $full_tableName.$indexFieldName
            WHERE $coOwnerResourceFullTableName.$coOwnerResourceFieldName IN
            (SELECT $indexFieldName FROM user WHERE $indexFieldName = :id)";
      }

      self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_SELECT_NAV_OWN . $tableName] .=
        " ORDER BY $indexFieldName, $orderByFieldName $orderByDirection";
      $str = self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_SELECT_NAV_OWN . $tableName];

      Log::debug("SQL PREPARE IS NOW READY: $str");

      self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_SELECT_ONE . $tableName]       =
            $selectBase . $whereBase . " IN (:searchID)";

      self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_DELETE . $tableName]          =
            $deleteBase . $whereBase . " = :searchID ";

      self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_INSERT . $tableName]          =
            $insertBase . "(" . Util::Array2csv(array_keys($dbLabels), $dbLabels) .
                          ") VALUES ($updateSetClause)";

      self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_UPDATE . $tableName]          =
            "$updateBase SET $updateSetClause WHERE $indexFieldName = :id";

      // ---- dependent and associative Collections ----
      // THERE WILL BE ONE QUERY PER UNIQUE Linked Collection
      //   THIS can be used to find fields in one table that hold value to another
      //   storageItems.ownerID = 1, etc ..

      if (!empty($dependentCollections)){

        foreach ($dependentCollections as $collectionName => $linkedCollectionItem) {
          $linkedClassName = NAME_SPACE . "\\" . $linkedCollectionItem["LinkedResourceName"];
          $instanceTemp = new $linkedClassName();
          //Create this in order to call Config, which is required in Static Constructor
          $linkedTableName = $linkedClassName::$TableName;
          $statement = "SELECT * FROM " . $linkedTableName  . " WHERE " . $linkedCollectionItem["LinkedFieldName"] . " IN (:searchID)";

          self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD . $tableName . $collectionName] = $statement;
        }
      }

      // -------------------------------------------
      // ASSOCIATIVE Lookup Tables
      //THERE WILL BE ONE QUERY PER UNIQUE Associative Collection
      if (!empty($associativeCollections)){
        foreach ($associativeCollections as $collectionName => $associativeCollectionItem) {
          $linkedTableName = '';
          $associativeKeyField    = $associativeCollectionItem["associativeKeyField"];
          $associateKeyFieldItems = explode(".", $associativeKeyField );
          $associativeTableName   = $associateKeyFieldItems[0];
          $associativeTablePrimaryFieldName = $associateKeyFieldItems[1];
          $associationObjects = $associativeCollectionItem["associationObjects"];

          foreach (array_keys($associationObjects) as $foreignResourceName) {
            $foreignClassName = NAME_SPACE . "\\" . $foreignResourceName;

            $associationObject                = $associationObjects[$foreignResourceName];
            $linkedKeyField                   = $associationObject["LinkedFieldName"];
            $linkedKeyFieldItems              = explode(".", $linkedKeyField);
            $associatedTableForeignFieldName  = $linkedKeyFieldItems[1];
            $foreignResourceTableName         = $foreignClassName::$TableName;
            $foreignResourceIndexFieldName    = $foreignClassName::$IndexFieldName;


            $additionalFields  = isset($associationObject["additionalFields"]) ?
                                    explode(",", $associationObject["additionalFields"]) : null;

            $ca = !is_null($additionalFields) ? count($additionalFields) : 0 ;

            //NEED A SELECT, INSERT, AND DELETE for ASSOCIATIVE  TABLES

            $statement = "SELECT * FROM $foreignResourceTableName"  .
                        " JOIN " . $associativeTableName . " ON " .
                        $linkedKeyField . " =  $foreignResourceTableName.$foreignResourceIndexFieldName WHERE $associativeKeyField IN (:searchID)";
            self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD . "$tableName.$collectionName.$foreignResourceName"] = $statement;


            $statement = "INSERT INTO $associativeTableName";
            $statement .= "($associativeTablePrimaryFieldName";
            $statement .= ",$associatedTableForeignFieldName";

            if (!is_null($additionalFields)){
              foreach ($additionalFields as $additionalFieldName) {
                $statement .= ",$additionalFieldName";
              }

            }
            $statement .= ") VALUES (?,?";

            for ($i=0; $i < $ca; $i++) {
              $statement .= ",?";
            }

            $statement .= ") ON DUPLICATE KEY UPDATE `$associativeTablePrimaryFieldName`=?";
            self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_INSERT . "$tableName.$collectionName.$foreignResourceName"] = $statement;

            $statement = "DELETE FROM $databaseName.$associativeTableName WHERE $associativeTablePrimaryFieldName=? AND $associatedTableForeignFieldName=?";
            self::$PreparedStatementStrings[Constants\SqlPrepareTypes::SQL_DELETE . "$tableName.$collectionName.$foreignResourceName"] = $statement;
          }
        }
      }
    }

    if (!self::GetInstance()->prepareCommonStatements()){
      Log::error("Error with preparing statements");
      die("========== 000000 ERROR WITH PREPARE statements for $tableName");
    } else {
        Log::debug("======= SUCCESS STATEMENT PREPARE = Statements prepared successfully for $tableName");
    }
}
  /*
    Prepare all common statements in DB, return immediately on failure
  */
  protected function prepareCommonStatements()
  {
    foreach (DataProvider::$PreparedStatementStrings as $statementName => $statementString) {
      //Now add these prepared statements to the DB INSTANCE
      if (!self::GetInstance()->prepareSingleStatement($statementName, $statementString)){
        Log::debug("ERROR WITH PREPARING $statementName !!!!");
        return false;
      }
    }
    return true;
  }
  /*
  *   Get a collection of records that have the dependency field matching this resource's id
  *
  *   There is already a prepared statement setup ready to accept the primaryID
  *      $resource->$tableName.$dependentResourceName = (select foreign where fieldname=primaryid)
  *
  *   @param - resource - the resource object with place-holders loaded for the dependency items

  *   @returns - array[$resourceName] = resources[ ]
  *
  */
  public static function GetDependentRecords($resource){
    $returnedDependencies = [];

    foreach ($resource->DependentResources as $dependencyName => $dependentResourceItem) {

          $preparedStatementValues  = array("searchID" => intval($resource->ID));
          $preparedStatementName    =
                Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD .
                get_class($resource)::$TableName .
                $dependencyName;

          $foreignResourceName  = $dependentResourceItem["LinkedResourceName"];
          $foreignResourceClassName = NAME_SPACE . "\\" . $foreignResourceName;
          // TODO: Way too much memory used, covert to use SQL where IN (select )
          $dependentResourceObjects = $foreignResourceClassName::GetInstancesUsingQuery(
                                                      $preparedStatementName,
                                                      $preparedStatementValues);

          if (!empty($dependentResourceObjects)){
            $num = count($dependentResourceObjects);
            Log::debug("Get Dependencies - DataProvider - Found $num $foreignResourceName items that depend on $resource->Name");
            $returnedDependencies[$dependencyName] = $dependentResourceObjects;
          }
          else {
            Log::debug("Get Dependencies - DataProvider - No dependent $dependencyName resources found for RESOURCE $resource->Name.
                        It will not be added to the collection of returned items.");
          }
      }
      return $returnedDependencies;
  }
  /*
    Get collection of associated resources
    @param: $resource - Resource (User, StorageFacility)
    @returns: array[associativecollectionName]["ForeignResources"]["associativeTableName"]
                                              ["associativeTablePrimaryFieldName"]
        [associationObjects] = {'displayConfigParams', Records[]}
  */
  public static function GetAssociatedRecords($resource){

    $returnedAssociations = [];
    foreach ($resource->Associations as $associativeCollectionName=>$associativeCollectionItem)
    {
        $preparedStatementValues = array("searchID"=> intval($resource->ID));

        //Pull out the keyfield, will need it to build the disassociate url
        $associativeKeyField              = $associativeCollectionItem["associativeKeyField"];
        $associateKeyFieldItems           = explode(".", $associativeKeyField );
        $associativeTableName             = $associateKeyFieldItems[0];
        $associativeTablePrimaryFieldName = $associateKeyFieldItems[1];

        $returnedAssociations[$associativeCollectionName]["associativeTableName"]             = $associativeTableName;
        $returnedAssociations[$associativeCollectionName]["associativeTablePrimaryFieldName"] = $associativeTablePrimaryFieldName;

        $associationObjects = $associativeCollectionItem["associationObjects"];

        $foreignResources = [];
        foreach (array_keys($associationObjects) as $associationObjectKey) {

          $associationObject    = $associationObjects[$associationObjectKey];
          $foreignResourceName  = $associationObjectKey;
          $linkedItems          = explode(".",$associationObject["LinkedFieldName"]);
          $linkedfieldName      = $linkedItems[1];
          $foreignResourceClassName = NAME_SPACE . "\\" . $foreignResourceName;
          $foreignResource      = new $foreignResourceClassName();
          $listSize             = $associationObject["ListSize"];
          $foreignResourceLabel = $associationObject["displayText"];

          $preparedStatementName = Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD .  $resource::$TableName . ".$associativeCollectionName.$foreignResourceName";

          $linkedObjects = $foreignResource::GetInstancesUsingQuery($preparedStatementName, $preparedStatementValues);

          $foreignResources[$associationObjectKey] =
                  array('ForeignResourceLabel'  => $foreignResourceLabel,
                        'LinkedFieldName'       => $linkedfieldName,
                        'ListSize'              => $listSize,
                        'LinkedResources'         => $linkedObjects
                        );
        }
        Log::debug("DataProvider - Adding data for  $associativeCollectionName");


        $returnedAssociations[$associativeCollectionName]["ForeignResources"] = $foreignResources;
    }

    return $returnedAssociations;

  }
  /*
    Set User hashed password into DB
  */
  public static function ChangePassword($resource, $password_raw){

    $hashed_passwd = password_hash($password_raw);
    $sql = "UPDATE $resource->$TableName SET ('upasswd') VALUES (?) WHERE id=" . $resource->ID;

  }
  /*
    Determine if a $userID is in adminusers table
    @param: $userID
    @returns: bool
  */
  public static function IsUserAdmin($userID){
    return self::GetInstance()->_isUserAdmin($userID);
  }
  /*
  Add a new user
  @returns:  array(newUserID/0; errMsg/empty)
  */
  public static function ADD_USER($emailaddress, $username, $password_raw, $firstname,
                                  $lastname, $city, $state, $zip)
  {

    $resource = new User();//This is only needed to get a flexible $TableName
    return self::GetInstance()->_adduser( $resource, $emailaddress, $username,
                                          $password_raw, $firstname,
                                          $lastname, $city, $state, $zip);

  }
  /*
    Verify hashed password is correct

    @returns: BOOL
  */
  public static function AUTHENTICATE($uname, $rawpassword){

    //User resource has a configurable tablename, this is why resource is created
    $resource = new User();

    return self::GetInstance()->_authenticate($resource, $uname, $rawpassword);
  }
  /*
    Authenticate a user and set a temporary authCode in DB
     - LOGIN STEP 1 - This is the first part of a login flow
                        towards grant of an access token
    @returns: array[userid,authCode] or NULL
  */
  public static function LOGIN($uname, $rawpassword){

    $userID = DataProvider::AUTHENTICATE($uname, $rawpassword);
    Log::debug("Trying to auth user [$uname] - userid found was [$userID]");
    if ($userID){

      $timeoutSecs = ConfigurationManager::GetParameter("Sessioning")->SessionTimeoutSecs;
      $authCode = self::GetInstance()->_setAuthorizationCode($userID, $timeoutSecs);

      if (!is_null($authCode)){
        return array($userID, $authCode);
      }
    }
    return null;
  }
  /*
    Generate a short-lived auth-code, insert into DB 'authcodes' with short expiry
    @param: $expiryInSeconds - Number of seconds before expiring this auth-code
  */
  public static function GET_AUTH_CODE($expiryInSeconds){

    return self::GetInstance()->_setAuthorizationCode(null, $expiryInSeconds);

  }
  /*

    Get a Security Access Token using a recently generated AuthCode
    It will exist in DB.  After verification, remove authCode from DB
     - LOGIN STEP 2 -- This is normally second part of a login flow that is detected by
        the router for /Login/
    @param: $userID - Required userID
    @param: $authCode - The AuthCode
    @param: $expiryInSeconds - Number of seconds from now to expire this access token
    @return: array[$accessToken, $expires_unix_time]
  */
  public static function SET_ACCESS_TOKEN($userID, $authCode, $expiryInSeconds){
    return self::GetInstance()->_setAccessToken($userID, $authCode, $expiryInSeconds);
  }
  /*
    Verify that a user exists
    @param: $emailAddress - REQUIRED
    @param: $profileName - optional
    @param: $authCode - optional

    @returns: $userID
  */
  public static function VERIFY_USER_EXISTS($emailAddress, $profileName=null, $authCode=null ){
        return self::GetInstance()->_verify_user_exists($emailAddress, $profileName, $authCode);
  }
  /*
    Using only an authCode that should exist in DB, reset a user password

    @param: authCode - authorizationCode previously granted
    @param: password_raw - entered on form
    @param: $bypassAuth = Do not check the AccessToken, assume valid
    @returns: success or failure
  */
  public static function RESET_PASSWORD($userID, $accessToken, $password_raw, $bypassAuth=false){

    $error = true;
    $accessGranted = $bypassAuth ? true : self::GetInstance()->_validateAccessToken($accessToken, $userID);

    if ($accessGranted){
      $error = self::GetInstance()->_resetPassword($userID, $password_raw);
    } else {
      Log::error("SECURITY - Access denied for '$userID' and token '$accessToken' ");
    }

    return $error;
  }
  /*
  * Remove all authorizations for userID
  * @param: $userID
  */
  public static function DELETE_AUTHORIZATIONS($userID=null){

    if (is_null($userID)){
      $userID = SessionManager::GetCurrentUserID();
    }
    if (!self::GetInstance()->_deleteAuthorizations($userID)){
      Log::warning("ERROR while deleting authorizations for user $userID");
    }
  }
  /*
    Return true/false if authCode is not expired

    @param: $authCode
    @param: $userID - optional,
                must match return value from DB matching this authcode
  */
  public static function AUTH_VALIDATE($authCode, $userID=null){

    $authorizedUserID = self::GetInstance()->_validateAuthCode($authCode);

    //SUCCESS IF:
    //   NOT EXPIRED AND userID matches if it was requested
    return $authorizedUserID >= 0 &&
           (
             is_null($userID) ||
             (!is_null($userID) && ($authorizedUserID===$userID))
           );
  }
  /*
    Return true/false if accessToken is valid
    @param: $extendSeconds - optional
  */
  public static function ACCESS_TOKEN_VALIDATE($accessToken, $userID, $extendSeconds=0){
    return self::GetInstance()->_validateAccessToken($accessToken, $userID, $extendSeconds);
  }
  public static function GetUserID($accessToken){

    $sql = "SELECT `userid` FROM `authorization` WHERE `accesstoken`='$accessToken' ";

    $row = self::GenericGET($sql);

    return ($row===null) ? 0 : $row[0]['userid'];
  }

  /*
  * Given an accessToken, get additional data:
  * returns: array[userID, expires_unix_time]
  */
  public static function GET_ACCESS_TOKEN_DATA($accessToken){
    return is_null($accessToken) ? null :
                      self::GetInstance()->_getAccessTokenData($accessToken);
  }
  /*
    ASSOCIATE two records into an associate table
      (example:  storagefacilityowners:{faciltyid, userid})

    @param: $resource - The record that has the association
    @param: $associativeCollectionName - string name of the collection
    @param: $fieldData - Data used in the association such as the fieldName and of the other object
              (example.  Key-value pair used in the association:
              array("palletid"=>88) would be used to associate a pallet to bin
              /Storagebin/
    @returns: $resource->ID
  */
  public static function ASSOCIATE($resource, $associativeCollectionName, $foreignResourceName, $fieldData)
  {
    $preparedStatementName = Constants\SqlPrepareTypes::SQL_INSERT .
                              $resource::$TableName .
                              ".$associativeCollectionName.$foreignResourceName";

    //Build the data object that will be passed into the prepared statement
    /*
      { Resource.ID, ForeignResource.ID }
    */
    $statementData = array($resource->ID);

    Log::debug("ASSOCIATE fieldData");
    foreach ($fieldData as $d) {
      Log::debug("- [$d]");
      $statementData[] = $d;
    }
    Log::debug("Added key again for ASSOCIATE ON DUPLICATE SUPPORT");
    $statementData[] = $resource->ID;
    $c = count($statementData);

    Log::debug("Prepared Statement (using [$c] params) :");
    foreach ($statementData as $d) {
      Log::debug("++ [$d]");
    }

    try {
      $result = self::GetInstance()->_sqlExecuteStatement($preparedStatementName, $statementData);
      return !is_null($result) ? $resource->ID : 0;
    } catch (\Exception $e) {
        Log::error("Database error with associate " . $e->getMessage());
        return 0;
    }
  }

  /*
  *  DISASSOCIATE two records into an associate table
  *
  *   (example:  storagefacilityowners:{faciltyid, userid})
  *
  *  @param: $resource - The record that has the association
  *  @param: $associativeCollectionName - string name of the collection
  *  @param: $foreignResourceName - classname of the other resource (i.e. user in facilityowners)
  *  @param: $fieldData - Key-value pair used in the disassociation such as the fieldName and of the other object
  *            (example.  array("palletid"=>88) would be field Data needed for disassociate
  */
  public static function DISASSOCIATE($resource, $associativeCollectionName, $foreignResourceName, $fieldData)
  {
    $preparedStatementName = Constants\SqlPrepareTypes::SQL_DELETE .
                              $resource::$TableName .
                              ".$associativeCollectionName.$foreignResourceName";

    //Now add the data that will be passed into the prepared statement
    /*
      { Resource.ID, ForeignResource.ID }
    */

    $statementData = array($resource->ID);
    $statementData[] = strval(array_values($fieldData)[0]);


    try {
      $result = self::GetInstance()->_sqlExecuteStatement($preparedStatementName, $statementData);
      return !is_null($result) ? $resource->ID : 0;

    } catch (\Exception $e) {
      return 0;//indicate error
    }

  }
  /*
  * Placeholder - request an association, such as during signup: user->provider
  *
  */
  public static function AddAssociationRequest(  $userID,
                                        $primaryType,
                                        $primaryRecordID,
                                        $foreignType,
                                        $foreignRecordID)
  {

    return 1;

  }
  /*
  Get records
  @param $recordIDs
  @param $recordIDs
  @returns associate array $row results
  */
  public static function LOAD($preparedStatementName, $dbLabels, $recordID){
    Log::debug("Loading records for " . self::$DBType);
    return self::GetInstance()->loadRecord($preparedStatementName, $dbLabels, $recordID);
  }
  /*
  * -----------------
  * CRUD FUNCTIONS
  *
  */
  public static function INSERT($resource, $fieldData){
    return self::GetInstance()->insertrecord($resource, $fieldData);
  }
  public static function GET($preparedStatementName, $recordIDs){
    return self::GetInstance()->_sql_GetRecords($preparedStatementName, $recordIDs);
  }
  public static function UPDATE($resource, $fieldData){
    return self::GetInstance()->updaterecord($resource, $fieldData);
  }
  public static function DELETE($resource, $fieldData){
    return self::GetInstance()->deleterecord($resource, $fieldData);
  }

  public static function GetRecordCount($tableName, $indexFieldName, $whereArray=null){
    $sql = "SELECT COUNT($indexFieldName) FROM $tableName";

    if (!is_null($whereArray)){

      foreach ($whereArray as $key => $value) {
        $sql .= " WHERE $key=$value AND";
      }
      $sql = substr($sql, 0, -3);
    }

    return self::GetInstance()->_genericSQL_Get($sql);
  }
  /*
  *  Get nv pair records - ID, string
  *
  *  Field pulled is from resourceConfig.json, and may be multiple fields
  *    to support such things as Lastname, Firstname
  *       //example: "$tableName.firstname, ' ', users.lastname)';
  *
  * @param string indexFieldName - Name of database field to use as record select
  * @param string displayFieldName - Name of database field to use as record display, could be more than one
  * @param int $minID - Used as min in SQL BETWEEN min AND max
  * @param int $maxID - Used as min in SQL BETWEEN min AND max
  */

  public static function GetRecordsForSelect($tableName, $indexFieldName, $displayFieldNames, $minID, $maxID){

    if (count($displayFieldNames)>1){
      $displayFieldStr = "CONCAT(";
      foreach ($displayFieldNames as $fieldName) {
        $displayFieldStr .= "$tableName.$fieldName" . ", ' ',";
      }
      // TODO: utility function
      $displayFieldStr = substr($displayFieldStr, 0, -6);//take off the last , ' ',
      $displayFieldStr .= ")";
    } else {
          $displayFieldStr = $displayFieldNames[0];
    }
    $sql = "SELECT $indexFieldName, $displayFieldStr FROM $tableName WHERE $indexFieldName";

    if ( !is_null($minID) && !is_null($maxID)){
       $sql .= " BETWEEN $minID AND $maxID";
    }

    Log::debug($sql);
    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function FindRecordsLike($tableName, $indexFieldName, $searchFieldName, $searchString){

    if (count($searchFieldName)>1){
      $displayFieldStr = "CONCAT("; //"$tableName.firstname, ' ', users.lastname)';
      foreach ($searchFieldName as $fieldName) {
        $displayFieldStr .= "$tableName.$fieldName" . ", ' ',";
      }
      // TODO: utility function
      $displayFieldStr = substr($displayFieldStr, 0, -6);//take off the last , ' ',
      $displayFieldStr .= ") AS value";
    } else {
          $displayFieldStr = $searchFieldName[0] . ' AS value';
    }
    $searchFieldName = $searchFieldName[0];

    $sql = "SELECT $indexFieldName as id, $displayFieldStr FROM $tableName WHERE $searchFieldName LIKE('$searchString%')";

    return self::GetInstance()->_genericSQL_Get($sql);
  }

  public function loadRecord($preparedStatementName, $dbLabels, $recordID)
  {

    $result = self::GetInstance()->_sql_GetRecord($preparedStatementName, $dbLabels, $recordID);

    if(isset($result) && count($result) > 0){
          return $result[0];//Only return the first record
    } else {
        Log::error("No record for for ID " . $recordID);
        return null;
    }
  }
  /*
  *  Get one or more records WHERE ID in ($IDs) )
  */
  protected function _sql_GetRecord($preparedStatementName,$dbLabels, $ID)
  {
    $result = self::GetInstance()->_sqlExecuteStatement($preparedStatementName, array("searchID"=>$ID));
    return !is_null($result) ? $result->fetchall() : null;
  }
  /* _sql_GetRecordWhere -
  @param: $recordsToGet = int[] array of records to get
  */
  protected function _sql_GetRecords($preparedStatementName, $recordIDs)
  {
    if (count($recordIDs)>1){
      error_log("more than one one");
    }
    $result = self::GetInstance()->_sqlExecuteStatement($preparedStatementName, $recordIDs);

    return !is_null($result) ? $result->fetchall() : null;
  }

  public static function IsProvider($userID){
    $providers = self::GetAccessibleRecords('Provider', $userID);
    return !empty($providers);
  }
  public static function IsEmployee($userID){
    $sql = "SELECT userid FROM storagefacilityworkers WHERE userid=$userID";
    $employee = self::GetInstance()->_genericSQL_Get($sql);
    return !empty($employee);
  }
  public static function IsClient($userID){
    $sql = "SELECT userid FROM client WHERE userid=$userID";
    $clients = self::GetInstance()->_genericSQL_Get($sql);
    return !empty($clients);
  }
  public static function GetResourceIDByName($resourceName, $nameField, $nameValue){
    $tableName = ConfigurationManager::GetTableName($resourceName);
    $sql = "SELECT id FROM $tableName WHERE $nameField LIKE('%$nameValue%')";

    $result =  self::GetInstance()->_genericSQL_Get($sql);

    if ($result){
      return $result[0]['id'];
    } else {
      return null;
    }
  }
  /*
  * return: rows[{fieldName}] - DB records that can be assigned for an assocition for this user
  *
  */
  public static function GetAssignableResources($associativeCollectionName, $userID){

    switch($associativeCollectionName){

      case 'clients':

        $sql = "SELECT id, CONCAT(firstname, ' ', lastname) as name
                FROM user WHERE id
                NOT IN (SELECT userid FROM client
                  WHERE providerid IN (SELECT id FROM provider WHERE provider.ownerid=$userID ) OR
                  providerid IN (SELECT providerid FROM providerowners WHERE userid=$userID)
                ) AND user.id!=$userID";
        break;
      case 'facilityworkers':
        $sql = "SELECT id, CONCAT(firstname, ' ', lastname) as name
                FROM user WHERE id
                NOT IN (SELECT userid FROM storagefacilityworkers
                  WHERE providerid IN (SELECT id FROM provider WHERE provider.ownerid=$userID ) OR
                  providerid IN (SELECT providerid FROM providerowners WHERE userid=$userID)
                ) AND user.id!=$userID";

        break;
      case 'providerowners':
        $sql = "SELECT id, CONCAT(firstname, ' ', lastname) as name
                FROM user WHERE id
                NOT IN (SELECT userid FROM providerowners
                  WHERE providerid IN (SELECT id FROM provider WHERE provider.ownerid=$userID ) OR
                  providerid IN (SELECT providerid FROM providerowners WHERE userid=$userID)
                ) AND user.id!=$userID";

        break;
      case 'binitems':
        $sql = "SELECT storagepallet.id, storagepallet.name
                  FROM storagepallet LEFT JOIN storagepalletinventory inventory
                  ON storagepallet.id = inventory.palletid
                  WHERE storagepallet.providerid IN
                  (SELECT providerowners.providerid FROM providerowners
                  WHERE providerowners.userid=$userID UNION
                  SELECT id FROM provider WHERE provider.ownerid=$userID
                )  AND inventory.palletid IS NULL";
        break;
      case 'palletinventory':
        $sql = "SELECT storagepallet.id, storagepallet.name
                  FROM storagepallet LEFT JOIN storagepalletinventory inventory
                  ON storagepallet.id = inventory.palletid
                  WHERE storagepallet.providerid IN
                      (SELECT providerowners.providerid FROM providerowners
                        WHERE providerowners.providerid IN (SELECT id FROM provider WHERE provider.ownerid=$userID )
                        OR providerowners.providerid IN (SELECT providerowners.providerid FROM providerowners WHERE providerowners.userid=$userID)
                      ) AND inventory.palletid IS NULL";
        break;
      case 'Storagepallet':
        $sql = "SELECT storagepallet.id, storagepallet.name
                  FROM storagepallet LEFT JOIN storagepalletinventory inventory
                  ON storagepallet.id = inventory.palletid
                  WHERE storagepallet.providerid IN
                  (SELECT providerowners.providerid FROM providerowners
                  WHERE providerowners.userid=$userID UNION
                  SELECT id FROM provider WHERE provider.ownerid=$userID
                )  AND inventory.palletid IS NULL";
        break;
      case 'receivers':
        $sql = "SELECT id, CONCAT(firstname, ' ', lastname) as name
                FROM user WHERE id
                NOT IN (SELECT clientid FROM receiver) AND user.id!=$userID";

        break;

      default:
        break;
    }

    if (isset($sql) && !empty($sql)){
      return self::GetInstance()->_genericSQL_Get($sql);
    } else {
      Log::error("Empty sql for associativeCollection = [$associativeCollectionName]");
      return null;
    }
  }
  public static function GetAccessibleRecords($resourceName, $userID, $isAdmin=null){

    $records = null;
    Log::debug("GETTING accessible records for $resourceName using userID [$userID]");

    if (is_null($isAdmin)){
      $isAdmin = self::IsUserAdmin($userID);
    }

    if ($isAdmin){
      $records = DataProvider::GetAllRecordsForNav($resourceName);
    } else {

      $userIDs = array($userID);
      //Needed for the duplicate SELECT IN associatedownersTable
      if (ConfigurationManager::IsResourceCoOwned($resourceName)){
        $userIDs[] = $userID;
      }
      $records = self::GetInstance()->_getAccessibleRecordsForResource($resourceName, $userIDs);
    }
    $c = is_null($records) ? 0 : count($records);
    Log::debug("ACCESSIBLE COUNT for $resourceName IS $c");

    return $records;
  }
  /*
  *   Given a resource, get all IDs owned by $userID
  *   @returns: array('id', 'name')
  */
  public static function GetOwnedRecordsForResource($resourceTableName,
                                                      $ownerFieldName,
                                                      $userID, $fieldSQLForDisplay = "name"){

    return self::GetInstance()->_getOwnedRecordsForResource($resourceTableName,
                                                            $ownerFieldName,
                                                            $fieldSQLForDisplay,
                                                            $userID);
  }


  public static function GetAllRecordsForNav($resourceName){

    $fqresourceName = NAME_SPACE . "\\" . $resourceName;
    $tableName        = $fqresourceName::$TableName;
    $displayFieldStr  = $fqresourceName::GetDisplayFieldsCSV($resourceName);
    $indexFieldName   = $fqresourceName::$IndexFieldName;
    $OrderByFieldName = $fqresourceName::$OrderByFieldName;
    $OrderByDirection = $fqresourceName::$OrderByDirection;

    $sql = "SELECT $indexFieldName, $displayFieldStr FROM $tableName ORDER BY $OrderByFieldName $OrderByDirection LIMIT " . static::$MaxSqlLimits;

    return self::GetInstance()->_genericSQL_Get($sql);
  }

  /*
  *   Get list of items currently in storage for a user
  *
  * @param: $userID - the userID
  * @returns: array[{itemID}] = "name", "qty"
  */
  public static function GetItemsInStorage($userID, $itemID=null, $isProvider=false){
    return self::GetInstance()->_getItemsInStorage($userID, $itemID, false, $isProvider);
  }
  public static function GetLotInventory($userID, $lotnumbers=null){
    return self::GetInstance()->_getLotsInStorage($userID, $lotnumbers);
  }
  public static function GetClientInventory($clientID){
    return self::GetItemsInStorage($clientID);
  }
  public static function GetLotsForStorageItem($storageItemID){
    $sql = "SELECT lotnumber, item_qty FROM storagepalletinventory WHERE storagepalletinventory.itemid = $storageItemID";
    return self::GetInstance()->_genericSQL_Get($sql);
  }

  /*
  *   Get list of items currently approved but pending SHIP
  *
  * @param: $userID - the userID
  * @returns: array[itemID] = "name", "qty"
  */
  public static function GetItemsPendingShipment($userID){
    $result = self::GetInstance()->_getItemsPending($userID, 'ship');
    return $result;
  }
  /*
  *   Get list of items currently approved but pending STORE
  *
  * @param: $userID - the userID
  * @returns: array[itemID] = "name", "qty"
  */
  public static function GetItemsPendingStorage($userID){
    $result = self::GetInstance()->_getItemsPending($userID, 'store');
    return $result;
  }
  public static function GetItemHistory($userID, $type=null, $dateFilter){
    return self::GetInstance()->_getItemHistory($userID, $type, $dateFilter);
  }
  public static function GetEmployeeHistory($userID, $type=null, $dateTimeFilter){
    return self::GetInstance()->_getEmployeeWorkItemHistory($userID, $type, $dateTimeFilter);
  }
  public static function GetPalletByName($palletName, $ownerID=null){

    return self::GetInstance()->_getPalletByName($palletName, $ownerID=null);

  }

  /*
  * Get all users which are listed as clients of this user (who must be a provider)
  * If user is NOT a provider, then they must be an employee of a provider
  */
  public static function GetClients($userID, $includeOnlyWithTransactions=null){

    $clients = self::GetInstance()->_getClients($userID);

    //Add a calculated qty before returning
    foreach ($clients as $clientID => $client) {

      $itemsInStorage = self::GetInstance()->_getItemsInStorage($clientID);
      $total = 0;
      $storageItemsExist = is_array($itemsInStorage) && count($itemsInStorage)>0;
      if ($storageItemsExist){
        foreach ($itemsInStorage as $item) {
          $total += $item["qty"];
        }
      }
      $clients[$clientID]["qty"] = $total;
    }
    return $clients;
  }
  public static function GetReceivers($userID, $navOnly=false){
    return self::GetInstance()->_getReceivers($userID, $navOnly);
  }
  public static function GetClientsForProvider($providerID){
    return self::GetInstance()->_getClientsForProvider($providerID);
  }
  public static function GetProvidersForEmployee($workerID){
    $providerIDs = self::GetInstance()->_getProvidersForEmployee($workerID);
    return $providerIDs;
  }
  public static function GetFacilitiesForEmployee($workerID){
    $facilities = self::GetInstance()->_getFacilitiesForEmployee($workerID);
    return $facilities;
  }
  public static function GetProviderForClient($clientID){
    return self::GetInstance()->_getProviderForClient($clientID);
  }
  public static function GetProviderName($clientID){
    return self::GetInstance()->_getProviderNameForClient($clientID);
  }
  public static function GetEmployees($userID=null){
    return self::GetInstance()->_getEmployees($userID);
  }
  public static function GetEmployeeNames($userID){

    $providers = self::GetInstance()->_getEmployees($userID);
    $returnData = array();

    foreach ($providers as $employees) {
      foreach ($employees as $employeeID => $employee) {
          $name = $employee['name'];
          $returnData[$employeeID] = array('id'=> $employeeID, 'name'=>$name);
      }
    }
    return $returnData;
  }

  public static function GetPendingItems($clientIDs, $type, $state=null, $requiredEmployeeID=null, $excludeEmployeeID=null){
    $returnItems = array();

    if (empty($clientIDs)){
      return $returnItems;
    }
    // TODO: bad sql design it is calling for every requested client
    Log::debug("GETTING PENDING ITEMS: type[$type] state[$state] requiredEmployeeID [$requiredEmployeeID] excludeEmployeeID [$excludeEmployeeID]");

    foreach ($clientIDs as $clientID) {

      $pendingItems =  self::GetInstance()->_getItemsPending($clientID, $type, $state, $requiredEmployeeID, $excludeEmployeeID);
      if (!empty($pendingItems)){
        $returnItems[$clientID] = $pendingItems;
      }
    }

    return $returnItems;
  }

  public static function GetUnapprovedStorageRequests($clientIDs){
    return self::GetPendingItems($clientIDs, 'store', 'approved');
  }
  public static function GetUnapprovedShipmentRequests($clientIDs){
    return self::GetPendingItems($clientIDs, 'ship', 'approved');
  }
  public static function GetUnfufilledStorageRequests($clientIDs){
    $pendingItems = [];
    $excludeEmployeeID = null;

    if (SessionManager::IsEmployee()){
      $excludeEmployeeID = SessionManager::GetCurrentUserID();
    }

    $pendingItems = self::GetPendingItems($clientIDs, 'store', null, null, $excludeEmployeeID);

    return $pendingItems;
  }
  public static function GetUnfufilledShipmentRequests($clientIDs){
    $pendingItems = [];
    $excludeEmployeeID = null;

    if (SessionManager::IsEmployee()){
      $excludeEmployeeID = SessionManager::GetCurrentUserID();
    }

    return self::GetPendingItems($clientIDs, 'ship', null, null, $excludeEmployeeID);
  }
  public static function GetStorageDetail($storageid,$palletid=null,$lotnumber=null, $tag=null){

    $storageDetail = array();

    $storageDetail = self::GetInstance()->_getStorageDetail($storageid,$palletid,$lotnumber, $tag);

    return $storageDetail;
  }
  public static function GetProductStorageDetail($storageitemid){

    $productStorageDetail = array();

    $productStorageDetail = self::GetInstance()->_getProductStorageDetail($storageitemid);

    return $productStorageDetail;
  }
  public static function GetStorageRequestInventory($storageid){

    $storageInventory = array();

    $storageInventory = self::getInstance()->_getStorageRequestInventory($storageid);

    return $storageInventory;

  }
  public static function GetPalletInventory($palletIDs, $userID, $lotnumber=null, $tag=null, $includeEmpty=false, $confirmedStorage=null ){

    $palletInventory = array();
    $palletInventory = self::GetInstance()->_getPalletInventory($palletIDs, $userID, $lotnumber, $tag, $includeEmpty, $confirmedStorage);
    return $palletInventory;
  }

  public static function GetEmptyPallets($providerIDsArray){
    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT storagepallet.id as palletid FROM storagepallet
                  INNER JOIN storagepalletinventory
                  ON storagepalletinventory.palletid=storagepallet.id";
    $sql .= " WHERE storagepallet.id IN (SELECT id FROM storagepallet WHERE storagepallet.providerid IN ($providerIDs))";

    $sql .= " AND storagepalletinventory.item_qty=0";

    return self::GetInstance()->_genericSQL_Get($sql);

  }
  public static function GetNeverUsedPallets($providerIDsArray){

    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT storagepallet.id as palletid FROM storagepallet

    WHERE storagepallet.id IN (SELECT id FROM storagepallet
      WHERE storagepallet.providerid IN ($providerIDs))

        AND storagepallet.id NOT IN (
      SELECT storagepallet.id FROM storagepallet
                  INNER JOIN storagepalletinventory
                  ON storagepalletinventory.palletid=storagepallet.id";
    $sql .= " )";

    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetFullPallets($providerIDsArray){
    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT * FROM storagepallet WHERE storagepallet.providerid IN ($providerIDs) AND storagepallet.full=1";
    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetLoadedPallets($providerIDsArray){
    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT storagepallet.id as palletid FROM storagepallet
                  INNER JOIN storagepalletinventory
                  ON storagepalletinventory.palletid=storagepallet.id";
    $sql .= " WHERE storagepallet.id IN (SELECT id FROM storagepallet WHERE storagepallet.providerid IN ($providerIDs))";

     $sql .= " AND storagepalletinventory.item_qty>0";
    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetOwnedBins($providerIDsArray){

    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT * FROM storagebin WHERE storagebin.providerid IN ($providerIDs)";

    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetBinIDByName($name){

    $binID = 0;

    if (!empty($name)){
      $sql = "SELECT storagebin.id FROM storagebin WHERE name ='$name'";

      $record = self::GetInstance()->_genericSQL_Get($sql);

      if (!empty($record)){
        $binID = $record[0]['id'];
      }
    }

    return $binID;
  }
  public static function GetBinsLoaded($providerIDsArray, $includeFull=true){

    if (empty($providerIDsArray)){
      return null;
    }
    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT DISTINCT storagebininventory.binid FROM storagebininventory ";
    $sql .= "WHERE storagebininventory.binid IN ";
    $sql .= "(SELECT id FROM storagebin WHERE storagebin.providerid IN ($providerIDs)";

    if (!$includeFull){
     $sql .= " AND storagebin.full=0";
    }
    $sql .= ")";
    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetBinsEmpty($providerIDsArray){

    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT DISTINCT t1.id FROM storagebin t1
            LEFT JOIN storagebininventory t2 ON t2.binid=t1.id
            WHERE t2.binid IS NULL AND t1.providerid IN ($providerIDs)";

    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetBinsFull($providerIDsArray){
    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT id FROM storagebin WHERE storagebin.providerid IN ($providerIDs)
      AND storagebin.full=1";

    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetBinInventory($userID, $binIDs=null){
    $binInventory = array();
    $binInventory = self::GetInstance()->_getBinInventory($userID, $binIDs);
    return $binInventory;
  }
  public static function GetBinInventoryByName($userID, $name){

    $binInventory = array();
    $sql = "SELECT id FROM storagebin WHERE name='$name'";
    $bin = self::GetInstance()->_genericSQL_Get($sql);

    if (!empty($bin)){
      $binID = $bin[0]["id"];
      $binInventory = self::GetBinInventory($userID, [$binID]);
    }
    return $binInventory;
  }

  public static function GetPalletInventoryByName($name, $userID, $lotnumber=null, $tag=null, $includeEmpty=true, $confirmedStorage=1){

    $palletInventory = array();
    $sql = "SELECT id FROM storagepallet WHERE name='$name'";
    $pallet = self::GetInstance()->_genericSQL_Get($sql);

    if (!empty($pallet)){
      $palletID = $pallet[0]["id"];
      $palletInventory = self::GetPalletInventory([$palletID], $userID, $lotnumber, $tag, $includeEmpty, $confirmedStorage);
    }

    return $palletInventory;
  }
  public static function GetOwnedPallets($providerIDsArray){

    $providerIDs = Util::Array2csv($providerIDsArray, ["ID"]);
    $sql = "SELECT * FROM storagepallet WHERE storagepallet.providerid IN ($providerIDs)";

    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function GetPalletsContainingItem($itemID, $lotnumber=null, $tag=null, $includeEmpty=false){

    $palletsContainingItem = [];
    try {
      $rowData = self::GetInstance()->_getPalletsContainingItem($itemID, $lotnumber, $tag, $includeEmpty);
    } catch (\Exception $e) {
      Log::error($e->getMessage());
    }

    //combine duplicates, adding qtys
    foreach ($rowData as $row) {
      $palletID = $row['palletid'];
      if (isset($palletsContainingItem[$palletID])){
        $palletsContainingItem[$palletID]['qty'] += $row[$palletID]['qty'];
      } else {
        $palletsContainingItem[$palletID] = $row;
      }
    }
    return $palletsContainingItem;
  }
  public static function GetPalletsAvailableForStorage($providerid, $includeFull=false, $itemid=null, $lotnumber=null, $onlyShowEmpty=true){

    $palletsAvailable = [];
    try {
      $rowData = self::GetInstance()->_getPalletsAvailableForStorage($providerid, $includeFull, $itemid, $lotnumber, $onlyShowEmpty);
    } catch (\Exception $e) {
      Log::error($e->getMessage());
    }

    //combine duplicates, adding qtys
    foreach ($rowData as $row) {

      $palletID   = $row['palletid'];
      $palletName = $row['palletname'];
      $itemID     = $row['itemid'];
      $qty        = $row['qty'];
      $itemName   = $row['itemname'];

      if (!isset($palletsAvailable[$palletName])){
        $palletsAvailable[$palletName] = array();
      }
      if (!isset($palletsAvailable[$palletID][$itemID])){
        $palletsAvailable[$palletName][$itemID] = array();
        $palletsAvailable[$palletName][$itemID] = $row;
      } else {
        $palletsAvailable[$palletName][$itemID]['qty'] += $qty;
      }
    }
    return $palletsAvailable;
  }
  public static function GenericGET($sql){
    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function Approve($resource, $userID){
      return self::GetInstance()->_approve($resource, $userID);
  }
  /*
  * Attach a shipment request to one or more pallet inventory entries
  */
  public static function AssignPalletForPull($shipmentID, $palletIDs){
    return self::GetInstance()->_assignPalletForPull($shipmentID, $palletIDs);
  }
  public static function Claim($resource, $userID, $fieldSuffix){
      return self::GetInstance()->_claim($resource, $userID, $fieldSuffix);
  }
  /*
  *  Store a storage onto a pallet
  *
  *  - First, update the palletinventory item qty.  Remember that there is already
  *      an inventory item when the provider assigned it
  */
  public static function Store($storageID, $stockerID, $palletID, $qty, $lotnumber,$itemID, $tag){

      $success = false;
      //first update the pallet qty and confirmed flag for this pallet
      if (self::GetInstance()->_addPalletStorageItemQty($palletID, $storageID, $qty, $lotnumber, $itemID, $tag)){
         Log::info("=====STORAGE ITEM [$storageID] STORED : [$qty] items onto pallet [$palletID]");
         $success = true;
      } else {
        Log::error("ERROR while trying to Update Qty $qty for storage request [$storageID] on pallet [$palletID] for [$stockerID]");
      }
      return $success;
  }
  /*
  * Update the timestamp and stockerID, which indicates this shipment is fulfilled
  * @returns: success/fail
  */
  public static function CloseStorage($storageID, $stockerID, $notes=""){

     $success = false;

     // storage request to include the final qty and stocker
     if (self::GetInstance()->_closeStorage($storageID, $stockerID, $notes)){
        Log::info("=====STORAGE REQUEST [$storageID] COMPLETED :  stocker[$stockerID] notes[$notes] date_stored updated ");
        $success = true;
     } else {
       Log::error("ERROR while trying to STORE $qty [$storageID] item for user [$stockerID]  SQL:  [$sqlUpdate]");
     }

     return $success;
  }
  /*
  *  Pull QTY from a pallet's storage request item
  *   If no lotnumber/tagnumber specified, qty will be pulled from any storageid on this pallet
  *    that has been flagged with this shipment_request_id
  *
  *  @returns: success/fail
  */
  public static function Pull($shippingRequestID, $stockerID, $palletID, $qty, $lotnumber=null, $tag=null){

    $success = false;
    //First update the pallet QTY, qty will be negative if shipping
    if (self::GetInstance()->_pullItemsFromPallet($palletID, $qty, $shippingRequestID, $lotnumber, $tag)){
      Log::info("$qty ITEMs [$shippingRequestID] PULLED from pallet $palletID by userID=[$stockerID]");
      $success = true;
    } else {
     Log::error("ERROR while trying to PULL $qty items for shipping request [$shippingRequestID] on pallet [$palletID] for [$stockerID]");
    }
    return $success;
  }
  /*
  * Update the timestamp and stockerID, which indicates this shipment is fulfilled
  * @returns: success/fail
  */
  public static function CloseShipment($shipmentResource){

     $success = false;
     $stockerID = SessionManager::GetCurrentUserID();
     $success = self::GetInstance()->_shipSetAsShipped($shipmentResource);
     if ($success){
        Log::info("PULL COMPLETED - Resource [$shipmentResource->ID] stocker=[$stockerID]");
     } else {
       Log::error("PULL COMPLETE ERROR while trying to Finalize SHIP Resource $shipmentResource->ID for stocker=[$stockerID]");
     }

     return $success;
  }
  public static function GetOpenShippingRequests(){
    $sql = "SELECT COUNT(id) as rowcount FROM shipment WHERE date_shipped IS NULL OR LOCATE('970', date_shipped,1)";
    $rowCount = self::GetInstance()->_genericSQL_Get($sql);

    return $rowCount[0]['rowcount']<1 ? 0 : $rowCount[0]['rowcount'];
  }
  public static function GetOpenStorageRequests(){
    $sql = "SELECT COUNT(id) as rowcount FROM storage WHERE date_stored IS NULL OR LOCATE('970', date_stored,1)";
    $rowCount = self::GetInstance()->_genericSQL_Get($sql);

    return $rowCount[0]['rowcount']<1 ? 0 : $rowCount[0]['rowcount'];
  }
  public static function GetStorageRequestsByStocker($employeeID=null){
      return self::GetInstance()->_getItemsPending(null, 'store', null, $employeeID, null);
  }
  public static function GetShippingRequestsByStocker($employeeID=null){

      $shippingRequests = array();
      $results = self::GetInstance()->_getItemsPending(null, 'ship', null, $employeeID, null);

      //Flatten the duplicated requests, combine requested pallets/bins
      foreach ($results as $item) {

          $shippingRequestID  = $item['shipmentid'];
          $shipQty            = $item['qty'];
          $confirmed_pulled_qty = $item['confirmed_pulled_qty'];
          $binName            = $item['binname'];
          $productName        = $item['name'];
          $itemID             = $item['id'];
          $lotnumber          = $item['lotnumber'];
          $tag                = $item['tag'];
          $palletName         = $item["palletname"];
          $binName            = $item["binname"];
          $receiverID         = $item["userid_receiver"];
          $qtyInStock         = $item["qty_in_stock"];
          $clientID           = $item["ownerid"];

          $receiver = "Not specified";
          if ($receiverID>0){
            $user = new User($receiverID);
            $receiver = $user->GetDisplayText();
          }

          $data = array("shipmentid"  =>$shippingRequestID,
                      "clientID"      =>$clientID,
                      "qty"           => $shipQty,
                      "qty_in_stock"  => $qtyInStock,
                      "receiver"      => $receiver,
                      "lotnumber"     => $lotnumber,
                      "tag"           => $tag,
                      "bins"          => $binName,
                      "targetpalletNames" => $palletName . ";$qtyInStock",
                      "productName"       => $productName,
                      "itemID"            => $itemID,
                      "receiverID"    => $receiverID,
                      "confirmed_pulled_qty" => $confirmed_pulled_qty
                        );

          if (array_key_exists($shippingRequestID, $shippingRequests)){
              $shippingRequests[$shippingRequestID]['bins'] .= ", $binName";
              $shippingRequests[$shippingRequestID]['targetpalletNames'] .= ",$palletName;$qtyInStock";
          } else {
              $shippingRequests[$shippingRequestID] = $data;
          }
      }

      return $shippingRequests;
  }
  public static function SearchForItem($searchString, $ownerID=null){
      return self::GetInstance()->_searchForItemByName($searchString, $ownerID);
  }

  public static function TransactionAdd($type, $userid, $clientid=null,
                                        $receiverid=null,
                                        $itemid=null, $providerid=null,
                                        $palletid=null, $binid=null,
                                        $notes=null){

    if (!self::GetInstance()->_transactionAdd($type, $userid, $clientid,
                                              $receiverid, $itemid, $providerid,
                                              $palletid, $binid, $notes)){
      Log::error("TRANSACTION ERROR Error writing a transaction [$type] for user [$userid] client [$clientid] reciever [$receiverid] item [$itemid] notes [$notes] providerid [$providerid], palletid [$palletid], binid [$binid] ");
    }

  }
  public static function GetMostRecentShipStoreDateForItem($itemID, $type){

    $mostRecentDate = null;
    if ($type=='shipment'){
      $mostRecentShipped = self::GetInstance()->_getmostRecentShippedDateForItem($itemID);
  	  if (!empty($mostRecentShipped)){
  		  $mostRecentDate = $mostRecentShipped[0]['date_shipped'];
  	  }
    } else {
      $mostRecentStored = self::GetInstance()->_getmostRecentStorageDateForItem($itemID);
  	  if (!empty($mostRecentStored)){
  		    $mostRecentDate = $mostRecentStored[0]['date_stored'];
  	  }
    }

    if ( substr($mostRecentDate,0,4)==='1970'){
      $mostRecentDate = null;
    }

	  return $mostRecentDate;
  }
  /*
  *
  */
  public static function GetTransactionData($type, $userID, $keyFieldName, $groupByFieldName=null, $filterByData=null, $offsetValue, $limit){

    $recordIDsCSV = "";
    $returnData = null;
    $reportConfig = ConfigurationManager::GetParameter('reportConfig');
    $includeLoginLogout = $reportConfig->includeLoginLogout;
    $sqlCount = "SELECT COUNT(transactions.id) as recordCount FROM transactions";

    $recs = array();
    switch ($type) {
      case Constants\ReportNames::TRANS_USER:

        $sqlData  = "SELECT CONCAT(users.firstname, ' ', users.lastname) as transusername,
        users.id as userid,
        providers.name as providername,
        CONCAT(clients.firstname, ' ', clients.lastname) as clientname,
        CONCAT(receivers.firstname, ' ', receivers.lastname) as receivername,
        receivers.id as receiverid,
        transactions.type as type, transactions.time_stamp,
        storageitem.name as storageitemname,
        transactions.notes as notes,
        transactions.id as transactionid,
        storagepallet.name as palletname,
        storagebin.name as binname
        FROM transactions
        INNER JOIN user users ON users.id=transactions.userid
        LEFT JOIN user clients ON clients.id=transactions.clientid
        LEFT JOIN provider providers ON providers.id=transactions.providerid
        LEFT JOIN user receivers ON receivers.id=transactions.receiverid
        LEFT JOIN storageitem ON storageitem.id=transactions.itemid
        LEFT JOIN storagepallet ON storagepallet.id=transactions.palletid
        LEFT JOIN storagebin ON storagebin.id=transactions.binid";

        $sqlCount .= " INNER JOIN user ON user.id=transactions.userid";
        $sqlWhere  = " WHERE transactions.$keyFieldName = $userID ";
        $sqlOrderBy = " ORDER BY userid, time_stamp DESC";

        break;

      case Constants\ReportNames::TRANS_CLIENT:
        $providers = DataProvider::GetAccessibleRecords("Provider", $userID);
        $providerIDArray      = null;
        //Build CSV of providerIDs
        foreach ($providers as $provider) {
          $providerIDArray[] = $provider['id'];
        }

        $providerIDsCSV = Util::Array2csv($providerIDArray, ["ID"]);
        $sqlCount .= " INNER JOIN user as clients ON clients.id=transactions.clientid";

        $sqlData  = "SELECT CONCAT(users.firstname, ' ', users.lastname) as transusername,
        users.id as userid,
        providers.name as providername,
        CONCAT(clients.firstname, ' ', clients.lastname) as clientname,
        CONCAT(receivers.firstname, ' ', receivers.lastname) as receivername,
        clients.id as clientid,
        receivers.id as receiverid,
        transactions.type as type, transactions.time_stamp,
        storageitem.name as storageitemname,
        transactions.notes as notes,
        transactions.id as transactionid,
        storagepallet.name as palletname,
        storagebin.name as binname
        FROM transactions
        INNER JOIN user users ON users.id=transactions.userid
        LEFT JOIN user clients ON clients.id=transactions.clientid
        LEFT JOIN provider providers ON providers.id=transactions.providerid
        LEFT JOIN user receivers ON receivers.id=transactions.receiverid
        LEFT JOIN storageitem ON storageitem.id=transactions.itemid
        LEFT JOIN storagepallet ON storagepallet.id=transactions.palletid
        LEFT JOIN storagebin ON storagebin.id=transactions.binid";

        $sqlWhere  = " WHERE transactions.$keyFieldName IN ";
        $sqlWhere .= "(SELECT userid FROM client WHERE providerid IN ($providerIDsCSV))";
        $sqlOrderBy = " ORDER BY clientid, time_stamp DESC";

        break;
      case Constants\ReportNames::TRANS_PROVIDER:
        // TODO:
        //  $fallbackGroupFieldName = "providername"; //used when trans client is N/A, like adding pallets, etc
        //  $recs = DataProvider::GetAccessibleRecords("Provider", $userID);

        break;

      case Constants\ReportNames::TRANS_EMPLOYEE:

        $providers = DataProvider::GetAccessibleRecords("Provider", $userID);
        $providerIDArray      = null;
        //Build CSV of providerIDs
        foreach ($providers as $provider) {
          $providerIDArray[] = $provider['id'];
        }

        $providerIDsCSV = Util::Array2csv($providerIDArray, ["ID"]);
        $sqlCount .= " INNER JOIN user as employees ON employees.id=transactions.userid";

        $sqlData  = "SELECT CONCAT(users.firstname, ' ', users.lastname) as transusername,
        users.id as userid,
        providers.name as providername,
        CONCAT(clients.firstname, ' ', clients.lastname) as clientname,
        CONCAT(receivers.firstname, ' ', receivers.lastname) as receivername,
        employees.id as employeeid,
        receivers.id as receiverid,
        transactions.type as type, transactions.time_stamp,
        storageitem.name as storageitemname,
        transactions.notes as notes,
        transactions.id as transactionid,
        storagepallet.name as palletname,
        storagebin.name as binname
        FROM transactions
        INNER JOIN user users ON users.id=transactions.userid
        LEFT JOIN user clients ON clients.id=transactions.clientid
        LEFT JOIN user employees ON employees.id=transactions.userid
        LEFT JOIN provider providers ON providers.id=transactions.providerid
        LEFT JOIN user receivers ON receivers.id=transactions.receiverid
        LEFT JOIN storageitem ON storageitem.id=transactions.itemid
        LEFT JOIN storagepallet ON storagepallet.id=transactions.palletid
        LEFT JOIN storagebin ON storagebin.id=transactions.binid";

        $sqlWhere  = " WHERE transactions.$keyFieldName IN ";
        $sqlWhere .= "(SELECT userid FROM storagefacilityworkers WHERE providerid IN ($providerIDsCSV))";
        $sqlOrderBy = " ORDER BY employeeid, time_stamp DESC";

        break;

      case Constants\ReportNames::TRANS_RECEIVER:

        $sqlCount .= " LEFT JOIN user as receivers ON receivers.id=transactions.receiverid";

        $sqlData  = "SELECT CONCAT(users.firstname, ' ', users.lastname) as transusername,
        users.id as userid,
        CONCAT(receivers.firstname, ' ', receivers.lastname) as receivername,
        receivers.id as receiverid,
        transactions.type as type, transactions.time_stamp,
        storageitem.name as storageitemname,
        storageitem.ownerid,
        transactions.notes as notes,
        transactions.id as transactionid,
        storagepallet.name as palletname,
        storagebin.name as binname
        FROM transactions
        INNER JOIN user users ON users.id=transactions.userid
        LEFT JOIN user receivers ON receivers.id=transactions.receiverid
        LEFT JOIN storageitem ON storageitem.id=transactions.itemid
        LEFT JOIN storagepallet ON storagepallet.id=transactions.palletid
        LEFT JOIN storagebin ON storagebin.id=transactions.binid";

        $sqlWhere  = " WHERE transactions.receiverid IN (SELECT receiverid FROM receiver WHERE clientid=$userID ) ";
        $sqlOrderBy = " ORDER BY storageitemname, time_stamp DESC";



        break;

      default:
        $recs = null;
        break;
    }

    //Add common SQL for all transactions

    //Exclude login/logout/masquerade txns
    if (!$includeLoginLogout){
      $sqlWhere .= " AND type!='USER_LOGIN' AND type!='USER_LOGOUT'";
      $sqlWhere .= " AND type!='MASQUERADE_START' AND type!='MASQUERADE_END'";
    }

    //Add external table filter (i.e. user.id=userID)
    if (!is_null($filterByData)){
      $filterBytableName          = $filterByData[0];
      $filterByFieldName          = $filterByData[1];
      $filterByrecordID           = $filterByData[2];

      $sqlWhere .= " AND $filterBytableName.$filterByFieldName=$filterByrecordID";
    }

    //Before getting the data, grab the total count using this query, and
    //  also grab the starting recordID using the offset requested.

    //(1.) EXECUTE THE COUNT QUERY to get current total record count and add it to returnData
    $sqlCount   .= $sqlWhere;
    $rowCount = self::GetInstance()->_genericSQL_Get($sqlCount);

    if ($rowCount[0]['recordCount']<1){
      return null;
    } else {
      $returnData['maxRowCount'] = $rowCount[0]['recordCount'];

    }


    //(2.) Get the recordID of the first record for the offset
    $sqlOffset = $sqlData . $sqlWhere . $sqlOrderBy . " LIMIT 1 OFFSET $offsetValue";
    $startRecordNumberData = self::GetInstance()->_genericSQL_Get($sqlOffset);
    $startRecordNumber = $startRecordNumberData[0]['transactionid'];


    //(3.) - Attach the data
    //Attach the data
    // TODO: This ONLY works for Dates right now.  Notice that it assumes
    //  a transaction ID in reverse because it is ordered by date DESC.
    //  This will not work long term when column sorting is implemented

    $sqlWhere .= " AND transactions.id <= $startRecordNumber";//pagination

    $sql = $sqlData . $sqlWhere;

    // add the grouping and LIMIT
    $sql .= " $sqlOrderBy LIMIT $limit";
    $rows = self::GetInstance()->_genericSQL_Get($sql);

    foreach ($rows as $row) {
      $keyFieldValue = $row[$groupByFieldName];
      if (empty($keyFieldValue) && isset($fallbackGroupFieldName)){
        $keyFieldValue = $row[$fallbackGroupFieldName];
      }
      if (!isset($returnData[$keyFieldValue])){
        $returnData[$keyFieldValue] = array();
      }
      $returnData[$keyFieldValue][] = $row;
    }

    return $returnData;
  }
  public static function GetBinForPallet($palletID){
    $sql = "SELECT binid FROM storagebininventory WHERE palletid=$palletID";
    return self::GetInstance()->_genericSQL_Get($sql);
  }
  public static function RemoveStorageItemFromPallets($storageid){
    $sql = "DELETE FROM storagepalletinventory WHERE storageid=$storageid";
    return self::GetInstance()->_genericSQL_update($sql);
  }
  public static function UnTagPalletsForShipment($shipmentid){
    $sql = "UPDATE storagepalletinventory SET shipment_request_id=NULL WHERE shipment_request_id=$shipmentid";
    return self::GetInstance()->_genericSQL_update($sql);
  }
  public static function ExecuteSQL($sql){
    Log::warning("Should not be using DataProvider::ExecuteSQL()  that often.  Better ORM interfaces should be set.");

    return self::GetInstance()->_genericSQL_execute($sql);
  }
}
