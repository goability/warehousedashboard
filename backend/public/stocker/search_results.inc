<?php
namespace Ability\Warehouse;

/*
* Display search results

If something was found, searchType will be BIN or PID,
otherwise the object creation flow starts

Scenario 1:  Objects are known
Bins:  pallets[PID]

Pallets: pid,clientName,binName,UoM,Lot,Tag,Description

*/
//echo "displayMode; $displayMode";
if ( ($resource=='scan' && empty($searchString)) ||
    ($resource=='action' && empty($currentPalletID) && empty($currentBinID))){
  echo "Empty request";
}
else{

  $spanSize           = ($searchType=='BIN') ? 5:3;
  $displayModeToggle  = $displayMode=='INFO' ? 'ACTION' : 'INFO';

  switch($searchType){
    case 'BIN':
    ?>
    <table width="202px" style="min-width: 202px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC'; background:antiquewhite;">
      <tr style='background:Silver'>
        <td colspan="2" align="center" style="background:Navy; color:Yellow">
            <H2 style="margin:0px;"><?php echo $currentBinName; ?></H2>
        </td>
      </tr>
      <?php
      if (!empty($storageRequests)){
        ?>
      <tr style='background:Silver'>
        <td colspan="2" align="center" style="background:Navy; color:Yellow">
          <form class="" action="<?php echo("$url_base/scan?$query_param_access_token"); ?>" method="post">
            <table align="left" width="202px" style="display: inline; min-width: 202px; max-width: 204px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC'; background:Black;">

                  <tr>
                    <td style='background:#000000; color:Yellow' align="right">PID </td>
                    <td>
                      <input type='text' name="palletName-scanned" id="palletName-scanned" size=12>
                      <button type="submit" name="action" value="addpallet">add</button>
                    </td>
                  </tr>
                  <tr>
                    <td style='background:#000000; color:Yellow' align="right">Ticket </td>
                    <td>

                      <select id='storage-requestid-scanned' name='storage-requestid-scanned'>
                        <?php
                           echo "<option value=0>Select a ticket</option>";
                           $requestIDs = array();
                           foreach ($storageRequests as $item) {
                             $storageRequestID          = $item['storageid'];
                             if (in_array($storageRequestID,$requestIDs)){
                               continue;
                             }
                             $requestIDs[] = $storageRequestID;


                             $currentStorageRequestQty  = $item['qty'];
                             $lotNumber                 = $item['lotnumber'];
                             // TODO: optimize on db side, don't call this way
                             $currentInventory       = InventoryManager::GetStorageRequestInventory($storageRequestID);
                             $storageRequestCountInStorage = $currentInventory[0]['current_qty'];
                             $ticketQtyRemaining = $currentStorageRequestQty - $storageRequestCountInStorage;

                             $storageItemDisplayText = $item['displaycode'] . " - " . $ticketQtyRemaining;

                             if (strlen($lotNumber)>0){
                               $storageItemDisplayText .= " | $lotNumber";
                             }
                             echo "<option value='$storageRequestID'>$storageItemDisplayText</option>";
                           }
                        ?>
                      </select>
                    </td>
                  </tr>
            </table>
          </form>
        </td>
      </tr>
    <?php
  }//end there are storage requests, show ad-hoc pallet create bar
      if (count($binInventory)>0){
        foreach ($binInventory as $palletName=>$pallet) {

            $palletItemName   = $pallet!=null ? $pallet[0]["itemname"] : "";
            $palletClientName = $pallet!=null ? $pallet[0]["clientname"] : "";
            $palletItemQty    = $pallet!=null ? $pallet[0]["item_qty"] : "";

            echo "<tr>";
            echo "<td style='background:#f0c284'>" . trim($palletName) . "</td>";
            echo "<td>" . $palletItemName . "</td>";
            if ($palletItemQty<1){
              $background = "red";
              $palletItemQty = "<b>0</b>";
            }
            else{
              $background = "silver";
            }
            echo "<td style='background:$background; text-align:center; padding=10px;'>" .$palletItemQty . "</td>";
            echo "<td>" .$palletClientName . "</td>";
            echo "</tr>";
        }
      }
      else{
        echo "<tr>";
        echo "<td style='background:#f0c284'><b>Empty Bin</b></td></tr>";
      }
      break;
    default:
      //Add new flow

      if ($clientScanMode){

        if (!isset($currentFacilityID))
        {
          $facilities = SessionManager::GetFacilitiesForEmployee();

          if (is_null($facilities)){
            echo "No assigned facility for this employee.";
          }
          else{
          ?>
          <table><tr><td colspan=2>
            <form class="" action="<?php echo("$url_base/clienttoggle?$query_param_access_token"); ?>" method="post">
              <select name="facilityid-scanned">
                <?php

                foreach ($facilities as $facility) {
                    if(!empty($facility)){
                      $id   = $facility['id'];
                      $name = $facility['shortcode'];
                      echo "<option value=$id>$name</option>";
                    }
                }
                ?>

              </select>
              <input type="submit" value="set site" />
            </form>

          </td></tr></table>

          <?php
          }
        }


      }
      else{
        echo "<br><b>NO PID or BIN FOUND</b>";
      }
}
} //end else there was a search string provided
