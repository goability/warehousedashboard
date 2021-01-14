<?php

namespace Ability\Warehouse;

$pendingStorageItems = DataProvider::GetItemsPendingStorage(SessionManager::GetCurrentUserID());
$pendingShipmentItems = DataProvider::GetItemsPendingShipment(SessionManager::GetCurrentUserID());

if (empty($pendingStorageItems)){
  echo "<div class='h3' id='pending-storage-title'>Pending Storage (none)</div>";
}else {
?>
<div class="h3" id="pending-storage-title">Pending Storage</div>

<table class="table" id="pending-storage-table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Quantity</th>
      <th>Requested on</th>
      <th>Needed by</th>
      <th>Approved</th>
    </tr>
  </thead>
  <tbody id='pending-storage-body'>
  <?php

  foreach ($pendingStorageItems as $item) {

    $date_created = $item['date_created'];
    $date_needed = $item['date_needed'];

    $date_approved = (null===$item['date_approved']) ?
                      "Not approved yet" :
                      Util::GetFormattedDateMySQL($item['date_approved']);
    ?>
    <tr class='table-dark text-dark'>

      <td><?php echo $item["name"]; ?></td>
      <td><?php echo $item["qty"]; ?></td>
      <td><?php echo Util::GetFormattedDateMySQL($date_created); ?></td>
      <td><?php echo Util::GetFormattedDateMySQL($date_needed); ?></td>
      <td><?php echo $date_approved; ?></td>
    </tr>

    <?php
  }

  ?>
  </tbody>
</table>
<?php } //end of else there are storage pending items
if (empty($pendingShipmentItems)){
  echo "<div class='h3' id='pending-shipment-title'>Pending Shipments (none)</div>";
} else {
?>

<div class="h3" id="pending-shipments-title">Pending Shipment</div>

<table class="table" id="pending-shipments-table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Quantity</th>
      <th>Requested on</th>
      <th>Needed by</th>
      <th>Approved</th>
    </tr>
  </thead>
  <tbody id='pending-shipments-body'>
  <?php

  foreach ($pendingShipmentItems as $item) {

    $date_created = Util::GetFormattedDateMySQL($item['date_created']);
    $date_needed  = Util::GetFormattedDateMySQL($item['date_needed']);

    $date_approved = (null===$item['date_approved']) ?
                      "Not approved yet" :
                      Util::GetFormattedDateMySQL($item['date_approved']);
    ?>
    <tr class='table-dark text-dark'>

      <td><?php echo $item["name"]; ?></td>
      <td><?php echo $item["qty"]; ?></td>
      <td><?php echo $date_created; ?></td>
      <td><?php echo $date_needed; ?></td>
      <td><?php echo $date_approved; ?></td>
    </tr>

    <?php
  }

  ?>
  </tbody>
</table>
<?php }//end of if there are items pending for shipment ?>
