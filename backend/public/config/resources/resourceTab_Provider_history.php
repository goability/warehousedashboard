<?php

namespace Ability\Warehouse;

$dateTimeFilter = ConfigurationManager::GetParameter("ProviderHistoryFilter");
$clientIDs      = SessionManager::GetClientIDs();

foreach ($clientIDs as $clientID) {

  $clientName = SessionManager::GetClientName($clientID);

  echo "<div class='h2' id='history-title'>$clientName</div>";

  $itemHistory = DataProvider::GetItemHistory($clientID, null, $dateTimeFilter);

  if (empty($itemHistory)){
    echo "<div class='h5' id='pending-storage-title'>No storage or shipment history</div>";
  } else {
  ?>
  <table class="table" id="history-table">
    <thead>
      <tr>
        <th>Action</th>
        <th>Item</th>
        <th>Quantity</th>
        <th>Lot</th>
        <th>Tag</th>
        <th>Label</th>
        <th>Created</th>
        <th>Approved</th>
        <th>Fulfilled</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody id='history-body'>
    <?php

    foreach ($itemHistory as $item) {

      $requestID = $item['requestid'];

      $requestType = $item["action"];
      $date_created = Util::GetFormattedDateMySQL($item['created']);
      $date_approved = (null===$item['approved'] || $item['approved']<'1971') ?
                        "0" : Util::GetFormattedDateMySQL($item['approved']);
      $date_fulfilled= (null===$item['fulfilled']) ?
                        "0" : Util::GetFormattedDateMySQL($item['fulfilled']);


      ?>
      <tr class='table-dark text-dark' id='<?php echo("history-row-$requestType-$requestID"); ?>'>
        <td><?php echo $requestType; ?></td>
        <td><?php echo $item["name"]; ?></td>
        <td><?php echo $item["qty"]; ?></td>
        <td><?php echo $item["lotnumber"]; ?></td>
        <td><?php echo $item["tag"]; ?></td>
        <td><?php echo $item["label"]; ?></td>
        <td><?php echo $date_created; ?></td>
        <td><?php echo $date_approved ? $date_approved : "Not approved"; ?></td>
        <td>
          <?php
          if ($date_approved){
            if (!$date_fulfilled){
              echo "<button class='btn-sm' id='button-cancel-ship-store-$requestID' onclick=\"PWH_UIService.cancelShipStore($requestID, '$this->accessToken', true);\">CANCEL $requestType Approval</button>";
            } else {
              echo $date_fulfilled;
            }
          }
          else{
            echo "<button class='btn-sm' id='button-cancel-ship-store-$requestID' onclick=\"PWH_UIService.cancelShipStore($requestID, '$this->accessToken', false);\">CANCEL $requestType Request</button>";
          }
          ?>
        </td>
        <td><?php echo $item["notes"]; ?></td>
      </tr>
      <?php
    }
    ?>
    </tbody>
  </table>
  <?php } //end of else there is item history
}
