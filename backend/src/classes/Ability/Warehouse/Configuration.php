<?php

namespace Ability\Warehouse;

class Configuration{

  private $_filePathDirectory;

  public $ConfigData;
  public $ResourceNames = array();
  public $RestrictedResources = array();
  public $Resources = array();
  public $UserResourceTableName = 'user';

  /*
  * Load the site configuration object, which has the requested resource Names
  */
  public function __construct($filePathDirectory){


      $this->_filePathDirectory = $filePathDirectory;
      $jsonString = file_get_contents(  $filePathDirectory .
                                                        DIRECTORY_SEPARATOR .
                                                        "SiteConfiguration.json"
                                                  );
      $this->ConfigData = json_decode($jsonString);

      if (null===$this->ConfigData){
        error_log("No Configuration data");
        echo "SYSTEM CONFIGURATION ERROR";
        die();
      }
      else{
        foreach ($this->ConfigData->activeResources as $resourceName) {
          $this->ResourceNames[] = $resourceName;
        }
        foreach ($this->ConfigData->onlyShowForAdmin as $resourceName) {
          $this->RestrictedResources[] = $resourceName;
        }
      }
  }

  /*
  * Get the JSON Resource definition for a resource
  * @param: $name - name of the resource (As defined in the config)
  * @returns:  nothing, this is a setter
  */
  public function LoadResourceConfigItem($name)
  {
    //Load the json config file for this object
    // TODO: LOOK CLOSER at reading this each time, memcache, etc ? for file ?
    //     for now, just stick in static array to reduce reloading during one page load at least

    $pathToFile = $this->_filePathDirectory .
                  DIRECTORY_SEPARATOR . "resources" .
                  DIRECTORY_SEPARATOR .  "resource_$name.json";
    if (!file_exists($pathToFile)){
      $err = "SETUP ERROR - Configuration File did not exist for $name.  Path:  $pathToFile";
      error_log($err);
      echo $err;
      die();
    }

    $jsonString = file_get_contents($pathToFile);
    $this->Resources[$name]  = json_decode($jsonString, true);
    $url = $this->Resources[$name]['navigationMenuURL'];

    if ($name==='User')
    {
      $this->UserResourceTableName = $tablenameUser = $this->Resources[$name]['tableName'];
    }

    $fqClassName = "\\Ability\\Warehouse\\" . $name;

    try {
      $fqClassName::StaticConstructor($name);
    } catch (\Exception $e) {
        die(" FATAL failed call [$fqClassName::StaticConstructor] for class [$fqClassName]");
    }
  }
  public function _getOwnedByFieldName($resourceName){
    return isset($this->Resources[$resourceName]["ownedByFieldName"]) ?
      $this->Resources[$resourceName]["ownedByFieldName"] : null;
  }
  public function _getTableName($resourceName){
    return isset($this->Resources[$resourceName]["tableName"]) ?
      $this->Resources[$resourceName]["tableName"] : null;
  }
  public function _getResourceConfigParameter($resourceName, $parameterName){
    return isset($this->Resources[$resourceName][$parameterName]) ?
      $this->Resources[$resourceName][$parameterName] : null;
  }
  /*
  * For all of the previously loaded ResourceNames, Load the resource config
  */
  public function LoadAllResourceConfigs(){

    foreach ($this->ResourceNames as $resourceName) {
      $this->LoadResourceConfigItem($resourceName);
    }
    //All resource classes are loaded, now harvest the parames needed for ownership of other resources

    User::SetupResourceOwnershipFields();//static singleton

  }
  public function GetLoadedResourceCount(){
    return count($Resources) > 1;
  }
}
