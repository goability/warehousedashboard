<?php

namespace Ability\Warehouse;

//Get approved but unfufilled storage and shipping requests all clients of providers this user works for
$userID = $employeeID = SessionManager::GetCurrentUserID();
$clientIDs = SessionManager::GetClientIDs();

Log::debug("Getting unfulfilled storage requests");
$storageRequests  = DataProvider::GetUnfufilledStorageRequests($clientIDs);
$shippingRequests = DataProvider::GetUnfufilledShipmentRequests($clientIDs);
$providerIDs      = SessionManager::GetProvidersForEmployee($userID);

if (empty($storageRequests)){
  echo "<div class='h3' id='pending-storage-title'>No storage requests</div>";
} else {

?>
<div class="h3" id="history-title">Storage Requests</div>
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

      $date_created     = Util::GetFormattedDateMySQL($item['date_created']);
      $date_needed      = Util::GetFormattedDateMySQL($item['date_needed']);
      $storageRequestID = $item['storageid'];

      ?>
      <tr class='table-dark text-dark' id='pendingStorageRowItem<?php echo $storageRequestID?>'>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item["qty"]; ?></td>
        <td id='rowData-lotnumber-<?php echo $storageRequestID; ?>'><?php echo $item["lotnumber"]; ?></td>
        <td id='rowData-tag-<?php echo $storageRequestID; ?>'><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_created; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td><?php echo $item["notes"]; ?></td>
      </tr>
      <?php
    }
  }

  ?>
  </tbody>
</table>
<?php } //end of else there are storage approved items waiting to be claimed
if (empty($shippingRequests)){
  echo "<div class='h3' id='pending-storage-title'>No shipping requests</div>";
} else {

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
  <tbody id='shipping-requests-body'>
  <?php

  foreach ($shippingRequests as $clientID => $requests) {

    if (empty($requests)){
      continue;
    }
    $clientName = SessionManager::GetClientName($clientID);

    echo "<tr class=h6><td colspan=9>$clientName</td></tr>";

    foreach ($requests as $item) {

      $date_created     = Util::GetFormattedDateMySQL($item['date_created']);
      $date_needed      = Util::GetFormattedDateMySQL($item['date_needed']);
      $shipmentRequestID = $item['shipmentid'];

      ?>
      <tr class='table-dark text-dark' id='pendingShipmentRowItem<?php echo $shipmentRequestID?>'>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item["qty"]; ?></td>
        <td id='rowData-lotnumber-<?php echo $shipmentRequestID; ?>'><?php echo $item["lotnumber"]; ?></td>
        <td id='rowData-tag-<?php echo $shipmentRequestID; ?>'><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_created; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td><?php echo $item["notes"]; ?></td>
      </tr>
      <?php
    }
  }
  ?>
  </tbody>
</table>
<?php }
