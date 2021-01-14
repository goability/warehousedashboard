<?php
namespace Ability\Warehouse;
?>
<table width="202px" style="min-width: 202px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC'; background:antiquewhite;">
  <tr style='background:Silver'>
    <td colspan="2" align="center" style="background:Navy; color:Yellow">
        <H2 style="margin:0px;"><?php echo $currentPalletName; ?></H2>
    </td>
  </tr>
  <tr>
    <td align="center" style="background:Silver; border: 2px solid black;'"><H5 style="margin:0px;"><?php echo $currentPalletQty; ?></H5></td>
    <td align="center" style="background:#FFFFFF;"><?php echo !empty($currentPalletItemName) ? $currentPalletItemName : "Empty pallet"; ?></td>
  </tr>
  <tr style='background:Silver;'>
    <td align="center">BIN</td><td align="center"> <?php echo !empty($currentPalletBinName) ? $currentPalletBinName : "<span style='color:red;'>Not assigned</span>"; ?></td>
  </tr>
  <?php
    if (!$emptyPallet){
  ?>
  <tr>
    <td style='background:Silver' align="center">Client</td><td><?php echo $currentPalletClientName; ?></td>
  </tr>

  <tr>
    <td style='background:Silver' align="center">Lot</td><td><?php echo $currentPalletLotNumber; ?></td>
  </tr>
  <tr>
    <td style='background:Silver' align="center">UoM</td><td><?php echo $currentPalletUoM; ?></td>
  </tr>
  <tr>
    <td style='background:Silver' align="center">Tag</td><td><?php echo $currentPalletTag; ?></td>
  </tr>
<?php }//end empty pallet
?><tr>
  <td colspan="2" align="center">
    <form class="" action="<?php echo("$url_base/action?$query_param_access_token"); ?>" method="post">
      <input type="hidden" id="currentPalletID" name="currentPalletID" value="<?php echo $currentPalletID; ?>">
      <input type="hidden" id="currentPalletName" name="currentPalletName" value="<?php echo $currentPalletName; ?>">
      <input type="hidden" id="currentPalletQty" name="currentPalletQty" value="<?php echo $currentPalletQty; ?>">
      <input type="hidden" id="currentPalletItemName" name="currentPalletItemName"  value="<?php echo $currentPalletItemName; ?>">
      <input type="hidden" id="currentPalletItemID" name="currentPalletItemID" value="<?php echo $currentPalletItemID; ?>">
      <input type="hidden" id="currentPalletOwnerID" name="currentPalletOwnerID" value=<?php echo $currentPalletOwnerID; ?>>
      <input type="hidden" id="currentPalletBinName" name="currentPalletBinName" value="<?php echo $currentPalletBinName; ?>">
      <input type="hidden" id="currentPalletStorageRequestID" name="currentPalletStorageRequestID" value="<?php echo $currentPalletStorageRequestID; ?>">
      <input type="hidden" id="currentPalletLotNumber" name="currentPalletLotNumber"  value="<?php echo $currentPalletLotNumber; ?>">

      <?php

          if ($currentPalletQty==0 && $countStoreTickets==0){
            $storeButtonColor = "Orange";
          }
          else{
            $storeButtonColor = ($countStoreTickets>0) ? "green" : "silver";
          }
          $shipButtonColor = ($countShipTickets>0) ? "green" : "silver";

          if ($currentPalletQty>0){

             if ($countShipTickets>0){
       ?>

      <input type="submit" value="SHIP" name="action" style="background-color:<?php echo $shipButtonColor; ?>">
    <?php }

  }
     if ($countStoreTickets || (!$currentStorageRequestIsClosed || $currentPalletQty==0)){ ?>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <input type="submit" value="STORE" name="action" style="background-color:<?php echo $storeButtonColor; ?>">
<?php }

 ?>
 <input type="submit" value="MOVE" name="action" style="background-color:LightBlue;">

    </form>
  </td>
</tr>
</table>
