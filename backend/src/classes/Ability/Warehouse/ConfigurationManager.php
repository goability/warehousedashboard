<?php
/*
* ConfigurationManager
*   - Load configuration items from statically configured file
*/
namespace Ability\Warehouse;

class ConfigurationManager{

  //Singleton Instance of the Configuration object
  private static $_configuration = null;

  // TODO: pull from JSON config
  public static $NavTopStaticConfigItems = array (
                        "Signup" => array("displayText" => "Join",
                                          "url" => "/Signup",
                                          "classes"       => "fa fa-join",
                                          "resourceName" => ""),
                        "Login" => array( "displayText" => "Login",
                                          "url" => "/Login",
                                          "classes"       => "fa fa-sign-in",
                                          "resourceName" => "")
                     );

  /*
    Static constructor
    Load the configuration and resources into this instance.

  */
  public static function LoadStaticConfigurations(){

    if ( is_null(self::$_configuration)){

      if (!file_exists(CONFIG_DIR)){
        echo "ERROR WITH SYSTEM CONFIGURATION [$configDirectory]";
        die();
      }

      //TODO this is loaded with every site call, need to cache

      self::$_configuration = new Configuration(CONFIG_DIR);
      self::$_configuration->LoadResourceConfigItem("User");//this one is always loaded
    }
  }
  /*
  * Returns true if there is more than one resource
  */
  public static function HasResources(){
    return  !is_null(self::$_configuration)           &&
            !empty(self::$_configuration->Resources)  &&
            count(self::$_configuration->Resources) > 1;//User is already there
  }
  /*
    Return a configuration resource item, and also load it statically so
    that it can be used again in the flow if needed
  */
  public static function GetResourceConfig($name, $forceReload=false){

      if (!isset(self::$_configuration->Resources[$name]) || $forceReload){
        self::$_configuration->LoadResourceConfigItem($name);
      }
      return isset(self::$_configuration->Resources[$name]) ?
                  self::$_configuration->Resources[$name] : [] ;
  }
  public static function GetResourceConfigParameter($resourceName, $parameterName){
    if (isset(self::$_configuration->Resources[$resourceName]) &&
        isset(self::$_configuration->Resources[$resourceName][$parameterName])){
          return self::$_configuration->Resources[$resourceName][$parameterName];
    }
    else{
      return null;
    }
  }
  /*
  *  Get a string array of currently loaded resources OR null
  *
  */
  public static function GetLoadedResourceNames(){
    return self::$_configuration->ResourceNames;
  }
  /*
  * Get an string array of resourceNames that should always be added, such as Storageitem
  */
  public static function IsOnlyVisibleToAdmin($resourceName){
    return in_array($resourceName, self::$_configuration->RestrictedResources);
  }
  /*
  * Load from disk all of the active resource .json files
  */
  public static function LoadAllResourceConfigs(){
      self::LoadStaticConfigurations();
      self::$_configuration->LoadAllResourceConfigs();
  }
  /*
  * Loads all from disk and returns it
  */
  public static function GetConfigurationObject(){
    if (empty(self::$_configuration)){
      self::LoadAllResourceConfigs();
    }
    return self::$_configuration;
  }
  /*
  * Get a count of loaded resources
  */
  public static function GetLoadedResourceCount(){
    return count(self::$_configuration->Resources);
  }
  /*
  * For a configured resource, return the field that holds the
  *   foreign resourceid that owns this resource
  */
  public static function GetOwnedByFieldName($resourceName){
    return self::$_configuration->_getOwnedByFieldName($resourceName);
  }
  /*
  * For a configured resource, return the fieldname that holds the
  *   resource name owns this resource (Provider, User, ...)
  */
  public static function GetOwnedByResourceName($resourceName){
    return self::$_configuration->_getOwnedByFieldName($resourceName);
  }

  public static function GetTableName($resourceName){
    return self::$_configuration->_getTableName($resourceName);
  }
  /*
  *  Get the name of the User table (from configuration)
  */
  public static function GetUserTableName(){
    return is_null(self::$_configuration) ?
                                  null :
                                  self::$_configuration->UserResourceTableName;
  }
  /*
  *  Given a resource, return the table.fieldName that can hold instances of this type
  *
  * @param: $resourceName  name of the resource
  * @returns: array of table.fieldnames, keyed by the associated resourceName
  *
  *  i.e.  $resource = 'Storageitem' will return --> [Storagecontainer] = "storagepalletinventory.itemid"
  */
  public static function GetAssociationFieldNamesForResource($resourceName, $associativeCollectionName){


    $associationConfigData             = new \stdClass;
    $associativeCollectionConfig    = self::GetResourceConfigParameter($resourceName, "associativeCollections")[$associativeCollectionName];


    // NOTE:
    //Grab the tableName for this lookup table and the two fields needed
    //  :: coOwnerResourceTableName         - providerowners
    //  :: coOwnerResourceFieldName         - providerid
    //  :: coOwnerResourceOwnedByFieldName  - userid

    // "LinkedFieldName" : "providerowners.userid"

    // First grab the association table and ownershipField
    //  example: "associativeKeyField": "providerowners.providerid",
    $coOwnerAssociativeKeyField   = $associativeCollectionConfig["associativeKeyField"];
    $associativeTableData         = explode(".", $coOwnerAssociativeKeyField);
    $coOwnerResourceTableName     = $associativeTableData[0];
    $coOwnerResourceFieldName     = $associativeTableData[1];

    //Now grab the owned by field, always a user
    // "LinkedFieldName" : "providerowners.userid",
    $coOwnerResourceOwnedByField          = $associativeCollectionConfig["associationObjects"]["User"]["LinkedFieldName"];
    $coOwnerResourceOwnedByFieldNameData  = explode(".", $coOwnerResourceOwnedByField);
    $coOwnerResourceOwnedByFieldName      = $coOwnerResourceOwnedByFieldNameData[1];

    $associationConfigData->coOwnerResourceTableName        = $coOwnerResourceTableName;
    $associationConfigData->coOwnerResourceFieldName        = $coOwnerResourceFieldName;
    $associationConfigData->coOwnerResourceOwnedByFieldName = $coOwnerResourceOwnedByFieldName;

    return $associationConfigData;
  }

  public static function GetParameter($parameterName){

    if (empty(self::$_configuration)){
      self::LoadAllResourceConfigs();
    }

    return property_exists(self::$_configuration->ConfigData, $parameterName) ?
          self::$_configuration->ConfigData->{$parameterName} : null;

  }

  public static function IsResourceCoOwned($resourceName){
      return !is_null(self::GetResourceConfigParameter($resourceName, "coownerAssociativeCollectionName"));
  }

  public static function GetReportConfig($reportID){

    $reportConfigData = null;
    foreach (self::$_configuration->ConfigData->reports as $report) {

      if ($report->reportid==$reportID){
        $reportConfigData['title']                = $report->title;
        $reportConfigData['isGrouped']            = $report->isGrouped;
        $reportConfigData['groupByFieldName']     = isset($report->groupByFieldName) ? $report->groupByFieldName : null;
        $reportConfigData['col_headers']          = array();
        foreach ($report->row_detail as $colhead) {
          $reportConfigData['col_headers'][] = [$colhead->col_head, $colhead->col_data];
        }
      }
    }
    return $reportConfigData;
  }

  public static function GetDatabaseType(){
      $config = self::GetParameter(NAME_SPACE . "\\Constants\\ConfigurationParameterNames::DATABASE");
      return $config->type;
  }
}
