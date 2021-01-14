<?php
namespace Ability\Warehouse;

$providerIDs          = SessionManager::GetOwnedRecordIDs('Provider');
$providerIDArray = array();

if  (!empty($providerIDs)){

//Build CSV of providerIDs
foreach ($providerIDs as $provider) {
  $providerIDArray[] = $provider['id'];
  $id = $provider['id'];
}

$numPalletsTotal      = count(DataProvider::GetOwnedPallets($providerIDArray));
$numPalletsEmpty      = count(DataProvider::GetEmptyPallets($providerIDArray));
$numPalletsPartial    = count(DataProvider::GetLoadedPallets($providerIDArray));
$numPalletsFull       = count(DataProvider::GetFullPallets($providerIDArray));
$numPalletsNeverUsed  = count(DataProvider::GetNeverUsedPallets($providerIDArray));
$numPalletsOnDock     = 0;
$numPalletsShipped    = 0;


//$numPalletsEmpty      += count(DataProvider::GetPalletsAvailableForStorage($providerID,false,null,null,true));
//$numPalletsPartial     += count(DataProvider::GetPalletsAvailableForStorage($providerID, true));;
//$numPalletsFull       += count(DataProvider::GetPalletsAvailableForStorage($providerID,true,null,null,false));;
//$numPalletsOnDock     += 0;
//$numPalletsShipped    += 0;
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
          <p class="card-text">
            <span class="badge badge-success">Empty not used <b><?php echo $numPalletsNeverUsed; ?></b></span><br>
            <span class="badge badge-warning">Empty in Bin <b><?php echo $numPalletsEmpty; ?></b></span><br>
            <span class="badge badge-primary">Partial <b><?php echo $numPalletsPartial; ?></b></span><br>
            <span class="badge badge-danger">Full <b><?php echo $numPalletsFull; ?></b></span><br>
            <span class="badge badge-info">Total <b><?php echo $numPalletsTotal; ?></b></span><br>
          </p>
        </p>
        <p class="card-text"><small class="text-muted"></small></p>

    </div>
  </div>
</div>
</div>

<?php } ?>
