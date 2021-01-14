<?php
namespace Ability\Warehouse;

/*
MySQLDataProvider
*/
class MySQLDataProvider extends DataProvider {

  // Holds prepared statement objects
  public static $_preparedStatementObjects;

  function __construct($configuration) {
    parent::__construct($configuration);
  }
  public function GetLastInsertedID(){
    return $this->handler->lastInsertId;
  }

  /*
    Prepare a single DB statement
    Customization for MySQL:  Store the resulting object in a member array
  */
  protected function prepareSingleStatement($statementName, $statementString)
  {
    $success = false;
    //Log::debug("PREPARE SINGLE $statementName, $statementString");
    $errorMessage = "===== STATEMENT PREPARE ERROR preparing $statementName using $statementString";
    try {

      $preparedStatement = $this->handler->prepare($statementString);

      if(!empty($preparedStatement))
      {
        $num_statment_params = $preparedStatement->columnCount();
        self::$_preparedStatementObjects[$statementName] = $preparedStatement;
        $success = true;
      }

    } catch (\Exception $e) {
        Log::error($errorMessage . " " . $e->getMessage());
    }

    return $success;
  }
  /*
   return CSV of prepared statements
  */
  public function ShowPreparedStatements()
  {
    $str = "";
    foreach (array_keys(self::$_preparedStatementObjects) as $statementName) {
       $str .= $statementName . ", ";
    }
    return substr($str, 0, -1);
  }
  public function insertrecord($resourceName, $fieldData){

    Log::debug("INSERTING $resourceName record");

    return ($this->_sqlExecuteStatement(Constants\SqlPrepareTypes::SQL_INSERT . $resourceName, $fieldData )) ? $this->GetLastInsertedID() : false;

  }
  /* Update a record */
  public function updaterecord($resourceName, $fieldData){
    return ($this->_sqlExecuteStatement(Constants\SqlPrepareTypes::SQL_UPDATE . $resourceName, $fieldData )) ? true : false;
  }
  /*
  delete an existing record
  */
  public function deleterecord($resource, $Id){
    return ($this->_sqlExecuteStatement(Constants\SqlPrepareTypes::SQL_DELETE . $resource, [$Id])) ? true : false;
  }

  /*  _sqlExecuteStatement - Execute the sql prepared statement and return it or null
  @returns Prepared Statement Object (which can be fetched on if needed) OR NULL
  */
  protected function _sqlExecuteStatement($preparedStatementName, $queryParameters)
  {
    $rows = null;
    $parameterTypes = "";
    $preparedStatementObject = null;

    try {
        $index=0;
        $preparedString = self::$PreparedStatementStrings[$preparedStatementName];

        Log::debug("EXECUTING STATEMENT [$preparedStatementName] using string [$preparedString]");

        $success = self::$_preparedStatementObjects[$preparedStatementName]->execute($queryParameters);

        return ($success) ? self::$_preparedStatementObjects[$preparedStatementName] : null;
      } catch (\Exception $e) {
        Log::error("ERROR WITH STATEMENT PREPARE FOR [$preparedStatementName]");
        Log::error($e->getMessage());
        return false;
      }
  }
  /*
    Find record using uname and passwd, look at profilename and emailaddress fields
    @returns:  userID of the found resource OR 0 if none found
  */
  protected function _authenticate($resource, $uname, $rawpassword){

    $result = $this->_sqlExecuteStatement("AUTH", array("emailaddress"=>$uname,"profilename"=>$uname));

    if ($result){
      $userRecords = $result ? $result->fetchall() : null;
    }

    if ($userRecords==null || count($userRecords) < 1){
      Log::error("No record matching $uname for profilename OR emailaddress. Remember, that these fieldnames can not be changed in config.");
      return 0;
    }
    else{
      return password_verify($rawpassword, $userRecords[0]["upasswd"]) ? $userRecords[0]["id"] : 0;
    }
  }
  /*
    Add a user
    @returns: userid, errMsg or empty
  */
  protected function _adduser(  $resource, $emailaddress, $username,
                                $password_raw, $firstname,
                                $lastname, $city, $state, $zip)
  {
    $errMsg = "";
    $newUserID = 0;//Holds the newly created Userid

    //hash the password before inserting it
    $pwd_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

    $sql = "INSERT INTO user (  `emailaddress`,
                                `profilename`,
                                `upasswd`,
                                `firstname`,
                                `lastname`,
                                `city`,
                                `state`,
                                `zip`) VALUES ( ':emailaddress',
                                                ':profilename',
                                                ':upasswd',
                                                ':firstname',
                                                ':lastname',
                                                ':city',
                                                ':state',
                                                ':zip')";

    $preparedAddUser = $this->handler->prepare($sql);

    $userData = array($emailaddress, $username, $pwd_hashed,
                      $firstname, $lastname, $city, $state, $zip);

    $preparedAddUser->bindValue(':emailaddress', $emailaddress, PDO::PARAM_STR);
    $preparedAddUser->bindValue(':profilename', $profilename, PDO::PARAM_STR);
    $preparedAddUser->bindValue(':upasswd', $upasswd, PDO::PARAM_STR);
    $preparedAddUser->bindValue(':firstname', $firstname, PDO::PARAM_STR);
    $preparedAddUser->bindValue(':lastname', $lastname, PDO::PARAM_STR);
    $preparedAddUser->bindValue(':city', $city, PDO::PARAM_STR);
    $preparedAddUser->bindValue(':state', $state, PDO::PARAM_STR);
    $preparedAddUser->bindValue(':zip', $zip, PDO::PARAM_STR);

    try {
      $success = $preparedAddUser->execute();
      $newUserID = $success ? $this->GetLastInsertedID() : 0;
    }
    catch (\PDOException $e) {
      $errMsg = $this->handler->error;
    }
    //If already failed due to exception
    if (!empty($errMsg) || !$success)
    {
      $errMsg = !$success ? $errMsg : $this->handler->error;;
      Log::error("ERROR WITH ADDING NEW USER  detail: $errMsg");
    }

