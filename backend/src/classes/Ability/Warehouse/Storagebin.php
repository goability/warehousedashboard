<?php
namespace Ability\Warehouse;

class Storagebin extends ResourceBaseType
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
      $this->_formPath = "forms/formStoragebin.php";
  }
  function InsertRecord($fieldData){

    $userID       = SessionManager::GetCurrentUserID();
    $newBinID     = parent::InsertRecord($fieldData);

    if ($newBinID>0){
      $newBin       = new Storagebin($newBinID);
      $newBinName   = $newBin->GetField('name');
      $providerID   = $this->GetField('providerid');

      Transaction::BinAdd($userID, $newBinID, $newBinName, $providerID);
    }
    else{
      Log::error("Bin was not created");
    }
    return $newBinID;
  }

  function DeleteRecord($id){
    $userID   = SessionManager::GetCurrentUserID();
    $name     = $this->GetField('name');
    $success  = parent::DeleteRecord($id)==0;
    $providerID = $this->GetField('providerid');

    if (!$success){
      Log::Error("Delete failed for Bin [$id]");
    } else {
      Transaction::BinDelete($userID, $id, $name, $providerID);
    }
    return $success;
  }
  public function GetRecordDeleteElement(){

    $providerid = $this->GetField('providerid');

    $el  = "<input type=\"hidden\" name=\"providerid\" value=$providerid>";
    $el .= "<input onclick=\"return confirm('Confirm delete?')\" type=\"submit\" name=\"delete\" value=\"delete\" id=\"record-delete\">";

    return $el;
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
    return new Storagebin(0);
  }
  public function GetDisplayText(){

    $displayName = $this->DB_Fields["name"];

    if (!empty($this->GetField('full')) && $this->GetField('full')){
      $displayName .= " FULL ";
    }
    return $displayName;
  }
}
