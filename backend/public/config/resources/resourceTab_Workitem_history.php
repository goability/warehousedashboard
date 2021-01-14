<?php

namespace Ability\Warehouse;


$currentUserID = SessionManager::GetCurrentUserID();
$dateTimeFilter = ConfigurationManager::GetParameter("EmployeeHistoryFilter");
$storageHistory  = DataProvider::GetEmployeeHistory($currentUserID, 'store', $dateTimeFilter);
$shippingHistory = DataProvider::GetEmployeeHistory($currentUserID, 'ship', $dateTimeFilter);

if (empty($storageHistory)){
  echo "<div class='h3' id='pending-storage-title'>No storage history</div>";
} else {

?>
<div class="h3" id="history-title">History</div>
<table class="table" id="history-table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Quantity</th>
      <th>Lot</th>
      <th>Tag</th>
      <th>Label</th>
      <th>Requested</th>
      <th>Approved</th>
      <th>Stored</th>
    </tr>
  </thead>
  <tbody id='pending-storage-body'>
  <?php

  foreach ($storageHistory as $item) {

      $requestID = $item['id'];

      $storage = new Storage($requestID);
      $date_needed      = Util::GetFormattedDateMySQL($item['date_needed']);
      $storageRequestID = Util::GetFormattedDateMySQL($item['storageid']);
      $isApproved = $storage->IsApproved();
      $isFulfilled= $storage->IsFulfilled();

      $date_stored      = (!$isApproved) ? "Not stored" : Util::GetFormattedDateMySQL($item['date_stored']);


      $date_approved = (!$isApproved) ?
                        "Not approved yet" : Util::GetFormattedDateMySQL($item['approved']);


      ?>
      <tr class='table-dark text-dark' id='requestStorageRowItem<?php echo $storageRequestID?>'>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item["qty"]; ?></td>
        <td id='rowData-lotnumber-<?php echo $storageRequestID; ?>'><?php echo $item["lotnumber"]; ?></td>
        <td id='rowData-tag-<?php echo $storageRequestID; ?>'><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td>
            <?php  echo $date_approved;?>
        </td>
        <td><?php echo $date_stored; ?></td>
        <td></td>
      </tr>
      <?php

  }

  ?>
  </tbody>
</table>
<?php } //end of else there are storage requests
if (empty($shippingHistory)){
  echo "<div class='h3' id='pending-storage-title'>No shipping requests</div>";
}
else{
?>
<div class="h3" id="shipping-requests-title">Shipping Requests</div>

<table class="table" id="pending-shipping-table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Quantity</th>
      <th>Lot</th>
      <th>Tag</th>
      <th>Label</th>
      <th>Requested</th>
      <th>Shipped</th>
    </tr>
  </thead>
  <tbody id='pending-shipping-body'>
  <?php

  foreach ($shippingHistory as $item) {

      $date_needed       = Util::GetFormattedDateMySQL($item['date_needed']);
      $shippingRequestID = Util::GetFormattedDateMySQL($item['shippingid']);
      $shipment = new Shipment($shippingRequestID);
      $isApproved = $shipment->IsApproved();
      $isFulfilled= $shipment->IsFulfilled();

      $date_shipped      = (!$isFulfilled) ? "Not shipped" : Util::GetFormattedDateMySQL($item['date_shipped']);


      $date_approved = (!$isApproved) ?
                        "Not approved yet" : Util::GetFormattedDateMySQL($item['approved']);

      ?>
      <tr class='table-dark text-dark' id='requestShippingRowItem<?php echo $shippingRequestID?>'>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item["qty"]; ?></td>
        <td><?php echo $item["lotnumber"]; ?></td>
        <td><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_needed; ?></td>
        <td><?php echo $date_shipped; ?></td>
      </tr>

      <?php

    }
  ?>
  </tbody>
</table>

<?php } //end of else there are ship requests
