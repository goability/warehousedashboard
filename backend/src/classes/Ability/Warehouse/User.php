<?php
namespace Ability\Warehouse;

class User extends ResourceBaseType
{
  public static $ResourceName         = null;
  public static $TableName            = null;
  public static $DisplayName          = null;
  public static $FormTitle            = null;
  public static $IndexFieldName       = null;
  public static $OrderByFieldName     = null;
  public static $OrderByDirection     = null;
  public static $OwnedByFieldName     = null;
  public static $OwnedByResourceName  = null;
  public static $ResourceOwnerships   = null;

  /*
    If true will have full access
  */
  public $IsAdministrator = false;
  public $IsClient        = false;
  public $IsProvider      = false;

  public $ExtraProfileHeader = "";


  function __construct($ID=null){

      parent::__construct($ID);

      $this->_formPath = "forms/formUser.php";
      if (!empty($ID)){

        $this->IsAdministrator = DataProvider::IsUserAdmin($ID);
        $this->IsClient        = DataProvider::IsClient($ID);
        $this->IsProvider      = DataProvider::IsProvider($ID);

        if ($this->IsClient){

          $providerID               = DataProvider::GetProviderForClient($ID);
          $provider                 = new Provider($providerID);
          $providerName             = $provider->GetDisplayText();
          $this->ExtraProfileHeader = "($providerName)";
        }
      }
  }

