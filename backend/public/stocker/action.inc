<?php
namespace Ability\Warehouse;
?>
<form class="" action="<?php echo("$url_base/action?$query_param_access_token"); ?>" method="post">
<table width="202px" style="min-width: 202px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC'; background:#FFFFFF;">
  <tr style='background:Silver'>
    <td colspan="2" align="center" style="background:Navy; color:Yellow">
        <H2 style="margin:0px;"><?php echo $currentPalletName; ?></H2>
    </td>
  </tr>
  <tr>
    <td align="center" style="background:Silver;"><H5 style="margin:0px;"><?php echo $currentPalletQty; ?></H5></td>
    <td align="center" style="background:#FFFFFF;"><?php echo !empty($currentPalletItemName) ? $currentPalletItemName : "Empty pallet"; ?></td>
  </tr>


  <tr>
    <td style='background:#000000; color:Yellow' align="right">PID </td>
    <td>
      <input type='text' name="palletName-scanned" id="palletName-scanned" size=16>
    </td>
  </tr>
  <tr>
    <td style='background:#000000; color:Yellow'  align="right">BIN </td><td>
      <input type='text' name="binName-scanned" id="binName-scanned" size=16></td>
  </tr>
  <tr>
    <td style='background:#000000; color:Yellow'  align="right">QTY </td><td>
      <input type='text' name="qty-scanned" id="qty-scanned" maxlength=9 size=6
<?php
  if ( isset($next_pallet_scanQty)){
    echo " value=$next_pallet_scanQty";
  }

?>

      >
    </td>
  </tr>
  <tr>
    <td style='background:#000000; color:Yellow'  align="right">LOT </td><td>
      <?php

      if ($actionType=='SHIP'){
            echo $currentPalletLotNumber;
      }
      else{
        if (empty($currentPalletLotNumber)){
          echo "<input type='text' name='lot-number-scanned' id='lot-number-scanned' size=12 maxlength=16>";
        }
        else {
          echo $currentPalletLotNumber;
        }
      }
      ?>
    </td>
  </tr>
  <?php

  if ($actionType=='SHIP'){
    ?>
  <tr>
    <td style='background:#000000; color:Yellow' align='right'>Ticket</td>
    <td>
    <select id='currentPalletShipmentRequestID' name='currentPalletShipmentRequestID'>
      <?php
          echo "<option value=0>Select a ticket</option>";
         foreach ($currentPalletShipmentRequests as $id=>$name) {
           echo "<option value='$id'";
           if ($id==$currentPalletShipmentRequestID){
             echo " selected";
           }
           echo ">$name</option>";
         }
      ?>
    </select>
    </td>
  </tr>
  <?php }
  else if ($currentPalletStorageRequestID==0 && !empty($storageRequests)){

?>
<tr>
  <td style='background:#000000; color:Yellow' align='right'>Ticket</td>
  <td>

  <select id='storage-requestid-scanned' name='storage-requestid-scanned'>
    <?php
       echo "<option value=0>Select a ticket</option>";
       $requestIDs = array();


       foreach ($storageRequests as $item) {
         $storageRequestID          = $item['storageid'];
         if (in_array($storageRequestID,$requestIDs)){
           continue;
         }
         $requestIDs[] = $storageRequestID;


         $currentStorageRequestQty  = $item['qty'];
         $lotNumber                 = $item['lotnumber'];
         $itemName                  = $item['name'];

         $displayitemName = empty($item['displaycode']) ? substr($itemName,0,6) : $item['displaycode'];

         // TODO: optimize on db side, don't call this way
         $currentInventory       = InventoryManager::GetStorageRequestInventory($storageRequestID);
         $storageRequestCountInStorage = $currentInventory[0]['current_qty'];
         $ticketQtyRemaining = $currentStorageRequestQty - $storageRequestCountInStorage;

         $storageItemDisplayText =  $displayitemName . " - " . $ticketQtyRemaining;

         if (strlen($lotNumber)>0){
           $storageItemDisplayText .= " | $lotNumber";
         }
         echo "<option value='$storageRequestID'>$storageItemDisplayText</option>";
       }
    ?>
  </select>
  </td>
</tr>

  <?php
  }
  else if ($currentPalletStorageRequestID>0){
    // TODO: optimize on db side, don't call this way
    $currentInventory       = InventoryManager::GetStorageRequestInventory($currentPalletStorageRequestID);
    $storageRequestCountInStorage = $currentInventory[0]['current_qty'];



    $currentStorageRequest = new Storage($currentPalletStorageRequestID);
    $currentStorageRequestQty = $currentStorageRequest->GetField('qty');
    $displayitemName = $currentStorageRequest->GetDisplayText();
    $ticketQtyRemaining = $currentStorageRequestQty - $storageRequestCountInStorage;

    $storageItemDisplayText = $currentPalletItemName;//  $displayitemName . " - " . $currentPalletItemName;
    ?>
    <tr>
      <td style='background:#000000; color:Yellow' align='right'>Ticket</td>
      <td><?php echo $storageItemDisplayText; ?></td>
    </tr>

    <?php
  }

  if (isset($confirmCompleteMessage)){
    echo "<tr style='background:#000000; color:Yellow; text-align:center;'><td colspan='2'>$confirmCompleteMessage</td></tr>";
} ?>
  <tr style='background:#000000; color:Yellow; text-align:center;'>
    <td colspan="2">
      <input type="hidden" id="currentPalletID" name="currentPalletID" value="<?php echo $currentPalletID; ?>">
      <input type="hidden" id="currentPalletName" name="currentPalletName" value="<?php echo $currentPalletName; ?>">
      <input type="hidden" id="currentPalletQty" name="currentPalletQty" value="<?php echo $currentPalletQty; ?>">
      <input type="hidden" id="currentPalletItemName" name="currentPalletItemName" value="<?php echo $currentPalletItemName; ?>">
      <input type="hidden" id="currentPalletItemID" name="currentPalletItemID" value="<?php echo $currentPalletItemID; ?>">
      <input type="hidden" id="currentPalletOwnerID" name="currentPalletOwnerID" value=<?php echo $currentPalletOwnerID; ?>>
      <input type="hidden" id="currentPalletBinName" name="currentPalletBinName"  value="<?php echo $currentPalletBinName; ?>">
      <input type="hidden" id="currentPalletStorageRequestID" name="currentPalletStorageRequestID" value="<?php echo $currentPalletStorageRequestID; ?>">
      <input type="hidden" id="currentPalletLotNumber" name="currentPalletLotNumber"  value="<?php echo $currentPalletLotNumber; ?>">


      <?php
      if ($actionType=='SHIP'){
        ?><input id='shipbutton' type='submit' value='SHIP' name='action' style='background-color: green; color:Yellow;'>
      <?php }
      else if ($actionType=='STORE'){ ?><input id='storebutton' type='submit' value='STORE' name='action' style='background-color: green; color:Yellow;'>
      <?php } ?>


      <?php

      if (isset($showConfirmCompleteButton)){
        echo "<input type='submit' value='CONFIRM COMPLETE' name='action'  style='background-color: green; color:Yellow;'>";
        if ($currentPalletShipmentRequestID>0){
          echo "<input type='hidden' id='currentPalletShipmentRequestID' name='currentPalletShipmentRequestID' value='$currentPalletShipmentRequestID'>";
        }

      }
      else if ($allowCloseTicket){
      echo "<input type='submit' value='COMPLETE REQUEST' name='action'  style='background-color: blue; color:Yellow;'>";

      } ?>
      <input type='submit' value='CANCEL' name='action'  style='background-color: red; color:Yellow;'>

    </td>
  </tr>
</table>
</form>
