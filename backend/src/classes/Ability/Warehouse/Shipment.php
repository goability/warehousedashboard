<?php
namespace Ability\Warehouse;

class Shipment extends ResourceBaseType
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
  function IsApproved(){
    $dateApproved = $this->GetField('date_approved');
    if ( null!=$dateApproved && !strpos($dateApproved, "970",1)){
      return true;
    }
    else{
      return false;
    }
  }
  function IsFulfilled(){
    $dateFulfilled = $this->GetField('date_shipped');
    if ( null!=$dateFulfilled && !strpos($dateFulfilled, "970",1)){
      return true;
    }
    else{
      return false;
    }
  }

  function UpdateRecord($fieldData){

    $userID   = SessionManager::GetCurrentUserID();
    $id       = $this->ID;
    $isApproved = $this->IsApproved();
    if (empty($this->GetField('notes'))){
      //// TODO: doesn't belong here
      $this->SetField('notes', '');
    }
    if (empty($this->GetField('lotnumber'))){
      //// TODO: doesn't belong here
      $this->SetField('lotnumber', '');
    }

    $success  = parent::UpdateRecord($fieldData)==0;

    if (!$success){
      Log::Error("Update failed for Shipment Request $id");
      return;
    }
    return $success;
  }

  function InsertRecord($fieldData){

    //adding initial qty remaining // TODO: this in db !!
    $fieldData['confirmed_pulled_qty'] = 0;
    $newShipmentID = parent::InsertRecord($fieldData);

    if ($newShipmentID<0){
      Log::Error("Insert failed for Shipment Request.");
      return;
    }

    $clientID   = $fieldData['userid_requestor'];
    $receiverID = $fieldData['userid_receiver'];
    $itemID     = $fieldData['itemid'];
    $qty        = $fieldData['qty'];
    $notes      = $fieldData['notes'];

    if ($receiverID==-1){
      $receiverID = null;
    }

    Transaction::ShipmentRequest($clientID, $itemID, $receiverID, $qty, $notes);
  }

  /*
  *  GetSelectOptionItemText
  *   - Given an db results array, built a select optin line item
  *
  */
  public function GetSelectOptionItemText($record)
  {
    return addslashes($record["name"]);
  }
  /*
  *  GetSelectListItemText
  *   - Given an db results array, built a list optin line item
  *
  */
  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }
  public function GetNewInstance()
  {
    return new Shipment(0);
  }
  public function claim($pullerID){
    return DataProvider::Claim($this, $pullerID, 'puller');
  }
  public function pull($pullerID, $palletID, $qty){
    return DataProvider::Pull($this->ID, $pullerID, $palletID, $qty);
  }
  /*
  * Set date_shipped AND userid_shipped
  *  Clear this ID from all palletInventory.shipment_request_ids
  */
  public function closeShipment(){
    return DataProvider::CloseShipment($this);
  }
  /*
  * Customize the List Item text for this resource
  */
  public function GetListItemText($callingResource=null)
  {
    if (isset($this->DB_Fields["name"])){
          $requestText = "";

          if ($callingResource!=null){

            if ($callingResource->Name=='Storagepallet'){
              $palletID     = $callingResource->ID;
              $productName  = $this->PalletDetails[$palletID]['itemname'];
              $qty          = $this->PalletDetails[$palletID]['item_qty'];
              $requestText  .= "$productName - In Storage [<b>" . $qty . "</b>]";
            }
            else if ($callingResource->Name=='Storageitem'){
              $requestText .= $callingResource->GetField('name');
            }
            else{
              $requestText .= $callingResource->Name;
            }
          }
          $shippedDate = !empty($this->GetField('date_shipped')) ?
            Util::GetFormattedDateMySQL($this->GetField('date_shipped')) : "Pending";

          $requestText .= " | " . $this->GetField('qty') . " | " . $shippedDate;

          return $requestText;
    }
    else{
      return "[db name field not set ]";
    }
  }
}
