<?php
namespace Ability\Warehouse;

class Storagelocation extends ResourceBaseType
{
  public static $ResourceName = null;
  public static $TableName    = null;
  public static $DisplayName  = null;
  public static $FormTitle    = null;
  public static $IndexFieldName = null;
  public static $OrderByFieldName  = null;
  public static $OrderByDirection  = null;
  public static $OwnedByFieldName = null;
  public static $OwnedByResourceName = null;

  function __construct($ID=null){
      parent::__construct($ID);
  }

  public function GetSelectOptionItemText($record)
  {
    return $record["row"] . $record["col"] . $record["shelf"];
  }

  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }

  public function GetNewInstance()
  {
    return new Storagelocation(0);
  }
}
