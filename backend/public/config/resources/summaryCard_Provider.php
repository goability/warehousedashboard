<?php
namespace Ability\Warehouse;

$employees = SessionManager::GetEmployees();
$providers = SessionManager::GetOwnedRecordIDs('Provider');
$clients = SessionManager::GetClients();
$numProviders = !empty($providers)  ? count($providers) : 0;
$numClients   = !empty($clients)    ? count($clients)   : 0;
$numEmployees = !empty($employees)  ? count($employees) : 0;

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
                white-space: nowrap;" >

        <h5 class="card-title"><?php echo $displayText; ?></h5>
        <p class="card-text">
          <span class="badge badge-warning">Providers <b><?php echo $numProviders; ?></b></span><br>
          <span class="badge badge-secondary">Clients <b><?php echo $numClients; ?></b></span><br>
          <span class="badge badge-success">Employees <b><?php echo $numEmployees; ?></b></span><br>
        </p>
        <p class="card-text"><small class="text-muted">You are owner of <?php echo $numProviders; ?> providers</small></p>
    </div>
  </div>
</div>
</div>
