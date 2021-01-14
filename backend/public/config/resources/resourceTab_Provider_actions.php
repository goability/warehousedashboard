<?php

namespace Ability\Warehouse;

//Get storage and shipping requests for clients of this user

$storageRequests  = DataProvider::GetUnapprovedStorageRequests(SessionManager::GetClientIDs());
$shippingRequests = DataProvider::GetUnapprovedShipmentRequests(SessionManager::GetClientIDs());
$aclProviders     = SessionManager::GetAccessibleRecordIDs("Provider");//get providers which this user has full access
$providerIDs      = array();

$dateFormater     = 'm/d/y';//how to format the dates on the form

// TODO: More efficient query here to gather all pallets for storage AND find all pallets when shipping
//   currently it is done below with each item, less efficient.  Should do here, store in array, the lookup


$userID = SessionManager::GetCurrentUserID();
$clientIDs = SessionManager::GetClientIDs();

if (empty($storageRequests)){
  echo "<div class='h3' id='pending-storage-title'>No storage requests</div>";
}
else{

  foreach ($aclProviders as $provider) {
    $providerIDs[] = intval($provider["id"]);
  }

//Storage requests exists, go ahead and grab the available bins

$includeFull = true;
$onlyShowEmpty = ConfigurationManager::GetParameter('storeOnlyOnEmptyPallet');

$availablePallets = array();
foreach ($providerIDs as $providerID) {
  $availablePallets = array_merge($availablePallets,
    InventoryManager::GetPalletsAvailableForStorage($providerID, $includeFull,null, null, $onlyShowEmpty));
}


$palletCount = !is_null($availablePallets) ?
                    count($availablePallets) : 0;

?>
<div class="h3" id="storage-requests-title">Storage Requests</div>
<table class="table" id="storage-requests-table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Quantity</th>
      <th>Lot</th>
      <th>Tag</th>
      <th>Label</th>
      <th>Requested on</th>
      <th>Store by</th>
      <th>Notes</th>
      <th></th>
    </tr>
  </thead>
  <tbody id='pending-storage-body'>
  <?php

  foreach ($storageRequests as $clientID => $requests) {

    if (empty($requests)){
      continue;
    }
    $clientName = SessionManager::GetClientName($clientID);

    echo "<tr class=h6><td colspan=9>$clientName</td></tr>";

    foreach ($requests as $item) {

      $date_created       = Util::GetFormattedDateMySQL($item['date_created'], $dateFormater);
      $date_needed        = Util::GetFormattedDateMySQL($item['date_needed'], $dateFormater);
      $storageRequestID   = $item['storageid'];
      $qty                = $item["qty"];
      $storageitemid      = $item["id"];

      ?>
      <tr class='table-dark text-dark' id='requestStorageRowItem<?php echo $storageRequestID?>'>
        <td><?php echo $item["name"]; ?><span id='store-rowData-itemid-<?php echo $storageRequestID; ?>' style='visibility:hidden'><?php echo $storageitemid;?></span></td>
        <td><?php echo $qty; ?></td>
        <td id='store-rowData-lotnumber-<?php echo $storageRequestID; ?>'><?php echo $item["lotnumber"]; ?></td>
        <td id='store-rowData-tag-<?php echo $storageRequestID; ?>'><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_created; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td><?php echo $item["notes"]; ?></td>
        <td>
          <button id="button_approve_Storage<?php echo $storageRequestID; ?>"
            onclick="PWH_UIService.showHideApprove(<?php echo $storageRequestID ?>, 'Storage', true)">approve</button>

            <div class="container" id="div_approve_Storage<?php echo $storageRequestID; ?>"
              style="background-color: LightBlue; display:none;">

              <?php
              if ($palletCount==0){
                echo "No pallets available for storage.";
                if ($onlyShowEmpty){
                  echo "<br> Current configuration requires fully empty pallets, no mixing.";
              ?>
                  <button class="btn btn-xs btn-danger" onclick="PWH_UIService.showHideApprove(<?php echo $storageRequestID; ?>, 'Storage', false)">cancel</button>
                <?php
                }
              }
              else{
                ?>
                <div class="row">
                  <div class="col">
                    qty: <input type="text" id="palletqty<?php echo $storageRequestID; ?>" size=6 value="<?php echo $qty; ?>">
                      <button type="button" id="assignbutton<?php echo $storageRequestID; ?>" name="button" onclick="<?php echo("PWH_UIService.selectPalletForShipORStorage('Storage', $storageRequestID);");?>">assign</button>
                      <input type="hidden" id="qty_orig<?php echo $storageRequestID; ?>" value="<?php echo $qty; ?>">
                      <input type="hidden" id="qty_remaining<?php echo $storageRequestID; ?>" value="<?php echo $qty; ?>">

                  </div>
                  <div class="col">
                    <button id="approveButtonStorage<?php echo $storageRequestID; ?>" class="btn btn-xs btn-success" disabled=true onclick="PWH_UIService.approveStorageRequest(<?php echo("$storageRequestID, $userID"); ?>)">approve</button>
                    <button class="btn btn-xs btn-danger" onclick="PWH_UIService.showHideApprove(<?php echo $storageRequestID; ?>, 'Storage', false)">cancel</button>
                  </div>
                </div>
              <div class="row">
                <div class="col">
                  <form class="" method="post">
                  <select class="" id="selectedPalletIDStorage<?php echo $storageRequestID; ?>" size=<?php echo $palletCount+1; ?>>
                    <option selected value="-1">[Client]</option>
                    <?php
                    foreach ($availablePallets as $palletName=>$palletContent) {
                      foreach ($palletContent as $itemid => $pallet) {

                        $palletID = $pallet['palletid'];
                        $itemname = !empty($pallet['itemname']) ? "&nbsp;" . $pallet['itemname'] : "";
                        $itemqty  = ($pallet['qty']>0) ? "-" . $pallet['qty'] : "";
                        $palletName = $pallet['palletname'];
                        $palletText = "[$palletName]" . $itemname . $itemqty;
                        $isfull = $pallet['full'];

                        echo "<option value=$palletID";

                        if ($isfull){
                          echo " disabled";
                        }
                        echo ">$palletText</option>";
                      }
                     }?>
                  </select>
                      </form>
                </div>
                <div class="col">
                  <h4>Pallet Distro</h4>
                  &nbsp;<select id="targetPalletIDStorage<?php echo $storageRequestID; ?>" size="3"></select>
                </div>
              </div>


              <?php
            }//end pallets are available for storage
              ?>
            </div>
        </td>
      </tr>
      <?php
    }
  }

  ?>
  </tbody>
</table>
<?php } //end of else there are storage requests
if (empty($shippingRequests)){
  echo "<div class='h3' id='shipping-requests-title'>No shipping requests</div>";
}
else{
?>
<div class="h3" id="shipping-requests-title">Shipping Requests</div>
<table class="table" id="shipping-requests-table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Quantity</th>
      <th>Lot</th>
      <th>Tag</th>
      <th>Label</th>
      <th>Requested on</th>
      <th>Ship by</th>
      <th>Notes</th>
      <th></th>
    </tr>
  </thead>
  <tbody id='pending-shipment-body'>
  <?php

  foreach ($shippingRequests as $clientID => $requests) {

    if (empty($requests)){
      continue;
    }
    $clientName = SessionManager::GetClientName($clientID);

    echo "<tr class=h6><td colspan=9>$clientName</td></tr>";

    foreach ($requests as $item) {

      $date_created = Util::GetFormattedDateMySQL($item['date_created'], $dateFormater);
      $date_needed = Util::GetFormattedDateMySQL($item['date_needed'], $dateFormater);
      $lotnumber = $item['lotnumber'];

      $shippingRequestID = $item['shipmentid'];
      $itemID = intval($item['id']);
      $palletsContainingItem = DataProvider::GetPalletsContainingItem($itemID, $lotnumber);

      $palletCount = !is_null($palletsContainingItem) ?
                          count($palletsContainingItem) : 0;

      ?>
      <tr class='table-dark text-dark' id='requestShippingRowItem<?php echo $shippingRequestID?>'>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item["qty"]; ?></td>
        <td id='ship-rowData-lotnumber-<?php echo $shippingRequestID; ?>'><?php echo $item["lotnumber"]; ?></td>
        <td id='ship-rowData-tag-<?php echo $shippingRequestID; ?>'><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_created; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td><?php echo $item["notes"]; ?></td>
        <td>
          <?php
            if (!empty($palletsContainingItem)){
          ?>
          <button id="button_approve_Shipment<?php echo $shippingRequestID; ?>"
            onclick="PWH_UIService.showHideApprove(<?php echo $shippingRequestID ?>, 'Shipping', true)">approve</button>

          <div class="container" id="div_approve_Shipping<?php echo $shippingRequestID; ?>"
              style="border-radius: 10px; padding: 6px; margin: 2px; background-color: LightBlue; display:none;">
              <div class="row">
                <div class="col-sm-12">
                  <button id="approveButtonShipping<?php echo $shippingRequestID; ?>" class="btn btn-xs btn-success" disabled=true onclick="PWH_UIService.approveShippingRequest(<?php echo("$shippingRequestID, $userID"); ?>)">approve</button>
                  <button class="btn btn-xs btn-danger" onclick="PWH_UIService.showHideApprove(<?php echo $shippingRequestID; ?>, 'Shipping', false);">cancel</button>
                </div>
              </div>
              <div class="row">
                <div class="col">
                  <form class="" method="post">
                  <select class="" id="selectedPalletIDShipping<?php echo $shippingRequestID; ?>" size=<?php echo $palletCount+1; ?>>
                    <option value=-1 disabled>Select a pallet</option>
                    <?php
                    foreach ($palletsContainingItem as $pallet) {
                      $itemid   = $pallet['itemid'];
                      $palletID = $pallet['palletid'];
                      $itemname = !empty($pallet['itemname']) ? "-" . $pallet['itemname'] : "";
                      $itemqty  = ($pallet['qty']>0) ? "-" . $pallet['qty'] : "";
                      $palletName = $pallet['palletname'];
                      $palletText = "[$palletName]" . $itemqty;
                      $isfull = $pallet['full'];
                      echo "<option value=$palletID";

                      if ($pallet['qty']<1){
                        echo " disabled";
                      }
                      echo " onclick=\"PWH_UIService.selectPalletForShipORStorage('Shipping', $shippingRequestID, $palletID);\"";
                      echo ">$palletText</option>";

                     }?>
                  </select>
                  </form>
                </div>
                <div class="col" align="left">
                  <h4>Pallet source</h4>
                  &nbsp;<select id="targetPalletIDShipping<?php echo $shippingRequestID; ?>" size="3"></select>
                </div>
              </div>
          </div>
          <?php
              }
              else{
                echo "No Inventory";
              }
          ?>
        </td>
      </tr>
      <?php
    }
  }
  ?>
  </tbody>
</table>

<?php } //end of else there are ship requests
