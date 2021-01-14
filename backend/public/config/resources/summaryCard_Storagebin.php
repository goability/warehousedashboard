<?php

namespace Ability\Warehouse;
$providerIDs          = SessionManager::GetAccessibleRecordIDs('Provider');

if (!empty($providerIDs)){

  $providerIDArray      = null;
  //Build CSV of providerIDs
  foreach ($providerIDs as $provider) {
    $providerIDArray[] = $provider['id'];
  }

  $numBinsAvailable     = SessionManager::GetCountOfAccessibleRecords('Storagebin');
  $numBinsPartialData   = DataProvider::GetBinsLoaded($providerIDArray,false);
  $numBinsPartial       = is_null($numBinsPartialData) ? 0 : count($numBinsPartialData);
  $numBinsEmptyData     = DataProvider::GetBinsEmpty($providerIDArray,false);
  $numBinsEmpty         = is_null($numBinsEmptyData) ? 0 : count($numBinsEmptyData);
  $numBinsFullData      = DataProvider::GetBinsFull($providerIDArray,false);
  $numBinsFull          = is_null($numBinsFullData) ? 0 : count($numBinsFullData);

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
          <span class="badge badge-success">Empty <b><?php echo $numBinsEmpty; ?></b></span><br>
          <span class="badge badge-primary">Partial <b><?php echo $numBinsPartial; ?></b></span><br>
          <span class="badge badge-danger">Full <b><?php echo $numBinsFull; ?></b></span><br>
          <span class="badge badge-info">Total <b><?php echo $numBinsAvailable; ?></b></span><br>
        </p>
        <p class="card-text"><small class="text-muted"></small></p>

    </div>
  </div>
</div>
</div>
<?php } ?>
