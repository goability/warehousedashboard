<?php
namespace Ability\Warehouse;

class Storageitem extends ResourceBaseType
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

  public $qtyInStorage = 0;

  function __construct($ID=null){
      parent::__construct($ID);
      $this->_formPath = "forms/formStorageitem.php";
  }

  function InsertRecord($fieldData){

    $newStorageID = parent::InsertRecord($fieldData);

    if ($newStorageID<0){
      Log::Error("Insert failed for StorageItem ");
      return;
    }
    $userID = $fieldData['ownerid'];
    $name = $fieldData['name'];

    $fieldData['sizexinches'] = (int)$fieldData['sizexinches'];
    $fieldData['sizeyinches'] = (int)$fieldData['sizeyinches'];
    $fieldData['sizezinches'] = (int)$fieldData['sizezinches'];

    Transaction::ItemAdd($userID, $newStorageID, $name);

    return $newStorageID;
  }
  function DeleteRecord($id){
    $userID   = SessionManager::GetCurrentUserID();
    $name     = $this->GetField('name');
    $success  = parent::DeleteRecord($id)==0;

    if (!$success){
      Log::Error("Delete failed for StorageItem $id");
    } else{
      Transaction::ItemDelete($userID, $id, $name);
    }
    return $success;
  }
  function UpdateRecord($fieldData){
    $userID   = SessionManager::GetCurrentUserID();
    $id       = $this->ID;
    if (empty($this->GetField('notes'))){
      //// TODO: doesn't belong here
      $this->SetField('notes', '');
    }

    $success  = parent::UpdateRecord($fieldData)==0;

    if (!$success){
      Log::Error("Update failed for StorageItem $id");
    } else {
      Transaction::ItemUpdate($userID, $id);
    }

    return $success;
  }
  public function GetSelectOptionItemText($record)
  {
    return $record["name"];
  }
  /*
  * Record Navigation Ship Store  This is the pop-out  Allowing Store/Ship
  */
  public function show_navExtraButtons($addReports=true){

    $configdefaultRequestDays = ConfigurationManager::GetParameter("ClientRequestDaysDefault");

    $defaultRequestDate = date('Y-m-d');
    $mod_date = strtotime($defaultRequestDate . "+ $configdefaultRequestDays days");
    $defaultRequestDate = date("Y-m-d",$mod_date) . "\n";

    $totalQty = 0;
    $lotnumbers = InventoryManager::GetLotsForStorageItem($this->ID);
    foreach ($lotnumbers as $row) {
      $lotnumber = $row["lotnumber"];
      $currentQty = $row["item_qty"];
      if (isset($lotQuantities[$lotnumber])){
        $lotQuantities[$lotnumber] += $currentQty;
      }
      else{
        $lotQuantities[$lotnumber] = $currentQty;
      }

      $totalQty += $currentQty;
    }

    $extraButtons = "&nbsp;<button class='btn-sm' id='button-ship'";

    if ($totalQty<1){
      $extraButtons .= " disabled";
    }

    $extraButtons .= " onclick=\"PWH_UIService.toggleShipStoreNav('ship');\">Ship</button>";

    $extraButtons .= "&nbsp;<button class='btn-sm' id='button-store' onclick=\"PWH_UIService.toggleShipStoreNav('store');\">Store</button>";


    $extraButtons .= "<div id='ship-store-detail' class='ship-store-detail-strip hidden'>";


    $extraButtons .= "<div class='container'>";

    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-12'>";
      $extraButtons .= "<span id='ship-store-ErrorMessage' style='color:Red; text-decoration:none; font-weight:normal;'></span>";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row' id='recipientRow'>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<label for='qty'>Receiver</label>";
      $extraButtons .= "</div>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<select id='userid_receiver'><option value='-1'>Select a receiver</option>";

      $recs = SessionManager::GetReceivers();

      if (!empty($recs)){

        $receiverOptionElement = '';
        foreach ($recs as $value) {
          $receiverName = $value['name'];
          $receiverID   = $value['id'];
          $receiverOptionElement .= "<option value=$receiverID>$receiverName</option>";
        }
        $extraButtons .= $receiverOptionElement;
      }
      $extraButtons .= "</select>";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<label for='qty'>Label</label>";
      $extraButtons .= "</div>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<input size=10 class='' type='text' name='ship-store-name' id='ship-store-name'>";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<label for='qty'>Quantity</label>";
      $extraButtons .= "</div>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<input size=10 class='' type='text' name='ship-store-qty' id='ship-store-qty'>";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<label for='ship-store-lot'>Lot</label>";
      $extraButtons .= "</div>";
      $lotQuantities = array();

      $extraButtons .= "<div class='col-sm-6'>";


      $lotListOptions = "";
      foreach ($lotQuantities as $lotnumber=>$qty){
          $lotListOptions .= "<option value='$lotnumber,$qty'>$lotnumber - [$qty]</option>";
      }
      $lotList = "<datalist id='lotlist'>";
      $lotList .= "<option value='AnyLot,$totalQty'>Any Lot - [$totalQty]</option>";
      $lotList .= $lotListOptions;
      $lotList .= "</datalist>";
      $extraButtons .= $lotList;

      $extraButtons .= "<input id='ship-store-lot' list='lotlist' />";


      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<label for='ship-store-tag'>Tag</label>";
      $extraButtons .= "</div>";


      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<input size=10 type='text' name='ship-store-tag' id='ship-store-tag'>";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<label for='ship-store-date_needed'>Date Needed</label>";
      $extraButtons .= "</div>";


      $extraButtons .= "<div class='col-sm-6'>";
      $extraButtons .= "<input type='date' name='ship-store-date_needed' id='ship-store-date_needed'";
      $extraButtons .= "value=" . $defaultRequestDate . ">";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";

    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-12'>";
      $extraButtons .= "<label for='ship-store-notes'>Special Instructions</label>";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-12'>";
      $extraButtons .= "<textarea id='ship-store-notes' name='ship-store-notes' rows=4 cols=20></textarea>";
      $extraButtons .= "</div>";

    $extraButtons .= "</div>";
    $extraButtons .= "<div class='row'>";

      $extraButtons .= "<div class='col-sm-12'>";
      $extraButtons .= "<input type='hidden' name='confirmed_pulled_qty' id='confirmed_pulled_qty' value=$totalQty>";
      $extraButtons .= "<input type='hidden' name='userid_approver' value=0>";
      $extraButtons .= "&nbsp;<button class='btn-sm' id='button-do-ship-store' onclick=\"PWH_UIService.doShipStore($this->ID, '$this->accessToken');\"></button>";
      $extraButtons .= "&nbsp;<button class='btn-sm' id='button-cancel' onclick=\"PWH_UIService.toggleShipStoreNav();\">Cancel</button>";
      $extraButtons .= "</div>";



    $extraButtons .= "</div>";

    $extraButtons .= "</div>";

    $extraButtons .= "</div>";

    return $extraButtons;
  }

  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }

  public function GetNewInstance()
  {
    return new Storageitem(0);
  }
}
