<?php
namespace Ability\Warehouse;

//Get storage and shipping requests for this user
$currentUserID = SessionManager::GetCurrentUserID();
$requestsClaimed = DataProvider::GetStorageRequestsByStocker($currentUserID);
$shippingRequests = DataProvider::GetShippingRequestsByStocker($currentUserID);

if (empty($requestsClaimed)){
  echo "<div class='h3' id='in-process-storage-title'>Storage Requests: none</div>";
}
else{
?>
<div class="h3" id="history-title">In Process Storage Requests</div>
<table class="table" id="history-table">
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
      <th>Pallet</th>
      <th>Bin</th>
      <th></th>
    </tr>
  </thead>
  <tbody id='in-process-storage-body'>
  <?php

  foreach ($requestsClaimed as $item) {

      $date_created     = $item['date_created'];
      $date_needed      = $item['date_needed'];
      $storageRequestID = $item['storageid'];
      $item_qty         = $item['qty'];
      $palletID         = $item['palletid'];

      ?>
      <tr class='table-dark text-dark' id='in_process_StorageRowItem<?php echo $storageRequestID?>'>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item_qty; ?></td>
        <td id='rowData-lotnumber-<?php echo $storageRequestID; ?>'><?php echo $item["lotnumber"]; ?></td>
        <td id='rowData-tag-<?php echo $storageRequestID; ?>'><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_created; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td><?php echo $item["notes"]; ?></td>
        <td><?php echo $item["palletname"]; ?></td>
        <td><?php echo $item["binname"]; ?></td>
      </tr>
      <?php

  }

  ?>
  </tbody>
</table>
<?php } //end of else there are storage requests
if (empty($shippingRequests)){
  echo "<div class='h3' id='in-process-storage-title'>No shipping requests in process</div>";
}
else{
?>
<div class="h3" id="history-title">In Process Shipping Requests</div>
<table class="table" id="history-table">
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
      <th>Pallet</th>
      <th>Bin</th>
      <th></th>
    </tr>
  </thead>
  <tbody id='in-process-shipping-body'>
  <?php

  foreach ($shippingRequests as $item) {

      $date_created     = $item['date_created'];
      $date_needed      = $item['date_needed'];
      $shipmentRequestID = $item['shipmentid'];
      $item_qty         = $item['qty'];
      $palletID         = $item['palletid'];

      ?>
      <tr class='table-dark text-dark' id='in_process_ShipmentRowItem<?php echo $shipmentRequestID?>'>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item_qty; ?></td>
        <td id='rowData-lotnumber-<?php echo $shipmentRequestID; ?>'><?php echo $item["lotnumber"]; ?></td>
        <td id='rowData-tag-<?php echo $shipmentRequestID; ?>'><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_created; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td><?php echo $item["notes"]; ?></td>
        <td><?php echo $item["palletname"]; ?></td>
        <td><?php echo $item["binname"]; ?></td>
        <td>
          <button id="button_ship_Shipment<?php echo $shipmentRequestID; ?>"
            onclick="PWH_UIService.showHideApprove(<?php echo $shipmentRequestID ?>, 'Shipment', true, 'ship')">ship</button>


            <div class="container" id="div_ship_Shipment<?php echo $shipmentRequestID; ?>"
              style="background-color: LightBlue; display:none;">
              <div class="row">
                <div class="col-sm-12">
                <form method="Post">
                   <input type="hidden" id="in_process_ShipmentRowItem<?php echo $shipmentRequestID; ?>palletID" value="<?php echo $palletID; ?>">
                    <input type="hidden" id="in_process_ShipmentRowItem<?php echo $shipmentRequestID; ?>qty_orig" value="<?php echo $item_qty; ?>">
                    <input type="number" name="in_process_ShipmentRowItem<?php echo $shipmentRequestID; ?>qty" id="in_process_ShipmentRowItem<?php echo $shipmentRequestID; ?>qty" value="<?php echo $item_qty; ?>" data-decimals="0" min="0" max="<?php echo $item_qty; ?>" step="1"/>
                    <button class="btn btn-xs btn-success" onclick="PWH_UIService.shipStorageItem(<?php echo("$shipmentRequestID, $currentUserID"); ?>)">SHIP IT</button>
                    <button class="btn btn-xs btn-danger" onclick="PWH_UIService.showHideApprove(<?php echo $shipmentRequestID; ?>, 'Shipment', false,'ship')">cancel</button>
                </form>
                </div>
              </div>
            </div>
        </td>
      </tr>
      <?php

  }

  ?>
  </tbody>
</table>
<?php } //end of else there are storage requests
