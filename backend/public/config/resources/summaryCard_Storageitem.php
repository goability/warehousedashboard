<?php

  namespace Ability\Warehouse;

  $currentUserID          = SessionManager::GetCurrentUserID();
  $totalInStorage         = InventoryManager::GetCountOfStoredItems($currentUserID);

  $currentProvider          = SessionManager::GetProvider();
  $currentProviderLogoPath  = $currentProvider ? $currentProvider->GetLogoPath() : null;
  $currentProviderName      = $currentProvider ? $currentProvider->GetDisplayText() : null;

  $totalPendingStorage   = InventoryManager::GetCountOfPendingStorages($currentUserID);
  $totalRecentStorages   = InventoryManager::GetCountOfRecentStorages($currentUserID);

  $totalPendingShipments = InventoryManager::GetCountOfPendingShipments($currentUserID);
  $totalRecentShipments  = InventoryManager::GetCountOfRecentShipments($currentUserID);

  $latestStatusMessage   = "";// TODO: status console

  $providerName          = DataProvider::GetProviderName($currentUserID);
  $providerName          = !empty($providerName) ? $providerName : "not specified";
?>
<div class="card">
<div class="container">
  <div class="row">
    <div class="col-6">
      <img class="card-img-top"
      src="<?php echo('/images/resources/' . $resourceImageHeader);?> "
      alt="Card image cap">
    </div>
    <div class="col-6"
          style="overflow: hidden;
                white-space: wrap;" >

        <h5 class="card-title"><?php echo $displayText; ?></h5>
        <p class="card-text">
            <span class="badge badge-success">Active Products</span> <span id="item-total-storage" class="summary-card-data-strong"></span><br>
            <span class="badge badge-info">Recent Shipped</span> <span id="item-total-recent-shipment" class="summary-card-data-strong"></span><br>
            <span class="badge badge-info">Recent Storage</span> <span id="item-total-recent-storage" class="summary-card-data-strong"></span><br>
            <span class="badge badge-warning">Shipments Pending</span> <span id="item-total-pending-shipment" class="summary-card-data-strong"></span><br>
            <span class="badge badge-warning">Storage Pending</span> <span id="item-total-pending-storage" class="summary-card-data-strong"></span><br>
        </p>
        <?php
        if ($currentProviderName){
          ?>
        <p class="card-text" style="text-align:center;"><small class="text-muted" id="latest-status-message">Storage provided by<br></p>
          <div class="" width=200 height=22>
            <img src="<?php echo $currentProviderLogoPath; ?>" style="max-width:100%; max-height=100%;">
          </div>
        <?php } ?>

    </div>
  </div>
</div>
</div>
<script type="text/javascript">


  $("#item-total-storage").html(<?php echo $totalInStorage; ?>);
  $("#item-total-pending-storage").html(<?php echo $totalPendingStorage; ?>);
  $("#item-total-pending-shipment").html(<?php echo $totalPendingShipments; ?>);
  $("#item-total-recent-storage").html(<?php echo $totalRecentStorages; ?>);
  $("#item-total-recent-shipment").html(<?php echo $totalRecentShipments; ?>);
  $("#latest-status-message").html(<?php echo $latestStatusMessage; ?>);

</script>
