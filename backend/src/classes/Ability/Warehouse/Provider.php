<?php
namespace Ability\Warehouse;

class Provider extends ResourceBaseType
{
  public static $ResourceName   = null;
  public static $TableName      = null;
  public static $DisplayName    = null;
  public static $FormTitle      = null;
  public static $IndexFieldName = null;
  public static $OwnedByFieldName = null;
  public static $OwnedByResourceName = null;
  public static $OrderByFieldName  = null;
  public static $OrderByDirection  = null;

  // Construct an object populated from
  // @param $ID = Int - DB Record.ID or null for new record
  function __construct($ID=null){
      parent::__construct($ID);
      $this->_formPath = "forms/formProvider.php";
  }

  /*
  *  GetSelectOptionItemText
  *   - Given an db results array, built a select optin line item
  *
  */
  public function GetSelectOptionItemText($record)
  {
    return $record["name"];
  }
  /*
    GetSelectListItemText
     - Given an db results array, built a list optin line item
  *
  */
  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }
  /*
    return new instance of this object
  */
  public function GetNewInstance()
  {
    return new Provider(0);
  }

  public function GetLogoPath(){
    return "images/resources/Provider/".$this->ID."/".$this->GetField('logoFileName');
  }
}
