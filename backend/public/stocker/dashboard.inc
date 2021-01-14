<?php
namespace Ability\Warehouse;
?>
<div style='min-width:200px; max-width:220px; background:Tan; border-radius:5px; align-vertical:top; align-horizontal:center;'>
  <table>
  <tr><td>
    <h3>
  <?php
  if ($clientScanMode && $currentFacilityID){
    echo "Scan Client Pallet";

    $last_item_name   = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_ITEM);
    $storageRequestId = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_STORAGE_REQUEST_ID);
    $last_qty         = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_QTY);
    $last_binName     = SessionManager::GetParameter(Constants\SessionVariableNames::STOCKER_CLIENT_SCAN_LAST_BIN);

  }
  else{
    echo "Scan Pallet or Bin";
  }?>
</h3>
  </td></tr>
  <?php
  if ($clientScanMode && !is_null($storageRequestId)){
    $last_binName = empty($last_binName) ? "<span style='color:red;'>Rouge</span>" : $last_binName;
    ?>
  <tr>
    <td>Last Scan:<Br><?php
      echo "$last_qty [$last_item_name";
      if ($last_qty>1){
        echo "s";
      }
      echo "] into Bin $last_binName";
     ?></td>
  </tr>
<?php } ?>
  </table>
</div>
