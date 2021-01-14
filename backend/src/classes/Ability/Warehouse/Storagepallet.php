<?php
namespace Ability\Warehouse;

class Storagepallet extends ResourceBaseType
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
      $this->_formPath = "forms/formStoragepallet.php";
  }

  function InsertRecord($fieldData){

    $userID       = SessionManager::GetCurrentUserID();
    $newPalletID  = parent::InsertRecord($fieldData);
    if ($newPalletID>0){
      $newPallet    = new Storagepallet($newPalletID);
      $newpalletName = $newPallet->GetField('name');
      $providerID   = $this->GetField('providerid');

      //Now grab the bin specified and add an association
      //  note that this will not be set for client scans.

      if (isset($fieldData['binID'])){
        $binID = $fieldData['binID'];


        if ($binID>0){
          $bin = new Storagebin($binID);
          // TODO: pull this association name from config
          $bin->associate('binitems', 'storagepallet', [$newPallet->ID]);
          $binName = $bin->GetDisplayText();
        }
        Log::debug("New Pallet created id [$newPalletID] name = [$newpalletName] and associated with bin $binName");
      }
      else{
        Log::debug("New Pallet created id [$newPalletID] name = [$newpalletName].  Not associated with any bin");

      }

      Transaction::PalletAdd($userID, $newPalletID, $newpalletName, $providerID);
    }
    else{
      Log::debug("Pallet was not created");
    }
    return $newPalletID;
  }
  public function UpdateRecord($fieldData){
      $results = parent::UpdateRecord($fieldData);
      if (empty($results)){

        $binID = isset($fieldData['binID']) ? $fieldData['binID'] : 0 ;

        if ($binID>0){
          $bin = new Storagebin($binID);
          // TODO: pull this association name from config
          // TODO: do not copy this code from insert
          $bin->associate('binitems', 'storagepallet', [$this->ID]);

        }
      }
  }
  function DeleteRecord($id){
    $userID     = SessionManager::GetCurrentUserID();
    $name       = $this->GetField('name');
    $success    = parent::DeleteRecord($id)==0;
    $providerID = $this->GetField('providerid');


    if (!$success){
      Log::Error("Delete failed for Pallet [$id]");
    } else{
      Transaction::PalletDelete($userID, $id, $name, $providerID);
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
    return new Storagepallet(0);
  }
  public function GetDisplayText()
  {
    $displayName = $this->DB_Fields["name"];

    $binRecord = InventoryManager::GetBinByPallet($this->ID);

    if (!empty($binRecord)){
      $displayName .= " : BIN [ " . $binRecord[0]['binname'] . " ] ";
    }

    if (!empty($this->GetField('full')) && $this->GetField('full')){
      $displayName .= " FULL ";
    }

    return $displayName;
  }
}
