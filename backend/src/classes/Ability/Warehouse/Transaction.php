<?php
namespace Ability\Warehouse;
/*
 Log a Transaction
*/
class Transaction
{
  /*
  * Add a generic transaction
  */
  static function Add($type, $userid, $clientid=null, $receiverid=null, $itemid=null,
                      $providerid=null, $palletid=null, $binid=null, $notes=null)
  {
    DataProvider::TransactionAdd($type, $userid, $clientid, $receiverid, $itemid,
                                  $providerid, $palletid, $binid, $notes);
  }
  static function StorageRequest($clientid, $itemid, $qty, $notes=null){

    $providerid = $palletid = $binid = $receiverid = null;
    $notes = is_null($notes) ? "" : $notes;

    $notes .= " QTY [$qty] items";

    self::Add(Constants\TransactionType::STORE_REQUEST, $clientid,
              $clientid, $receiverid, $itemid, $providerid, $palletid, $binid, $notes);

  }
  static function StorageRequestCancel($approverid, $requestid){
    $storage = new Storage($requestid);
    $isApproved = $storage->IsApproved();
    $itemid = $storage->GetField('itemid');
    $requestName = $storage->GetDisplayText();
    $notes = "Storage request [$requestid] [$requestName] cancelled in";
    $notes .= $isApproved ? " approved" : " not approved";
    $notes .= " state by user [$approverid]";

    self::Add(Constants\TransactionType::STORE_CANCEL, $approverid,
              $storage->GetField('userid_requestor'), null, $itemid, null, null, null, $notes);
  }
  static function ShipRequestCancel($approverid, $requestid){
    $shipment = new Shipment($requestid);
    $isApproved = $shipment->IsApproved();
    $itemid = $shipment->GetField('itemid');
    $requestName = $shipment->GetDisplayText();
    $notes = "Shipment request [$requestid] [$requestName] cancelled in";
    $notes .= $isApproved ? " approved" : " not approved";
    $notes .= " state by user [$approverid]";

    self::Add(Constants\TransactionType::SHIP_CANCEL, $approverid,
              $shipment->GetField('userid_requestor'), null, $itemid, null, null, null, $notes);
  }
  static function StorageApproval($approverid, $providerid, $clientid, $itemid, $palletid=null,$notes){
    self::Add(Constants\TransactionType::STORE_APPROVE, $approverid,
              $clientid, null, $itemid, $providerid, null, null, $notes);
  }
  static function StorageFulfilled($approverid, $providerid, $clientid, $itemid, $palletID, $binID, $notes){
    self::Add(Constants\TransactionType::STORE_FULFILL, $approverid,
              $clientid, null, $itemid, $providerid, $palletID, $binID, $notes);
  }

  static function ShipmentRequest($clientid, $itemid, $receiverid, $qty, $notes=null){

    $providerid = $palletid = $binid = null;
    $notes = is_null($notes) ? "" : $notes;

    $notes .= " QTY [$qty] items";

    self::Add(Constants\TransactionType::SHIP_REQUEST, $clientid,
              $clientid, $receiverid, $itemid, $providerid, $palletid, $binid, $notes);

  }
  static function ShipmentApproval($approverid, $providerid, $clientid, $itemid, $notes){
    self::Add(Constants\TransactionType::SHIP_APPROVE, $approverid,
              $clientid, null, $itemid, $providerid, null, null, $notes);
  }
  static function ShipmentFulfilled($approverid, $providerid, $clientid, $itemid, $notes){
    self::Add(Constants\TransactionType::SHIP_FULFILL, $approverid,
              $clientid, null, $itemid, $providerid, null, null, $notes);
  }

  static function Login($userid){
    self::Add(Constants\TransactionType::USER_LOGIN, $userid);
  }
  static function Logout($userid){
    self::Add(Constants\TransactionType::USER_LOGOUT, $userid);
  }

  static function ItemAdd($userid, $itemid, $name){
    self::Add(Constants\TransactionType::ITEM_ADD, $userid,
              null, null, $itemid,null,null,null,$name);
  }
  static function ItemDelete($userid, $itemid, $name){
    self::Add(Constants\TransactionType::ITEM_DELETE, $userid,
              null, null, $itemid,null,null,null,$name);
  }
  static function ItemUpdate($userid, $itemid){
    self::Add(Constants\TransactionType::ITEM_UPDATE, $userid,
              null, null, $itemid);
  }

  static function PalletAdd($userid, $palletid, $name, $providerid){
    self::Add(Constants\TransactionType::PALLET_ADD, $userid,
              null, null, null,$providerid, $palletid, null,$name);
  }
  static function PalletDelete($userid, $palletid, $name, $providerid){
    self::Add(Constants\TransactionType::PALLET_DELETE, $userid,
              null, null, null, $providerid,$palletid,null,$name);
  }
  static function PalletChangeBin($userid, $palletid, $oldbinID, $oldbinName, $newbinName, $providerid){
    self::Add(Constants\TransactionType::PALLET_CHANGE_BIN, $userid,
              null, null, null, $providerid,$palletid,$oldbinID, "FROM $oldbinName to $newbinName");
  }
  static function PalletAssignBin($userid, $palletid, $binID, $palletName, $binName, $providerid){
    self::Add(Constants\TransactionType::PALLET_ASSIGN_BIN, $userid,
              null, null, null, $providerid,$palletid,$binID, "Pallet [$palletName] assigned to [$binName]");
  }
  static function BinAdd($userid, $binid, $name, $providerid){
    self::Add(Constants\TransactionType::BIN_ADD, $userid,
              null, null, null,$providerid,null,$binid, $name);
  }
  static function BinDelete($userid, $binid, $name, $providerid){
    self::Add(Constants\TransactionType::BIN_DELETE, $userid,
              null, null, null, $providerid,null,$binid,$name);
  }

  static function UserSignup($userid, $name){
    self::Add(Constants\TransactionType::USER_SIGNUP, $userid,
              null,null,null,null,null,null, $name);
  }
  static function UserAdd($userid, $name){
    self::Add(Constants\TransactionType::USER_ADD, $userid,
              null,null,null,null,null,null, $name);
  }
  static function UserUpdate($userid, $name){
    self::Add(Constants\TransactionType::USER_UPDATE, $userid,
              null,null,null,null,null,null, $name);
  }
  static function UserDelete($userid, $notes){
    self::Add(Constants\TransactionType::USER_DELETE, $userid,
              null,null,null,null,null,null, $notes);
  }
  static function UserChangePassword($userid, $name){
    self::Add(Constants\TransactionType::USER_CHANGE_PASSWORD, $userid,
              null,null,null,null,null,null, $name);
  }
  static function StartMasquerade($userID, $clientID){
    self::Add(Constants\TransactionType::MASQUERADE_START, $userID,$clientID);
  }
  static function EndMasquerade($userID, $clientID){
    self::Add(Constants\TransactionType::MASQUERADE_END, $userID, $clientID);
  }
}
?>
