<?php
namespace Ability\Warehouse;

  if ($loggedin){

?>
<br><table width="202px" border="0" style="max-width: 204px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC'; background:'Silver'">
  <tr>
    <td colspan="3"><?php echo $statusMessage;?></td>
  </tr>
  <tr style="background:Silver">
    <td>
      <?php echo "<a href='$url_base/dashboard?$query_param_access_token'>$userName</a>"; ?></td>
    <td style='background:#000000;' align='center'>
      <form class="" action="<?php echo("$url_base/clienttoggle?$query_param_access_token"); ?>" method="post">
        <input type="hidden" id="clientScanMode" name="clientScanMode"  value="<?php echo $clientScanMode; ?>">
        <input type="submit" style="width:5em;height:2em;"
          <?php
          if ($clientScanMode){
            echo "value='exit'";
          }
          else{
            echo "value='client'";
          }?>
          ><br><span style='color:Yellow;'><B><?php echo $currentFacilityName; ?></B></span>
      </form></td>
    <td align="right"><a href="<?php echo $url_logout; ?>">LOGOUT</a></td>
  </tr>
</table>

<script type="text/javascript">


<?php
    echo "document.getElementById('searchString').focus();";
if ($viewToInclude=='action.inc'){
  //Set component focus for next SCAN
      if ($actionType=='SHIP' || $actionType=='STORE'){

          $enforcePalletScan  = ($actionType=='SHIP') ? $enforcePalletScanOnStore : $enforcePalletScanOnShip;
          $enforceBinScan     = ($actionType=='SHIP') ? $enforceBinScanOnStore    : $enforceBinScanOnShip;

          //Always prepopulate the pallet if it is known and allowed
          if (!$enforcePalletScan){
            if (!empty($currentPalletName)){
              echo "document.getElementById('palletName-scanned').value = '$currentPalletName';";
            }
          }
          if (isset($next_pallet_scanQty)){
              echo "document.getElementById('qty-scanned').value = '$next_pallet_scanQty';";
          }
          if (!$enforceBinScanOnStore && !empty($currentPalletBinName)){
             echo "document.getElementById('binName-scanned').value = '$currentPalletBinName';";
          }




          //Client Scan Mode
          //
          //  -- Always disable PalletID, never enforce a second scan
          //
          //  -- If no active ticket
          //      - set focus to BIN for pallet assignment
          //      - allow picking of a ticket and setting of qty for this pallet
          //       This represents the first scan towards a ticket
          //
          //  -- If a ticket has already started
          //        -  Disable ticket-select menu
          //        -  Pre-populate QTY with last amount (allow edit)
          //        -  Pre-populate BinName (allow edit)
          //        -  Set focus to submit
          //
          //


          if ($clientScanMode){
            if ( empty($currentPalletBinName)){
              echo "document.getElementById('binName-scanned').focus();";
            }
            else{
              echo "document.getElementById('storebutton').focus();";
            }
          }
          else{
            if ($enforcePalletScan){
              echo "document.getElementById('palletName-scanned').focus();";
            }
            else{
              echo "document.getElementById('qty-scanned').focus();";
            }
          }
      }
      else if ($actionType=='MOVE'){
          echo "document.getElementById('binName-scanned').focus();";
      }
      else{
        echo "document.getElementById('searchString').focus();";
      }
    }
?>
</script>
<?php } ?>
