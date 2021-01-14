<?php
namespace Ability\Warehouse;

class Storage extends ResourceBaseType
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
  
  public $ProductName='';
  public $PalletDetails;//array "productName", array ("palletid", "palletName", "currentQty", "lotnumber", "tag")

  public $IsClosed = false;
  /*
  * Construct an object populated from
  * @param $ID = Int - DB Record.ID or null for new record
  */
  function __construct($ID=null){
    parent::__construct($ID);

  }
  function additionalRecordSetup(){
    if (is_numeric($this->ID) && $this->ID>0){
      $pallets = DataProvider::GetStorageDetail($this->ID);
      foreach ($pallets as $pallet) {
        $palletID = $pallet['id'];
        $this->PalletDetails[$palletID] = $pallet;

      }
      $this->IsClosed = !is_null($this->GetField('date_stored'));
    }
  }
  /*
    GetSelectOptionItemText
     - Given an db results array, built a select optin line item
  *
  */
  public function GetSelectOptionItemText($record)
  {
    return $record["name"];
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
  /*
    return new instance of this object
  */
  public function GetNewInstance()
  {
    return new Storage(0);
  }
  public function claim($stockerID){
    return DataProvider::Claim($this, $stockerID, 'stocker');
  }
  public function store($stockerID, $palletID, $qty){
    $itemID     = $this->GetField('itemid');
    $lotnumber  = $this->GetField('lotnumber');
    $tag        = $this->GetField('tag');
    return DataProvider::Store($this->ID, $stockerID, $palletID, $qty, $lotnumber, $itemID, $tag);
  }
  // if finished, mark timestamp on storage record
  public function closeStorage($stockerID, $notes=null){
    return DataProvider::CloseStorage($this->ID, $stockerID, $notes);
  }
  /*
  * When listing storage requests, show the product name
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
            } elseif ($callingResource->Name=='Storageitem'){
              $requestText .= $callingResource->GetField('name');
            } else {
              $requestText .= $callingResource->Name;
            }
          }
          $storedDate = !empty($this->GetField('date_stored')) ?
            Util::GetFormattedDateMySQL($this->GetField('date_stored')) : "Pending";

          $requestText .= " | " . $this->GetField('qty') . " | " .  $storedDate;
          return $requestText;
    }
    else{
      return "[db name field not set ]";
    }
  }
  function InsertRecord($fieldData){

    $approver = $fieldData['userid_approver'];
    if (\is_nan($approver) || $approver ===''){
      $fieldData['userid_approver'] = 0;
    }

    $newStorageID = parent::InsertRecord($fieldData);

    if ($newStorageID<0){
      Log::Error("Insert failed for Storage Request.");
      return;
    }
    $clientID = $fieldData['userid_requestor'];
    $itemID   = $fieldData['itemid'];
    $qty      = $fieldData['qty'];
    $notes    = $fieldData['notes'];

    Transaction::StorageRequest($clientID, $itemID, $qty, $notes);

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
      Log::Error("Update failed for Storage Request $id");
    }
    return $success;
  }
  function IsApproved(){
    $dateApproved = $this->GetField('date_approved');
    return ( null!=$dateApproved && !strpos($dateApproved, "970",1)) ? true : false;
  }
  function IsFulfilled(){
    $dateFulfilled = $this->GetField('date_stored');
    return ( null!=$dateFulfilled && !strpos($dateFulfilled, "970",1)) ? true : false;
  }
}
