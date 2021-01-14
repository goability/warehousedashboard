<?php
namespace Ability\Warehouse;
?>
    <form class="" action="<?php echo("$url_base/action?$query_param_access_token"); ?>" method="post">
<table width="202px" style="min-width: 202px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC'; background:antiquewhite;">
  <tr style='background:Silver'>
    <td colspan="2" align="center" style="background:Navy; color:Yellow">
        <H2 style="margin:0px;"><?php echo $currentPalletName; ?></H2>
    </td>
  </tr>
  <tr>
    <td style='background:Red; text-align:center;color:#FFFFFF;' align="left" colspan="2" align="center"><b>MOVE PALLET</b></td>
  </tr>
  <tr>
    <td align="center" style="background:Silver;"><H2 style="margin:0px;"><?php echo $currentPalletQty; ?></H2></td>
    <td align="center" style="background:#FFFFFF;"><?php echo !empty($currentPalletItemName) ? $currentPalletItemName : "Empty pallet"; ?></td>
  </tr>
  <tr>
    <td style='background:Silver' align="center">FROM </td>
    <td align="center">
      <?php
        if (strlen($currentPalletBinName)<1){
          echo "<span style='color:Red;'><B>Not Assigned</b></span>";
        }
        else{
          echo $currentPalletBinName;
        }
      ?>
    </td>
  </tr>
  <tr>
    <td style='background:#000000; color:Yellow'  align="right">TO BIN </td><td>
      <input type='text' name="binName-scanned" id="binName-scanned" size=16></td>
  </tr>

  <tr>
    <td colspan="2" align="center">
        <input type="hidden" id="currentPalletID" name="currentPalletID" value="<?php echo $currentPalletID; ?>">
        <input type="hidden" id="currentPalletName" name="currentPalletName" value="<?php echo $currentPalletName; ?>">
        <input type="hidden" id="currentPalletBinID" name="currentPalletBinID" value="<?php echo $currentPalletBinID; ?>">
        <input type="hidden" id="currentPalletQty" name="currentPalletQty" value="<?php echo $currentPalletQty; ?>">
        <input type="hidden" id="currentPalletItemName" name="currentPalletItemName" value="<?php echo $currentPalletItemName; ?>">
        <input type="hidden" id="currentPalletBinName" name="currentPalletBinName"  value="<?php echo $currentPalletBinName; ?>">


        <input type="submit" value="MOVE" name="action" style="background-color:LightBlue;">
    </td>
  </tr>
</table>
</form>
