<?php
namespace Ability\Warehouse;

$siteURL          = ConfigurationManager::GetParameter("SiteURL") ;

?>
<h1>Application Utilities</h1>
<div class="container" style="padding:0px; margin:2px;">
  <div class="row">
    <div class="col" style="background:LightBlue;">
      <h2>Database Imports</h2>
    </div>
  </div>
  <div class="row">
    <div class="col" style="background:White;">
      <span><?php echo $statusMessage; ?></span>
    </div>
  </div>

  <div class="row">
    <div class="col" style="background:Blue;color:LightYellow;">
      Provide table-name to source data from
    </div>
  </div>
  <div class="row">
    <div class="col">
      <form class="" action="<?php echo $siteURL . "/Utility" . "?accessToken=$accessToken"; ?>" method="post">
        <input type="hidden" name="action" value="import-data">
        <table style="background:Silver;color:#000000; padding:0px; margin:0px;">
          <tr>
            <td><b>Users</b></td>
            <td></td>
          </tr>
          <tr>
            <td>source table name</td>
            <td>
              <input type="text" name="source-table-name" placeholder="Source table-name" value="" size=30></td>
              <input type="hidden" name="import-type" value="User">

            </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
              <button class="badge badge-primary" name="import">Import Users</button></td>
          </tr>
        </table>
      </form>

      </div>
  </div>
</div>

<span class="badge badge-warning">Import Clients</b></span><br>
<span class="badge badge-warning">Import Products</b></span><br>
<span class="badge badge-warning">Import Bins</b></span><br>
<span class="badge badge-warning">Import Pallets</b></span><br>
<span class="badge badge-warning">Import Inventory</b></span><br>