  public function DeleteRecord($id){
    $userID       = SessionManager::GetCurrentUserID();
    $name         = $this->GetDisplayText();
    $success      = parent::DeleteRecord($id)==0;
    $deleter      = new User($userID);
    $deleterName  = $deleter->GetDisplayText();
    $notes    = "$name, ID=[$id] deleted by user [$deleterName], id=[$userID]";

    if (!$success){
      Log::Error("Delete failed for User [$name] userID=[$id].  Attempted by [$deleterName] ID = $userID");
    }
    else{
      Transaction::UserDelete($userID, $notes);
    }
    return $success;
  }
  public function InsertRecord($fieldData){

    $name       = $fieldData['emailaddress'];
    $defaultPassword = ConfigurationManager::GetParameter("DefaultPassword");
    $userDefaultPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // HACK: config holds a non-visible field for the password-hash, and is not sent to client
    //    better design would be how to sync mysql encrption with same one as php so you can set
    //    default in DB

    $fieldData['upasswd']       = $userDefaultPassword;
    $this->DB_Fields['upasswd'] = $userDefaultPassword;
    $newUserID  = parent::InsertRecord($fieldData);
    $success    = $newUserID > 0;

    if (!$success){
      Log::Error("Add new User failed for [$name]");
    }
    else{
      Transaction::UserAdd($this->ID, $name);
    }
    return $success;
  }
  public function UpdateRecord($fieldData){
    $success = !(parent::UpdateRecord($fieldData));

    $userid = $this->ID;
    $name   = $this->GetDisplayText();
    if (!$success){
      Log::error("Error updating record [$success] for user [$name] user ID = [$userid]");
    }
    else{
      Transaction::UserUpdate($userid, $name);
    }
    return $success;
  }
  public function GetSelectOptionItemText($record)
  {
    return $record["lastname"] . ", " . $record["firstname"];
  }
  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }
  public function GetListItemText($callingResource=null)
  {
    return $this->DB_Fields["firstname"] . " " . $this->DB_Fields["lastname"];
  }
  public function GetDisplayText(){
    return $this->GetListItemText();
  }
  public function GetNewInstance()
  {
    return new User(0);
  }
  /*
  * Get Resources this user has access to.
          If not present already in user properties OR if a force-reload is requested,
          a requery will be triggered, which builds it from the Database

  */
  public function GetResourcesForUser(){
    return DataProvider::GET_RESOURCES($this->ID);
  }
  /* Show an HTML form, ready to post to static::$ResourceName (User, StorageItem, ...)
  */
  public function ShowFormNavigationSelect()
  {
    //If they are an admin, only then can they see all users and edit them
    if (SessionManager::IsAdministrator()){
      return parent::ShowFormNavigationSelect();
    }
  }
  //---------------------------
  // Static Methods
  //---------------------------
  /*
  *      -- SetupResourceOwnershipFields()
  *
  *
  *  Traverse all loaded resources and add entries necessary to locate
  *    foreign objects that are owned by a User Resource.  These parameters
  *    will be referenced when building the SQL PREPARE, following a common format:
  *
  *  CONSTRAINTS - 'id' and 'name' field must exist in all Non-User resources
  *          TODO: move that to config
  *
  *  ::: COMMON PREPARE SQL FOR SELECT :::
  *
  *  SELECT id,name FROM {resource->tableName}
  *                 WHERE {resource->tableName}.{resource->ownedByFieldName} IN
  *                 (SELECT id FROM {ownedByResourceName->ownerForeignTableName}
  *                    WHERE {ownedByResourceName->ownerForeignTableName}.{ownedByResourceName->ownerForeignTableName->ownerForeignFieldName}=360)
  *
  *  Singleton is enforced at static level, so this should only be called once
  *
  *  Foreach $resource
  *   1.) If ownedByResourceName==User, add this resource config tablename, and foreign fieldname
  *   2.) Else, load the ownedByResourceName:
  *          Recursively call 1.) passing in
  *
  *  Scenario 1:  StorageItem (owned directly and soley by a User)
  *
  *     1.) owned by resource is User: so set the data
  *
  *         Resources['StorageItem']->ownerForeignTableName = user
  *         Resources['StorageItem']->ownerForeignFieldName = id
  *         Resources['StorageItem']->ownerLocalFieldName   = 'ownerID'
  *
  *  SQL will be:   SELECT id, name FROM storageitem
  *                 WHERE storageitem.ownerID (ownerLocalfieldName) IN
  *                 (SELECT id FROM user(ownerForeignTableName)
                    WHERE user(ownerForeignTableName).id(ownerForeignFieldName)=360)
  *
  *
  *
  *   Scenario 2:  Storagepallet
  *
  *     1.) NOT owned by a User, it is a provider
  *     2.) Load Provider resource and repeat
  *     3.) Provider is indeed owned by a User, so return all providers owned by current user
  *     4.)  back in Storagepallet, use those ownership flags
  *
  *         Resources['Storagepallet']->ownerForeignTableName = provider
  *         Resources['Storagepallet']->ownerForeignFieldName = ownerid
  *         Resources['Storagepallet']->ownerLocalfieldName   = ownerid
  *
  *
  *  SQL will be:   SELECT id,name FROM storagepallet
  *                 WHERE storagepallet.providerid(ownerLocalfieldName) IN
  *                 (SELECT id FROM provider(ownerForeignTableName)
  *                    WHERE provider(ownerForeignTableName).ownerid(ownerForeignFieldName)=360)
  *
  *
  *  SQL PREPARE:
  *     SELECT id,name FROM storagepallet
  *                 WHERE storagepallet.providerid(ownerLocalfieldName) IN
  *                 (SELECT id FROM provider(ownerForeignTableName)
  *                    WHERE provider(ownerForeignTableName).ownerid(ownerForeignFieldName)=360)
  *
  *
  *   This is ONLY a function of the User object, because ultimately that is what drive Access
  *
  *  @returns:
  */
  public static function SetupResourceOwnershipFields(){

    if (is_null(self::$ResourceOwnerships)){


      $loadedResources = SessionManager::GetAccessibleResourceNames();
      $loadedResources[] = "User";//add this in manually.

      //For each resource, goal is to grab the parameters needed to complete the SQL
      //  used when selecting types of that resource.
      //
      // ownerLocalfieldName - Provider.ownerid, Storageitem.ownerid,
      foreach ($loadedResources as $resourceName) {
          $fqResourceName = NAME_SPACE."\\".$resourceName;

          $resourceTableName                = $fqResourceName::$TableName;//used only for logging below
          $ownedByResourceName              = $fqResourceName::$OwnedByResourceName;
          $resourceOwnedByFieldName         = $fqResourceName::$OwnedByFieldName;
          $fqo = NAME_SPACE."\\".$ownedByResourceName;
          $ownerResourceTableName           = $fqo::$TableName;
          $ownerResourceOwnedByFieldName    = $fqo::$OwnedByFieldName;
          $ownerResourceIndexFieldName      = $fqo::$IndexFieldName;
          $coOwnerCollectionName =
            ConfigurationManager::GetResourceConfigParameter($resourceName,
                                          'coownerAssociativeCollectionName');;

          $coOwnerResourceTableName         = null;
          $coOwnerResourceFieldName         = null;
          $coOwnerResourceOwnedByFieldName  = null;

          if (!is_null($coOwnerCollectionName)){

              $associationConfigData            = ConfigurationManager::GetAssociationFieldNamesForResource($resourceName, $coOwnerCollectionName);
              $coOwnerResourceTableName         = $associationConfigData->coOwnerResourceTableName;
              $coOwnerResourceFieldName         = $associationConfigData->coOwnerResourceFieldName;
              $coOwnerResourceOwnedByFieldName  = $associationConfigData->coOwnerResourceOwnedByFieldName;


          }
          self::$ResourceOwnerships[$resourceName] =
                      new ResourceOwnershipInfo($resourceOwnedByFieldName,
                                                $ownerResourceTableName,
                                                $ownerResourceOwnedByFieldName,
                                                $ownerResourceIndexFieldName,
                                                $coOwnerResourceTableName,
                                                $coOwnerResourceFieldName,
                                                $coOwnerResourceOwnedByFieldName);

      }
    }
  }
}
