<?php
namespace Ability\Warehouse;

class Storagefacility extends ResourceBaseType
{
  public static $ResourceName = null;
  public static $TableName    = null;
  public static $DisplayName  = null;
  public static $FormTitle    = null;
  public static $IndexFieldName = null;
  public static $OwnedByFieldName = null;
  public static $OwnedByResourceName = null;
  public static $OrderByFieldName  = null;
  public static $OrderByDirection  = null;

  function __construct($ID=null){
        parent::__construct($ID);
  }
  public function GetSelectOptionItemText($record)
  {
    return $record["name"];
  }
  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }
  public function GetNewInstance()
  {
    return new Storagefacility(0);
  }
}
