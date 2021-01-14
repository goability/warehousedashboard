<?php
namespace Ability\Warehouse;

$clients = SessionManager::GetClients();

if (empty($clients)){
  echo "<div class='h3' id='pending-storage-title'>No active clients</div>";
}
else{
?>
<div class="h3" id="history-title">Clients</div>

<table class="table" id="history-table">
  <thead>
    <tr>
      <th></th>
      <th>Name</th>
      <th>Email</th>
      <th>Location</th>
      <th>In Storage</th>
      <th>Last Transaction</th>
      <th>Awaiting action</th>
    </tr>
  </thead>
  <tbody id='pending-storage-body'>
  <?php
  $userID = SessionManager::GetCurrentUserID();

  foreach ($clients as $client) {
    $clientID = $client["id"];
    $clientName = $client["name"];
    $actions_needed = "none";
    ?>
    <tr class='table-dark text-dark'>
      <td><button class='fas fa-mask' id='masq_<?php echo $clientID; ?>'
                onclick="PWH_UIService.Masquerade('<?php echo $clientName . "','" . $clientID . "'";?>)"></button>
      <td><?php echo $client["name"]; ?></td>
      <td><?php echo $client["emailaddress"]; ?></td>
      <td><?php echo $client["location"]; ?></td>
      <td><?php echo $client["qty"]; ?></td>
      <td></td>
      <td><?php echo $actions_needed; ?></td>

    </tr>

    <?php
  }
  ?>
  </tbody>
</table>
<?php } //end of else there is item history