    return array($newUserID, $errMsg);
  }
  /*
     - Set an Authorization Code
     - Add an entry into the authorization table
     - IF userID exist: this is used as part of authorization flow during site sessioning
     - If userID NOT Exist, an entry still needs to be set:
              (to ensure our software is doing these things and not a bot)
     -     initial login (step 1 - set by Auth API and checked later when issuing AccessToken )
     -     password-reset flow (step 1 - request a reset link)

    @returns authCode or null
  */
  protected function _setAuthorizationCode($userID=0, $expirySeconds){

    $authCode = Util::GenerateAuthorizationCode();
    $userID = empty($userID) ? 0 : $userID;
    $userIDMsg = '';
    $msg = "AUTH CODE ";

    $expiresUnixTimestamp = time() + $expirySeconds;

    // TODO: databse default is already set to 0 for userid , this can be removed
    $sql = "INSERT INTO `authorization` (`userid`, `authcode`, `expires_unix_time`)
                            VALUES ($userID, '$authCode', $expiresUnixTimestamp)";

    $statement = $this->handler->query($sql);
    if ($statement){

      $msg .= " SET SUCCESS - $authCode";
      $t = time();
      $edate = Util::GetFormattedDate($expiresUnixTimestamp);
      $nowDate = Util::GetFormattedDate($t);
      Log::debug("GENERATED $authCode will expire at $edate it is $nowDate");

      return $authCode;
    }
    else{
      $msg .= " SET FAILURE ";
      Log::error($msg);
      return null;
    }
  }
  /*
    Get an AccessToken using a valid AuthCode and userID combo
    Validate there is a record in authorization with an AuthCode
    return $accessToken or null ;
  */
  protected function _setAccessToken($userID, $authCode, $expirySeconds){

    $returnData = array();
    $error = true;
    $accessToken = null;
    $expiresUnixTimestamp = \time() + $expirySeconds;

    //Verify the data (instead of sql-injection)
    if (  is_nan($userID) ||
          strpos($authCode, " ") ||
          strlen($authCode)!=36
        )
    {
      Log::error("BAD USER_ID $userID INPUT FOR VALIDATE AUTH CODE $authCode");
    }
    else {
      //Generate an Access Token;
      $accessToken = UTIL::GenerateAccessToken();

      Log::debug("SWAPPING AuthCode FOR AccessToken for user $userID .  Access Token - $accessToken");

      $sql = "UPDATE `authorization` SET
                `accesstoken`='$accessToken',
                `expires_unix_time`=$expiresUnixTimestamp,
                `authcode` = NULL
                WHERE userid=$userID AND `authcode`='$authCode'";

      $statement = $this->handler->query($sql);
      if ($statement){
        $returnData["accessToken"]        = $accessToken;
        $returnData["expires_unix_time"]  = $expiresUnixTimestamp;

        $edate = Util::GetFormattedDate($expiresUnixTimestamp);
        Log::debug("AccessToken/authCode updated successfuly in DB for $userID.  It will expire at $edate");
        $error = false;
      }
      else{
        Log::error("Error with the update statement while swapping authCode for accessToken");
      }
    }
    return $returnData;
  }
  /*
    Validate an entry exist matching userID and accessToken
    @param: $currentAccessToken
    @param: $extendSeconds - add seconds to current expiration
    @returns: array [accessToken][expires_unix_time]
  */
  protected function _validateAccessToken($currentAccessToken, $userID, $extendSeconds=0)
  {

    $returnData = array();
    $sql = "SELECT `expires_unix_time` FROM `authorization` WHERE `accesstoken` = '$currentAccessToken' AND `userid`=$userID";
    $result = $this->handler->query($sql);
    if ($result->num_rows > 0){

      $row        = $result->fetch(\PDO::FETCH_ASSOC);
      $expiryTime = $row["expires_unix_time"];
      $timeNow = time();

      // Token IS VALID
      if ($expiryTime>$timeNow)
      {
        //Default return data to current record
        $returnData["accessToken"]        = $currentAccessToken;
        $returnData["expires_unix_time"]  = $expiryTime;

        //SHOULD IT BE EXTENDED ?
        if ($extendSeconds>0){

          $newAccessToken = UTIL::GenerateAccessToken();
          Log::info("Access token has been requested to be extended.  Generating a new one - $newAccessToken");

          $newExpiryUnixTimestamp = time() + $extendSeconds;

          $sql = "UPDATE `authorization` SET
                    `accesstoken`='$newAccessToken',
                    `expires_unix_time`=$newExpiryUnixTimestamp,
                    `authcode` = NULL
                    WHERE userid=$userID AND `accesstoken`='$currentAccessToken'";


          if ($this->handler->query($sql) === TRUE){
            $returnData["accessToken"]        = $newAccessToken;
            $returnData["expires_unix_time"]  = $newExpiryUnixTimestamp;

            $edate = Util::GetFormattedDate($newExpiryUnixTimestamp);
            Log::debug("AccessToken/authCode updated successfuly in DB for $userID.  It will expire at $edate");
            $error = false;
          }
          else{
            Log::error("Error with the update statement while swapping authCode for accessToken");
          }
        }
        else{
          Log::debug("Access token $currentAccessToken is not being extended");
        }
      }
      else{ // TOKEN IS EXPIRED

        $expiredTime = Util::GetFormattedDate($expiryTime);
        Log::debug("ACCESS TOKEN IS EXPIRED '$currentAccessToken' at '$expiredTime'.
          Removing this from DB along with other entries if a userID is known");

        $this->_deleteAuthorizations($userID);
      }
    }
    else{
      Log::error("User $userID and accessToken $currentAccessToken NOT FOUND. Cleaning up all existing authorizations for $userID");
      $this->_deleteAuthorizations($userID);
    }

    return $returnData;
  }
  /*
  * Get access Token data
  * returns: array[userID, expires_unix_time]
  */
  protected function _getAccessTokenData($accessToken){

    $returnData = array();
    $sql = "SELECT `userid`, `expires_unix_time` FROM `authorization` WHERE `accesstoken`='$accessToken'";

    $result = $this->handler->query($sql);
    if ($result){
      $row = $result->fetch(\PDO::FETCH_ASSOC);
      $returnData["userID"] = $row["userid"];
      $returnData["expires_unix_time"] = $row["expires_unix_time"];
    }
    return $returnData;
  }
 /*
    Verify that a user exists
    @param: $emailaddress - REQUIRED
    @param: $userID - optional
    @param: $authCode - optional

    @returns: $userID
  */
  protected function _verify_user_exists($emailaddress, $profileName, $authCode){

    $userID = 0;

    $sql = "SELECT user.id as userid FROM user";

    if (!empty($authCode)){
      $sql .= ",authorization WHERE authorization.authcode='$authCode' AND ";
    }
    else{
      $sql .= " WHERE";
    }
    $sql .= " user.emailaddress='$emailaddress'";

    if (!empty($profileName)){
      $sql .= " AND user.profilename='$profileName'";
    }

    Log::debug("FETCHING USER WITH AUTH " . $sql);

    $row = $this->_genericSQL_Get($sql);

    $i=0;
    foreach ($row as $item) {
      $uid = "USERID: --> " . $item['userid'];
      Log::debug('item --- idx [' . $i++ . "] ==> value: [$uid] ");
    }
    if (!empty($row)){

      $userID = $row[0]['userid'];

      if ($userID>0)
      {

        //Be sure stale userIDs are deleted, they should not exist
        $sqlDelete = "DELETE FROM `authorization` WHERE `userID`=$userID";
        $result = $this->_genericSQL_delete($sqlDelete);

        //Now update the authToken table to include this matched userID

        $sqlUpdate = "UPDATE `authorization` SET `userid`=$userID WHERE `authcode`='$authCode'";

        if ($this->handler->query($sqlUpdate) === TRUE){
          Log::debug("_verify_user_exists AuthCode updated successfuly to include userID=$userID");
        }
        else{
          Log::warning("_verify_user_exists ERROR while trying to update $userID to the authorization table.  SQL:  $sqlUpdate");
        }
      }
      else{
        Log::error("_verify_user_exists ERROR userid was not returned using $emailaddress and $authCode");
      }
    }

    Log::debug("RETURNING USERID [$userID]");

    return $userID;
  }
  /*
    Verify an authToken has not expired in the authtokens table
    return $userID, 0 if empty or -1 if expired
  */
  protected function _validateAuthCode($authCode){

    $userID = -1;
    $sql = "SELECT `userid`, `expires_unix_time` FROM `authorization` WHERE `authcode`='$authCode'";
    $statement = $this->handler->query($sql);

    if ($statement){

      $row        = $statement->fetch(\PDO::FETCH_ASSOC);
      if (empty($row)){
        Log::error("No data returned using SQL: $sql");
        return false;
      }
      $userID     = $row["userid"];
      $expiryTime = $row["expires_unix_time"];
      $timeNow = time();

      if ($expiryTime>$timeNow){
        return $userID;
      }
      else{
        Log::debug("TOKEN EXPIRED $expiryTime > $timeNow .. DELETING THIS AUTH");

        $expiredTime = Util::GetFormattedDate($expiryTime);
        Log::debug("AUTHCODE EXPIRED '$authCode' at '$expiredTime'.
          Removing this from DB along with other entries if a userID is known");

        $sqlDelete = "DELETE FROM `authorization` WHERE `authcode`='$authCode'";
        if (!empty($userID)){
          $sqlDelete .= " OR `userid`=$userID";
        }

        Log::debug("DELETING OLD AUTH: " . $sqlDelete);
        $r = $this->handler->query($sqlDelete);
        if ($r){
          $userID = empty($userID) ? 0 : $userID;//????
          Log::debug("DELETE authCodes Success for userID=$userID");
        }
        else{
          Log::error(" $r = ERROR while trying to remove old authCodes -  $sqlDelete");
          return false;
        }
      }
    }
    return true;
  }
  /*
    Reset a user's password
    @param - $userID of the requested user
    @param - $password_raw - the raw text password
  */
  protected function _resetPassword($userID, $password_raw){

    $error = true;
    $newPasswordHash = password_hash($password_raw, PASSWORD_DEFAULT);
    $sqlUpdate = "UPDATE `user` SET `upasswd`='$newPasswordHash' WHERE `id`=$userID";

    if ($this->handler->query($sqlUpdate) === TRUE){
      $error = false;
    }
    else{
      Log::error("ERROR while trying to update password for user $userID .  SQL:  $sqlUpdate");
    }

    return $error;
  }
  /*
    Determine if a $userID is in adminusers table
    @param: $userID
    @returns: bool
  */
  /*
    @param: userID
    @returns: Success bool
  */
  protected function _deleteAuthorizations($userID = 0){


    $userID = is_nan($userID) ? 0 : $userID;
    $currentTime = time();
    $sqlDelete = "DELETE FROM `authorization` WHERE";

    if ($userID>0){

      $sqlDelete .= " `userid`=$userID";
    }
    else{
      $sqlDelete .= " `expires_unix_time`< $currentTime";
    }

    Log::debug("CLEANUP AUTHS for User - $sqlDelete");

    $statement = $this->handler->query($sqlDelete);
    $deletedRows = $statement->rowCount();

    if ($deletedRows>0){
      Log::debug("DELETE authorizations Success for userID=$userID");
    }
    else{
      Log::warning("DELETE AUTHS Nothing to delete -  $sqlDelete");
    }

    return 1;
  }
  /*
    @returns: bool
  */
  protected function _isUserAdmin($userID){

    if ($userID==0 || is_nan($userID))
    {
      Log::error("IsUserAdmin - BAD REQUEST for $userID");
      return false;
    }
    else{
      $sql = "SELECT `userid` FROM `adminusers` WHERE `userid`=$userID";
      $statement = $this->handler->query($sql);
      $count = $statement->fetchColumn();
      if ($count){
        return true;
      }
      else{
        return false;
      }
    }
  }
  /*
  *  Return true based on count of results > 0
  */
  protected function RecordsExist($sql){
    Log::info("CALLING RecordsExisting using SQL $sql");
    $result = $this->handler->query($sql);
    return ($result->num_rows > 0);
  }
  protected function _getAccessibleRecordsForResource($resourceName, $userIDs){


    $resourceClassName = NAME_SPACE . "\\" . $resourceName;
    $tableName = $resourceClassName::$TableName;
    $preparedStatementIndex = Constants\SqlPrepareTypes::SQL_SELECT_NAV_OWN . $tableName;

    $sql = self::$PreparedStatementStrings[$preparedStatementIndex];
    $ids = "";
    foreach ($userIDs as $userID) {
      $ids .= $userID . ",";
    }
    $ids = substr($ids,0,-1);

    $params = array("searchField"=>$ids);
    if (ConfigurationManager::IsResourceCoOwned($resourceName)){
      $params["id"]=$ids;
    }

    $result = $this->_sqlExecuteStatement(Constants\SqlPrepareTypes::SQL_SELECT_NAV_OWN . $tableName, $params);

    $userRecords = $result ? $result->fetchall() : null;

    return ($userRecords==null || count($userRecords) < 1) ? null : $userRecords;

  }

  /*
  *  Get all records owned by user
  *   returns mysql row result format, rows[] = {'name', 'id'}
  */
  protected function _getOwnedRecordsForResource( $resourceTableName,
                                                  $ownerFieldName,
                                                  $displayFieldName,
                                                  $userID){

    $recordsForResource = array();
    $sql = "SELECT `id`, `$displayFieldName` FROM `$resourceTableName` WHERE `$ownerFieldName` = $userID";

    $result = $this->handler->query($sql);
    $recordsForResource = (!empty($result)) ?
                                $result->fetch_all(MYSQLI_ASSOC) : null;


    $c = count($recordsForResource);
    Log::debug("========= _getOwnedRecordsForResource OWNER FOUND $c records owned by $userID using SQL $sql");


    return $recordsForResource;
  }
  /*
  *  Get all records associated to user
  *   returns int[] or empty []
  */
  protected function _getAssociatedRecordsForResource(  $associationTableName,
                                                        $ownerFieldName,
                                                        $fieldSQLForDisplay,
                                                        $userID){

    $recordsForResource = array();
    $sql = "SELECT `id`, `$displayFieldName` FROM `$associationTableName` WHERE `$ownerFieldName` = $userID";

    $result = $this->handler->query($sql);
    while ($recordID= $result->fetch_row()){
      $recordsForResource[] = $recordID;
    }

    $c = count($recordsForResource);
    Log::debug("ASSOCIATIONS _getAssociatedRecordsForResource - FOUND $c records owned by $userID using SQL $sql");

    return $recordsForResource;
  }
  /*
  * Get array of items and quantities
  * @returns: items[itemid] = quantities
  */
  protected function _getItemsInStorage($userID, $itemID=null, $showLots=false, $isProvider=false){

    $itemsInStorage = [];

    $sql = "SELECT storageitem.id,
                    storageitem.name as name,
                    user.companyname as companyname,
                    storagepalletinventory.lotnumber as lotnumber,
                    storagepalletinventory.tag as tag,
                    storagepalletinventory.item_qty as qty
                    FROM storageitem
                    INNER JOIN user ON storageitem.ownerid = user.id
                    INNER JOIN storagepalletinventory ON storagepalletinventory.itemid=storageitem.id";
    $sql .= " WHERE `storagepalletinventory`.confirmed=1";

    if (!is_null($itemID)){
      $sql .= " AND storageitem.id=$itemID";
    }
    if (!$isProvider){
      $sql .= " AND storageitem.ownerid=$userID ";
    }
    else{
      $sql .= " AND storageitem.ownerid IN
        (SELECT userid from client WHERE providerid IN
          (
            SELECT providerid FROM providerowners WHERE userid=$userID
            UNION
            SELECT id FROM provider WHERE ownerid=$userID
          )
        )";

    }

    $sql .= " ORDER BY companyname,name ASC";
    $result = $this->handler->query($sql);
    Log::debug("GET ITEMS IN STORAGE: $sql");
    if ($result){
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){

        $productName = $row['name'];
        $qty = intval($row["qty"]);
        $itemid = $row["id"];
        $lotnumber = $row["lotnumber"];
        $companyname = $row["companyname"];
        if ($showLots){
          $itemsInStorage[$itemid . $lotnumber] = array(  "id"=>$row["id"], "name"=>$row["name"], "qty"=>$qty, "lotnumber"=>$row["lotnumber"]);
        }
        else{
          if (!array_key_exists($itemid, $itemsInStorage)){
            $itemsInStorage[$itemid] = array(  "id"=>$row["id"], "companyname"=>$row["companyname"], "name"=>$row["name"], "qty"=>$qty, "lotnumber"=>$row["lotnumber"]);
          }
          else{
            $itemsInStorage[$itemid]["qty"] += $qty;
          }
        }
      }
    }
    return $itemsInStorage;
  }
  protected function _getLotsInStorage($userID, $lotnumbers=null){

    $itemsInStorage = $this->_getItemsInStorage($userID, null, true);

    $lotsInStorage = array();
    if (is_null($itemsInStorage)){
      return $lotsInStorage ;
    }

    //Now group by lot
    foreach ($itemsInStorage as $row) {
      $total      = intval($row["qty"]);
      $lotnumber  = $row["lotnumber"];
      //if items do not have a lot number, add them using id
      if (empty($lotnumber)){
        $lotnumber = $row["id"];
      }
      if (!array_key_exists($lotnumber, $lotsInStorage)){

        $lotsInStorage[$lotnumber] = array(  "name"=>$row["name"], "qty"=>$total, "lotnumber"=>$row["lotnumber"]);
      }
      else{
        $lotsInStorage[$lotnumber]["qty"] += $total;
      }
    }

    return array_values($itemsInStorage);
  }
  /*
  * $unreachedStep = 'created, approved, stored'
  */
  protected function _getStorageRequests($userID, $unreachedStep){

    $storageRequests = array();
    $sql = "SELECT storageitem.id, storageitem.name, storage.lotnumber,
                    storage.tag, storage.qty,
                    storage.date_created, storage.date_approved, storage.id AS storageid,
              FROM storageitem
              INNER JOIN storage
              ON storageitem.id=storage.itemid
              WHERE storage.date_" . $unreachedStep . " IS NULL";
    $sql .= " OR storage.date_" . $unreachedStep . " < '1971'";
    $sql .= " AND storageitem.ownerid = $userID";

    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $storageRequests[] = $row;
    }

    return $storageRequests;
  }
  /*
  * $unreachedStep = 'created, approved, shipped'
  */
  protected function _getShipmentRequests($userID, $unreachedStep){

    $shipmentRequests = array();
    $sql = "SELECT storageitem.id, storageitem.name, shipment.lotnumber,
                    shipment.tag, shipment.qty,
                    shipment.date_created, shipment.date_approved, shipment.id AS shipmentid
              FROM storageitem
              INNER JOIN shipment
              ON storageitem.id=shipment.itemid
              WHERE shipment.date_" . $unreachedStep . " IS NULL";
    $sql .= " OR shipment.date_" . $unreachedStep . " < '1971'";
    $sql .= " AND storageitem.ownerid = $userID";

    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $storageRequests[] = $row;
    }

    return $shipmentRequests;
  }
  /*
  * @pendingStep: 'approved', 'completed'
  */
  protected function _getItemsPending($ownerID=null, $type, $pendingStep=null, $requiredEmployeeID=null, $excludeEmployeeID=null){

    $itemsPending = [];
    $sql = "";

    //If pendingStep is not provided, then it is the final step, so approval is required
    $approvalRequired = is_null($pendingStep) ? 1 : 0;

    if ($type==='ship'){
      $pendingStep = is_null($pendingStep) ? 'shipped' : $pendingStep;

      $sql = "SELECT DISTINCT storageitem.id, storageitem.name, storageitem.ownerid, shipment.qty, shipment.confirmed_pulled_qty,
                    shipment.lotnumber,shipment.tag, shipment.notes, shipment.name as label,
                    shipment.date_created, shipment.date_approved, shipment.date_needed, shipment.id AS shipmentid,
                    shipment.userid_puller as userid_puller, shipment.userid_receiver";
      if ($pendingStep == 'shipped'){
        $sql .= ", storagepalletinventory.palletid AS palletid,
                storagepalletinventory.item_qty AS qty_in_stock, storagebin.name as binname, storagepallet.name as palletname";
      }
      $sql .= " FROM storageitem
                    INNER JOIN shipment
                    ON storageitem.id=shipment.itemid";
      if ($pendingStep == 'shipped'){
        $sql .= " RIGHT JOIN storagepalletinventory ON
        storagepalletinventory.shipment_request_id=shipment.id
        LEFT JOIN storagepallet ON storagepallet.id=storagepalletinventory.palletid
        LEFT JOIN storagebininventory ON storagebininventory.palletid = storagepalletinventory.palletid
        LEFT JOIN storagebin ON storagebininventory.binid=storagebin.id";
      }
      $sql .= " WHERE (shipment.date_$pendingStep IS NULL OR LOCATE('970', shipment.date_$pendingStep,1))";

      if ($approvalRequired){
        $sql .= " AND (shipment.date_approved IS NOT NULL";
        $sql .= " AND !LOCATE('970', shipment.date_approved,1))";
      }
      if (!is_null($requiredEmployeeID)){
        $sql .= " AND userid_puller = $requiredEmployeeID";
      }
      if (!is_null($excludeEmployeeID)){
        $sql .= " AND (userid_puller IS NULL OR userid_puller != $excludeEmployeeID)";
      }
      if (!is_null($ownerID)){
        $sql .= " AND storageitem.ownerid = $ownerID";
      }

      $sql .= " ORDER BY date_created DESC";
      //echo $sql;

    }
    else if ($type==='store'){
      $pendingStep = is_null($pendingStep) ? 'stored' : $pendingStep;
      $sql = "SELECT DISTINCT storageitem.id, storageitem.name, storage.qty,
                    storage.lotnumber,storage.tag, storage.notes, storage.name as label,
                    storage.date_created, storage.date_approved, storage.date_needed, storage.id AS storageid,
                    storagepalletinventory.item_qty, storagepalletinventory.confirmed, storagepallet.name AS palletname, storagepallet.id AS palletid,
                    storagepallet.name as palletname,
                    storagebin.name as binname, user.id as ownerid, user.displaycode,
                    storagebin.id as binid
                    FROM storageitem
                    INNER JOIN storage
                    ON storageitem.id=storage.itemid
                    LEFT JOIN storagepalletinventory
                    ON storagepalletinventory.storageid=storage.id
                    LEFT JOIN storagepallet
                    ON storagepallet.id = storagepalletinventory.palletid
                    LEFT JOIN storagebininventory
                    ON storagebininventory.palletid = storagepallet.id
                    LEFT JOIN storagebin
                    ON storagebin.id = storagebininventory.binid
                    INNER JOIN user ON storageitem.ownerid=user.id
                    WHERE (storage.date_" . $pendingStep . " IS NULL OR LOCATE('970', storage.date_$pendingStep,1))";

                    //final step, double check approval again
      if ($approvalRequired){
        $sql .= " AND (storage.date_approved IS NOT NULL";
        $sql .= " AND !LOCATE('970', storage.date_approved,1))";
      }

      if (!is_null($requiredEmployeeID)){
        $sql .= " AND storage.userid_stocker = $requiredEmployeeID";
      }
      if (!is_null($excludeEmployeeID)){
        $sql .= " AND (storage.userid_stocker IS NULL OR storage.userid_stocker != $excludeEmployeeID)";
      }

      if (!is_null($ownerID)){
        $sql .= " AND storageitem.ownerid = $ownerID";
      }

      $sql .= " ORDER BY date_created DESC";
    }


    $result = $this->handler->query($sql);

    //// TODO: why did it return duplicates in the first place?  DISTINCT is not working yet ...
    $itemsAdded = array();
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $id = $row['id'];
      if (!in_array($id, $itemsAdded)){
        $itemsAdded[]   = $id;
        $itemsPending[] = $row;
      }

    }
    return $itemsPending;
  }
  function _getEmployeeWorkItemHistory($employeeID, $type, $dateFilter){

    $history = array();

    if ($type=='ship'){
      $sql = "SELECT 'SHIP' as action, storageitem.name, shipment.name AS label, shipment.qty,
          shipment.lotnumber, shipment.tag, shipment.notes,
          shipment.date_created AS date_created, shipment.date_approved AS date_approved,
          shipment.date_shipped AS date_shipped,
          shipment.date_needed AS date_needed,
          shipment.id AS shippingid
          FROM shipment
          JOIN storageitem
          ON storageitem.id=shipment.itemid
          WHERE shipment.userid_puller=$employeeID AND
              TIMESTAMP(shipment.date_shipped, '0:00:00') > DATE_SUB(NOW(), INTERVAL $dateFilter)";

      $sql .= " ORDER BY shipment.date_shipped DESC";
      }
    else if ($type=='store'){
      $sql = "SELECT 'STORE' as action, storageitem.name, storage.name AS label, storage.qty,
          storage.lotnumber, storage.tag, storage.notes,
          storage.date_created AS date_created, storage.date_approved AS date_approved,
          storage.date_stored AS date_stored,
          storage.date_needed AS date_needed,
          storage.id AS storageid
          FROM storage
          JOIN storageitem
          ON storageitem.id=storage.itemid
          WHERE storage.userid_stocker=$employeeID AND
              TIMESTAMP(storage.date_stored, '0:00:00') > DATE_SUB(NOW(), INTERVAL $dateFilter)";
      $sql .= " ORDER BY storage.date_stored DESC";
    }

    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $history[] = $row;
    }
    return $history;
  }
  /*
  * Get history of ships and stores
  * $dateFilter: {N year ;N day,N min,N year} - adds to SQL QUERY to filter timestamp > filter
        default to 14 days
  */
  function _getItemHistory($userID, $type=null, $dateFilter){

    $history = array();

    if (null===$type || 'SHIP'===$type)
    {
      $sql = "SELECT 'SHIP' as action, storageitem.name, shipment.id AS requestid, shipment.name AS label, shipment.qty,
          shipment.lotnumber, shipment.tag, shipment.notes,
          shipment.date_created AS created, shipment.date_approved AS approved,
          IF(shipment.date_shipped<'1971', '', shipment.date_shipped) AS fulfilled
          FROM shipment
          JOIN storageitem
          ON storageitem.id=shipment.itemid
          WHERE shipment.userid_requestor=$userID AND TIMESTAMP(shipment.date_created, '0:00:00') > DATE_SUB(NOW(), INTERVAL $dateFilter)";
      $sql .= " ORDER BY created DESC";

      $result = $this->handler->query($sql);
      while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
        $history[] = $row;
      }
    }

    if (null===$type || 'STORE'===$type){
      $sql = "SELECT 'STORE' as action, storageitem.name, storage.id AS requestid, storage.name AS label, storage.qty,
          storage.lotnumber, storage.tag, storage.notes,
          storage.date_created AS created, storage.date_approved AS approved,
          IF(storage.date_stored<'1971', '', storage.date_stored) AS fulfilled
          FROM storage
          JOIN storageitem
          ON storageitem.id=storage.itemid
          WHERE storage.userid_requestor=$userID AND TIMESTAMP(storage.date_created, '0:00:00') > DATE_SUB(NOW(), INTERVAL $dateFilter)";

      $sql .= " ORDER BY created DESC";

      $result = $this->handler->query($sql);
      while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
        $history[] = $row;
      }
    }
    return $history;
  }

  /*
  * Get all users associated with a provider of which this user is an owner
  * If no user is provided, a full list of all clients are returned
  */
  function _getClients($userID=null){

    $clients = array();
    $sql = "SELECT user.id, CONCAT(user.firstname, ' ', lastname) AS name,
    user.emailaddress, CONCAT(user.city,', ', user.state) AS location,
    user.phonehome, user.phonemobile, user.phoneother
    FROM user JOIN client ON user.id=client.userid
    WHERE client.providerid IN ";


    $sql .= "(SELECT provider.id FROM user
          INNER JOIN provider ON provider.ownerid=user.id";

    if (!is_null($userID)){
          $sql .= " WHERE user.id=$userID";
    }
    $sql .= ")";
    Log::debug("Get Clients: $sql");


    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $clientID = $row['id'];
      $clients[$clientID] = $row;
    }
    return $clients;
  }

  /*
  * Get shipping receivers for a client
  */
  function _getReceivers($userID, $navOnly=false){


    $receivers = array();

    $sql = "SELECT user.id, CONCAT(user.firstname, ' ', user.lastname) AS name";

    if (!$navOnly){
        $sql .= ", user.companyname, user.emailaddress,
                  user.phonehome, user.phonemobile, user.phoneother,
                  user.city, user.state, user.zip, user.website";
                  }

    $sql .= " FROM user INNER JOIN receiver ON receiver.receiverid=user.id";

    $sql .= " WHERE receiver.receiverid IN (SELECT receiverid FROM receiver WHERE clientid=$userID) OR
                  receiver.clientid IN (SELECT userid FROM client WHERE providerid IN (SELECT providerid FROM providerowners WHERE userid=$userID
                      UNION
                      SELECT id FROM provider WHERE ownerid=$userID))";

  /*  $sql .= " WHERE receiver.clientid IN";
    $sql .= " (SELECT userid FROM client WHERE providerid IN";
    $sql .= " (SELECT providerid FROM providerowners WHERE userid=$userID
                      UNION
                      SELECT id FROM provider WHERE ownerid=$userID))";
    $sql .= " OR receiver.clientid IN";
    $sql .= " (SELECT receiverid FROM receiver WHERE clientid=$userID )";
*/

    //Log::debug("Get Receivers SQL: $sql");

    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $receivers[] = $row;
    }
    return $receivers;
  }
  /*
  * Get all users associated with a provider of which this user is an owner
  */
  function _getClientsForProvider($providerID){

    $clients = array();
    $sql = "SELECT user.id, CONCAT(user.firstname, ' ', lastname) AS name,
    user.emailaddress, CONCAT(user.city,', ', user.state) AS location
    FROM user JOIN client ON user.id=client.userid
    WHERE client.providerid = $providerID";

    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $clientID = $row['id'];
      $clients[$clientID] = $row;
    }
    return $clients;
  }
  /*
  * Return array of providerIDs for which this user is an employee
  */
  function _getProvidersForEmployee($workerID){

    $providerIDs = array();

    $sql = "SELECT provider.id FROM provider
            INNER JOIN storagefacilityworkers ON
            storagefacilityworkers.providerid = provider.id
            WHERE storagefacilityworkers.userid = $workerID";

    Log::debug($sql);
    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $providerIDs[] = $row['id'];
    }

    return $providerIDs;
  }
  function _getFacilitiesForEmployee($workerID){

    $facilities = null;
    $sql = "SELECT storagefacility.id, storagefacility.shortcode
            FROM storagefacility
            WHERE id IN (
            SELECT facilityid FROM storagefacilityproviders WHERE providerid IN (
            SELECT provider.id FROM provider
                    INNER JOIN storagefacilityworkers ON
                    storagefacilityworkers.providerid = provider.id
                    WHERE storagefacilityworkers.userid = $workerID)
                  )";

    $result = $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $facilities[] = $row;
    }

    return $facilities;
  }
  function _getProviderForClient($clientID){
    $sql = "SELECT providerid FROM client WHERE userid=$clientID";
    $result = $this->handler->query($sql);

    if($result){
      $row = $result->fetch(\PDO::FETCH_ASSOC);
      if ($row){
        return $row['providerid'];
      }
    }
    else{
      Log::error("Client has no provider for some reason, assocation needs to be fixed");
    }
  }
  function _getProviderNameForClient($clientID){

    $sql = "SELECT name FROM provider INNER JOIN client ON provider.id=client.providerid WHERE client.userid=$clientID";
    $result = $this->handler->query($sql);

    if($result){
      $providerRow =  $result->fetch(\PDO::FETCH_ASSOC);
      if ($providerRow){
        return $providerRow['name'];
      }
    }
    Log::error("Client has no provider for some reason, assocation needs to be fixed");
    return null;
  }
  /*
  * @return: employees['storagefacility;provider']['resourceid'][employeeID] = { name, location, email, recent transactions}
  */
  function _getEmployees($userID=null){

    $employees = array();

    //get employees of facilities this user owns

    $sql = $sqlbase = "SELECT user.id, CONCAT(user.firstname, ' ', lastname) AS name,
                  user.emailaddress, CONCAT(user.city,', ', user.state) AS location
            FROM user JOIN storagefacilityworkers ON user.id=storagefacilityworkers.userid";

    $sql .= " WHERE storagefacilityworkers.facilityid
            IN (SELECT storagefacilityowners.facilityid FROM user
              INNER JOIN storagefacilityowners ON storagefacilityowners.userid=user.id";

    if(!is_null($userID)){
            $sql .= " WHERE user.id=$userID";
          }
   $sql .= ")";

    Log::debug("Get Employees:  $sql");


    $result = $this->handler->query($sql);
    if ($result){
      while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
        $employeeID = $row['id'];
        $employees['storagefacility'][$employeeID] = $row;
      }
    }

    // get employees of providers this user owns
    $sql = $sqlbase . " WHERE storagefacilityworkers.providerid
            IN (SELECT provider.id FROM provider
            INNER JOIN user ON provider.ownerid=user.id";
    if (!is_null($userID)){
      $sql .= " WHERE user.id=$userID";
    }
      $sql .=")";

    $result = $this->handler->query($sql);
    if ($result){
      while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
        $employeeID = $row['id'];
        $employees['provider'][$employeeID] = $row;
      }
    }

    return $employees;
  }
  function _getStorageRequestInventory($storageitemID){

    $sql = "SELECT SUM(storagepalletinventory.item_qty) as current_qty
                  FROM storagepalletinventory
                  WHERE storagepalletinventory.storageid=$storageitemID AND
                  storagepalletinventory.confirmed=1";
    return $this->_genericSQL_Get($sql);
  }
  function _getProductStorageDetail($storageitemID){
    //Get count

    $sql = "SELECT storage.id, storagepalletinventory.lotnumber,
            storagepalletinventory.tag, storageitem.name as itemname,
            storagepallet.name, storagepallet.id, storagepalletinventory.item_qty
            FROM storagepalletinventory
            INNER JOIN storageitem ON
              storagepalletinventory.itemid=storageitem.id
            INNER JOIN storagepallet ON
              storagepalletinventory.palletid=storagepallet.id";
    $sql .= " WHERE storagepalletinventory.itemid=$storageItemID";

  }
  function _getStorageDetail($storageRequestID, $palletID=null){

    $sql = "SELECT storage.id, storage.name, storage.lotnumber,
            storage.tag, storageitem.name as itemname, storagepallet.name, storagepallet.id, storagepalletinventory.item_qty
            FROM storage
            INNER JOIN storageitem ON
            storage.itemid=storageitem.id
            INNER JOIN storagepalletinventory ON
            storagepalletinventory.storageid = storage.id
            INNER JOIN storagepallet ON storagepalletinventory.palletid=storagepallet.id";
    $sql .= " WHERE storage.id=$storageRequestID";

    if (!is_null($palletID)){
      $sql .= " AND storagepalletinventory.palletid=$palletID";
    }

    return $this->_genericSQL_Get($sql);

  }
  /*
  *  $userID can be provider OR employee
  */
  function _getPalletInventory($palletIDsArray, $userID, $lotnumber=null, $tag=null, $includeEmpty=false, $confirmedStorage=null){


    if (empty($palletIDsArray)){
      $palletIDs = "SELECT id FROM storagepallet WHERE providerid";
      $palletIDs .= " IN (SELECT id FROM provider WHERE provider.ownerid=$userID)";
      $palletIDs .= " OR providerid IN (SELECT providerid from storagefacilityworkers WHERE storagefacilityworkers.userid=$userID )";
    }
    else{
      Log::error("Pallet Inventory _getPalletInventory should not be passing in array of palletIDs!  ");
      $palletIDs = Util::Array2csv($palletIDsArray, ["ID"]);
    }

    $sql = "SELECT storage.id, storage.name, storage.lotnumber, storage.qty as request_qty,
            storage.tag, storageitem.name as itemname, storageitem.uom as uom, storageitem.ownerid as itemownerid,
            storageitem.id as itemid,
            storagepallet.name as palletname,
            storagepallet.id as palletid, storagepalletinventory.item_qty,
            storagebin.name AS binname,
            CONCAT(user.firstname, ' ', user.lastname) as clientname
            FROM storage
            INNER JOIN storageitem ON
            storage.itemid=storageitem.id
            INNER JOIN user ON
            user.id = storageitem.ownerid
            INNER JOIN storagepalletinventory ON
            storagepalletinventory.storageid = storage.id
            INNER JOIN storagepallet ON storagepalletinventory.palletid=storagepallet.id
            LEFT JOIN storagebininventory ON storagebininventory.palletid = storagepallet.id
            LEFT JOIN storagebin ON storagebininventory.binid = storagebin.id";

    $sql .= " WHERE storagepalletinventory.palletid IN ($palletIDs)";

    if (!$includeEmpty){
      $sql .= " AND storagepalletinventory.item_qty>0";
    }
    if (!is_null($lotnumber)){
      $sql .= " AND storage.lotnumber='$lotnumber'";
    }
    if (!is_null($tag)){
      $sql .= " AND storage.tag='$tag'";
    }
    if (!is_null($confirmedStorage)){
      $sql .= " AND confirmed=$confirmedStorage";
    }

    $sql .= " ORDER BY palletname ASC";

    Log::debug("=== Transaction:  $sql");

    return $this->_genericSQL_Get($sql);

  }

  function _getBinInventory($userID, $binIDsArray=null){

    if (!is_null($binIDsArray)){
      $binIDs = Util::Array2csv($binIDsArray, ["ID"]);

      $sql = "SELECT storagebin.id, storagebin.name as binname, storagepallet.name as palletname, storagepallet.id as palletid
              FROM storagebin
              LEFT JOIN storagebininventory ON storagebininventory.binid = storagebin.id
              LEFT JOIN storagepallet ON storagebininventory.palletid = storagepallet.id
              WHERE storagebin.id IN ($binIDs) ORDER BY binname ASC";
    }
    else{
      $sql = "SELECT storagebin.id, storagebin.name as binname, storagepallet.name as palletname, storagepallet.id as palletid
              FROM storagebin
              LEFT JOIN storagebininventory ON storagebininventory.binid = storagebin.id
              LEFT JOIN storagepallet ON storagebininventory.palletid = storagepallet.id
              WHERE storagebin.id IN (

                SELECT id FROM storagebin WHERE storagebin.providerid IN
                    (
                      SELECT providerid FROM providerowners WHERE userid=$userID
                      UNION
                      SELECT id FROM provider WHERE ownerid=$userID
                    )
              ) ORDER BY binname ASC";
    }

    return $this->_genericSQL_Get($sql);
  }
  function _getPalletsContainingItem($itemid, $lotnumber=null, $tag=null, $includeEmpty=false){

    $sql = "SELECT storagepallet.id AS palletid,
                  storagepallet.name AS palletname,
                  storagepallet.full AS full,
                  storage.name AS storagename,
                  storage.lotnumber AS lotnumber,
                  storage.tag AS tag,
                  storagepalletinventory.item_qty AS qty,
                  storagepalletinventory.storageid as storageid,
                  storageitem.id as itemid,
                  storageitem.name as itemname
            FROM storagepallet RIGHT JOIN storagepalletinventory ON
            storagepalletinventory.palletid = storagepallet.id
            LEFT JOIN storage ON storage.id=storagepalletinventory.storageid
            INNER JOIN storageitem ON storageitem.id=storage.itemid";

      $sql .= " WHERE storagepalletinventory.itemid=$itemid";

      if (!empty($lotnumber)){
        $sql .= " AND storagepalletinventory.lotnumber='$lotnumber'";
      }
      if (!is_null($tag)){
        $sql .= " AND storagepalletinventory.tag='$tag'";
      }
      if (!$includeEmpty){
        $sql .= " AND storagepalletinventory.item_qty>0";
      }
      Log::debug("Get Pallet Containing Item === ");
      Log::debug($sql);

      return $this->_genericSQL_Get($sql);

  }
  /*
  *  Return pallets in following preference:
  *    Already holding this item and not full
  *    Empty pallets in same bin
  *    Available bin
  */
  function _getPalletsAvailableForStorage($providerid, $includeFull=false, $itemid=null, $lotnumber=null, $onlyShowEmpty=true){

    $sql = "SELECT storagepallet.id AS palletid,
                  storagepallet.name AS palletname,
                  storagepallet.providerid as providerid,
                  storagepallet.full AS full,
                  storagepallet.repeatedbatch as repeatedbatch,
                  storage.name AS storagename,
                  storage.lotnumber AS lotnumber,
                  storageitem.name AS itemname,
                  inventory.item_qty AS qty,
                  storageitem.id AS itemid
            FROM storagepallet
	          LEFT JOIN storagepalletinventory as inventory ON inventory.palletid=storagepallet.id
            LEFT JOIN storage ON storage.id=inventory.storageid
            LEFT JOIN storageitem ON storageitem.id=storage.itemid
            WHERE providerid = $providerid AND storagepallet.usable=1";

            if (!$includeFull){
              $sql .= " AND storagepallet.full=0";
            }

            if (!empty($itemid)){
              $sql .= " OR storageitem.id=$itemid";
            }
            if (!empty($lotnumber)){
              $sql .= " OR storage.lotnumber='$lotnumber'";
            }
            if ($onlyShowEmpty){
              $sql .= " AND (
                (storage.qty IS NULL OR storage.qty<1) ||
                (storagepallet.repeatedbatch=1 AND storage.date_stored IS NULL)
              )";
            }//repeatedbatch

            return $this->_genericSQL_Get($sql);
  }
  function _approve($resource, $userID){

    $resourceID = $resource->ID;
    $tableName = get_class($resource)::$TableName;
    $currentTime = time();

    $sqlUpdate = "UPDATE $tableName SET userid_approver=$userID,
                          `date_approved`=FROM_UNIXTIME($currentTime)
                          WHERE id=$resourceID";

    if ($this->handler->query($sqlUpdate)){
      Log::debug("$resource->Name approval [$resourceID] updated successfuly by userID=$userID");
    }
    else{
      Log::error("ERROR while trying to approve $resource->Name [$resourceID] for user $userID  SQL:  $sqlUpdate");
    }
  }
  function _claim($resource, $userID, $fieldSuffix){
    $currentTime = time();
    $tableName = get_class($resource)::$TableName;
    $resourceID = $resource->ID;
    $sqlUpdate = "UPDATE $tableName SET userid_$fieldSuffix = $userID WHERE id=$resourceID";

    if ($this->handler->query($sqlUpdate)){
      Log::debug("$tableName $fieldSuffix resource $resourceID CLAIMED successfuly by userID=$userID");
    }
    else{
      Log::error("ERROR while trying to CLAIM resource $resource->ID for user $userID  SQL:  $sqlUpdate");
    }
  }
  function _closeStorage($storageID, $stockerID, $qty, $notes=null){

    $currentTime = time();

    $sqlUpdate = "UPDATE storage SET
                          date_stored=FROM_UNIXTIME($currentTime),
                          userid_stocker = $stockerID,
                          notes = CONCAT(notes,'$notes')
                          WHERE storage.id=$storageID";


   //Remove pallet associations that are NOT confirmed.
   //  This occurs when recommended pallets were not used

   Log::debug("===== STORAGE CLOSED SQL $sqlUpdate");
   return $this->handler->query($sqlUpdate);

  }
  /*
  *   Mark a shipment item as shipped, all pallets pulled
  */
  function _shipSetAsShipped($resource, $userID=null){

    if (is_null($userID)){
      $userID = SessionManager::GetCurrentUserID();
    }
    $currentTime = time();
    $tableName = get_class($resource)::$TableName;
    $sqlUpdate = "UPDATE $tableName SET
                          date_shipped=FROM_UNIXTIME($currentTime),
                          userid_puller = $userID
                          WHERE $tableName.id=$resource->ID";


   return $this->handler->query($sqlUpdate);

  }
  /*
  * Update Qty AND confirmed flag
  */
  function _addPalletStorageItemQty($palletID, $storageID, $qty, $lotnumber, $storageItemID, $tag){

    $sqlUpdate = "INSERT INTO storagepalletinventory
                      (confirmed, itemid, item_qty, lotnumber, palletid, storageid, tag)
                      VALUES (1, $storageItemID, $qty,
                                  '$lotnumber',
                                  $palletID,
                                  $storageID,
                                  '$tag'
                                )
                      ON DUPLICATE KEY
                      UPDATE item_qty = IF (NOT confirmed, 1, item_qty + $qty),
                      confirmed=1";

    Log::debug("SET PALLET STORAGE ITEM QTY: $sqlUpdate");
    return $this->handler->query($sqlUpdate);
  }

  /*
  *   remove items from a pallet's storage request item
  *   Get list of all storageitems on this pallet flagged with matching content
  *    Iterate these inventories, removing quantities until no more remains
  *
  *   If some remains, then this pallet DID NOT have enough, and that is ok
  *
  *@param qtyToPull
  */
  function _pullItemsFromPallet($palletID, $qtyToPull, $shippingRequestID,$lotnumber=null, $tag=null){

    $qtyRemaining = $qtyToPull;
    //Get each pallet that has at least the amount of storage requesting
    $sqlSelect = "SELECT storageid, item_qty FROM storagepalletinventory
                  WHERE palletid = $palletID
                  AND shipment_request_id = $shippingRequestID";

    $storageInstances = $this->handler->query($sqlSelect);

    //Look at each inventory pair that has been flagged for pull
    //  Update qtys until no more remains

    foreach ($storageInstances as $storageOnPallet) {
      $storageID = $storageOnPallet['storageid'];

      if ($qtyRemaining>0){
        $pullQty = $storageOnPallet["item_qty"];//assume pulling it all
        if ($pullQty >= $qtyRemaining){
            $pullQty = $qtyRemaining;//just pull what you need
        }

        $sqlUpdate = "UPDATE storagepalletinventory
                      SET item_qty = item_qty - $pullQty
                      WHERE storagepalletinventory.palletid = $palletID AND
                      storagepalletinventory.storageid = $storageID";
        Log::debug("===== SQL PULL PALLET QTY: $sqlUpdate");
        $result = $this->handler->query($sqlUpdate);
        if ($result){
          $qtyRemaining -= $pullQty;
        }
      }
    }

    //Now increment the confirmed pull for this shipment
    $sqlUpdateConfirmed = "UPDATE shipment SET confirmed_pulled_qty = confirmed_pulled_qty + $qtyToPull WHERE shipment.id=$shippingRequestID";
    Log::debug($sqlUpdateConfirmed);
    $result = $this->handler->query($sqlUpdateConfirmed);
    if (!$result){
      Log::error(" SHIPPING error while updating confirmed_pulled_qty for shipment:  sql: $sqlUpdateConfirmed");
    }
    return true;
  }
  function _getUnstoredItemsForStorageRequest($storageRequestID){

    $records = array();
    $sql = "SELECT SUM(item_qty) as num FROM storagepalletinventory
    WHERE storageid=$storageRequestID
    AND confirmed=0";

    $result =  $this->handler->query($sql);
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)){
      $records[] = $row;
    }

    return $records[0]['num'];

  }
  function _assignPalletForPull($shipmentID, $palletIDsCSV, $lotnumber=null, $tag=null){

    $sql = "UPDATE storagepalletinventory SET shipment_request_id=$shipmentID
            WHERE storagepalletinventory.palletid IN ($palletIDsCSV)";
    Log::debug($sql);
    return $this->handler->query($sql);
  }
  function _getPalletByName($palletName, $ownerID=null){

    $sql = "SELECT * FROM storagepallet";
    if (!is_null($ownerID)){
      $sql .= " INNER JOIN provider ON provider.ID = storagepallet.ownerID";
      $sql .= " LEFT JOIN user ON user.id=provider.ownerid";
    }

    $sql .= " WHERE name='$palletName'";

    Log::debug($sql);

    return $this->_genericSQL_Get($sql);
  }
  function _searchForItemByName($searchString, $ownerID=null){

    $sqlFind = "SELECT id, name FROM storageitem
                WHERE name LIKE('%$searchString%')";

    if (!is_null($ownerID)){
      $sqlFind .= " AND ownerid=$ownerID";
    }

    return $this->_genericSQL_Get($sqlFind);

  }
  function _transactionAdd($type, $userid, $clientid=null, $receiverid=null,
                            $itemid=null, $providerid=null,
                            $palletid=null, $binid=null,
                            $notes=null){

    if (!is_null($notes)){
      $notes = addslashes($notes);
    }
    if (is_null($userid)){
      $userid = 'NULL';
    }
    if (is_null($receiverid) || $receiverid===''){
      $receiverid = 'NULL';
    }
    if (empty($clientid) || $clientid===''){
      $clientid = 'NULL';
    }
    if (empty($itemid) || $itemid===''){
      $itemid = 'NULL';
    }
    if (is_null($providerid) || $providerid===''){
      $providerid = 'NULL';
    }
    if (is_null($palletid) || $palletid===''){
      $palletid = 'NULL';
    }
    if (is_null($binid)){
      $binid = 'NULL';
    }

    $sql = "INSERT INTO transactions (`type`, `userid`, `clientid`, `receiverid`, `itemid`, `providerid`, `palletid`, `binid`, `notes`)";
    $sql .= " VALUES ('$type', $userid, $clientid, $receiverid, $itemid, $providerid, $palletid, $binid, '$notes')";
    Log::debug("TRANSACTION SQL $sql");
    $statement = $this->handler->query($sql);

    return $statement->rowCount() > 0;

  }
  function _getmostRecentShippedDateForItem($itemID){
    $sql = "SELECT IFNULL(shipment.date_shipped,'pending') AS date_shipped FROM shipment WHERE itemid=$itemID ORDER BY shipment.date_shipped DESC";
    return $this->_genericSQL_Get($sql);
  }
  function _getmostRecentStorageDateForItem($itemID){
    $sql = "SELECT IFNULL(storage.date_stored,'pending') AS date_stored FROM storage WHERE itemid=$itemID ORDER BY storage.date_stored DESC";
    $result =  $this->_genericSQL_Get($sql);

    return $result;
  }



  //// TODO: get rid of this generic method

  function _genericSQL_Get($sql){

    $records = array();
    $statement = $this->handler->query($sql);

    return $statement->fetchAll(\PDO::FETCH_ASSOC);
  }
  function _genericSQL_update($sql){

    $this->handler->query($sql);
    return $this->handler->affected_rows>0;
  }
  function _genericSQL_delete($sql){

    $this->handler->query($sql);
    return $this->handler->affected_rows>0;
  }
  function _genericSQL_execute($sql){
    try {
      $this->handler->query($sql);
      return $this->handler->affected_rows > 0 ? $this->handler->affected_rows : true;
    } catch (\Exception $e) {
      error_log("MYSQL ERROR " . $e->getMessage());
      return false;
    }



  }

}
