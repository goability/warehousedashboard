<?php

namespace Ability\Warehouse;

$url_base = ConfigurationManager::GetParameter('StockerDeployInSubdir') ? "/stocker" : "";
$query_param_access_token = "accessToken=$accessToken";

$url_logout             = "$url_base/logout?$query_param_access_token";
$url_ship               = "$url_base/ship?$query_param_access_token";
$url_store              = "$url_base/store?$query_param_access_token";
$url_toggle_searchType  = "?$query_param_access_token";

?>
<form class="" action="<?php echo("$url_base/scan?$query_param_access_token"); ?>" method="post">
  <table width="204px" style="width: 204px; max-width: 204px; margin:0px; padding:0px; border-spacing: 0px; border-color:'#FFFFCC'; background:Black;">
    <tr>
      <td>
          <input type="input" id="searchString" name="searchString" size="16">
          <input type="submit" style="background-color:<?php echo ($clientScanMode) ? 'Gold' : 'LightSilver'; ?>"
             value="<?php
            echo ($clientScanMode) ? "scan" : "find";
          ?>">
      </td>
    </tr>
  </table>
</form>
</tr>
</table>
<script type="text/javascript">
    var input = document.getElementById("searchString");
    input.addEventListener("keyup", function(event) {

    if (event.keyCode === 13) {
      var searchString = input.value;
      //alert(searchString);
    }
    });
</script>
