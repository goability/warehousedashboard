<?php
namespace Ability\Warehouse;

class InventoryManager
{

  static function GetStoredItems($userID){
    return DataProvider::GetItemsInStorage($userID);
  }
  static function GetCountOfStoredItems($userID){
    $data = DataProvider::GetItemsInStorage($userID);
    return is_null($data) ? 0 : count($data);
  }
  static function GetCountOfPendingStorages($userID){
    $data = DataProvider::GetItemsPendingStorage($userID);
    return is_null($data) ? 0 : count($data);
  }
  static function GetCountOfPendingShipments($userID){
    $data = DataProvider::GetItemsPendingShipment($userID);
    return is_null($data) ? 0 : count($data);
  }
  static function GetRecentActions($userID){
    return DataProvider::GetItemHistory($userID);
  }
  static function GetCountOfRecentActions($userID, $type){
    $dateTimeFilter = ConfigurationManager::GetParameter("ClientRecent$type" . "Filter");
    $recentHistory = DataProvider::GetItemHistory($userID, $type, $dateTimeFilter);
    $count = 0;
    foreach ($recentHistory as $historyItem) {
      if($historyItem["action"]==$type && $historyItem["fulfilled"]){
        $count+=1;
      }
    }

    return $count;
  }
  static function GetCountOfRecentStorages($userID){
    return self::GetCountOfRecentActions($userID, "STORE");
  }
  static function GetCountOfRecentShipments($userID){
    return self::GetCountOfRecentActions($userID, "SHIP");
  }
  static function GetLatestStatusMessage($userID){
    $data = DataProvider::GetItemsInStorage($userID);
    return empty($data) ? 0 : count($data);
  }
  static function GetBinByPallet($palletID){
    $sql = "SELECT storagebin.id, storagebin.name AS binname FROM storagebin
            JOIN storagebininventory ON storagebininventory.binid=storagebin.id
            WHERE storagebininventory.palletid=$palletID";

    return DataProvider::GenericGET($sql);
  }
  /*
  * Given an itemid, first find a pallet that is owned by provider
  */
  static function GetPalletsAvailableForStorage($providerid, $includeFull=false, $itemid=null, $lotnumber=null, $onlyShowEmpty=true){
    return DataProvider::GetPalletsAvailableForStorage($providerid, $includeFull, $itemid, $lotnumber,$onlyShowEmpty);
  }
  static function GetPalletInventory($palletIDs, $userID, $lotnumber=null, $tag=null, $includeEmpty=false){

    $pallets = DataProvider::GetPalletInventory($palletIDs, $userID,  $lotnumber, $tag, $includeEmpty);
    return $pallets;
  }
  static function GetPendingPalletInventory($palletIDs, $userID, $lotnumber=null, $tag=null){

    $pallets = DataProvider::GetPalletInventory($palletIDs, $userID,  $lotnumber, $tag, true, 0);
    return $pallets;
  }
  static function GetBinInventory(){

    $binInventory = DataProvider::GetBinInventory(SessionManager::GetCurrentUserID());
    return $binInventory;
  }
  static function GetBinInventoryByName($name){

    $data = DataProvider::GetBinInventoryByName(SessionManager::GetCurrentUserID(), $name);
    return $data;
  }
  static function GetPalletInventoryByName($name, $userID){
    $data = DataProvider::GetPalletInventoryByName($name, $userID);
    return $data;
  }

  static function GetClientInventory($userID=null){

    $clientInventory = null;

    if (is_null($userID))
    {
      $userID = SessionManager::GetCurrentUserID();
    }

    $isProvider = SessionManager::IsProvider();
    $clientInventory = DataProvider::GetItemsInStorage($userID, null, $isProvider);

    return $clientInventory;
  }
  static function GetLotInventory($clientID, $lotnumbers=null){
    $clientInventory = null;

    $clientInventory[""] = DataProvider::GetLotInventory($clientID, $lotnumbers);

    return $clientInventory;
  }
  static function GetProductInventory($userID, $itemID){
    $productInventory = null;

    $productInventory[""] = DataProvider::GetItemsInStorage($userID, $itemID);

    return $productInventory;
  }

  static function GetLotsForStorageItem($storageItemID){

    return DataProvider::GetLotsForStorageItem($storageItemID);

  }
  /*
  *  Gets original and current (active distro and qty) storageRequest data
  *
  *  @returns $storageItemData
  *
  *  "pallets" : []
  */
  static function GetStorageItem($storageitemID){

    $storageItemData = null;

    try {
      $storageItemData = DataProvider::GetStorageDetail($storageitemID);
    } catch (\Exception $e) {
      Log::error($e->getMessage());

    }
    return $storageItemData;
  }
  static function GetStorageRequestInventory($storageRequestID){

    $storageRequestData = null;

    try {
      $storageRequestData = DataProvider::GetStorageRequestInventory($storageRequestID);
    } catch (\Exception $e) {
        echo $e->getMessage();
        Log::error($e->getMessage());

    }
    return $storageRequestData;
  }
  static function GetStorageItemForRequest($storageRequestID){

    $sql = "SELECT `storageitem`.`id` FROM `storageitem` INNER JOIN `storage` ON `storage`.`itemid` = `storageitem`.`id` WHERE `storage`.`id`=$storageRequestID";
    $row = DataProvider::GenericGET($sql);

    if (is_null($row)){
      return null;
    } else {
      $id = $row[0]['id'];
      $storageItem = new Storageitem(1);
    }
    return $storageItem;
  }
}
