<?php

namespace Ability\Warehouse;

$currentUserID = SessionManager::GetCurrentUserID();
$storageItems = DataProvider::GetItemsInStorage($currentUserID);
$accessToken = SessionManager::GetAccessToken();
?>
<span class="h3" id="stored-items-title"></span>
<table class="table">
  <thead>
  <tr>
    <th>Item</th>
    <th>Lot</th>
    <th>Quantity</th>
  </tr>
</thead>
<tbody id='stored-items-body'>

<?php
$totalQty = 0;
foreach ($storageItems as $itemid=>$item) {
  $name = $item['name'];
  $storageitemid = $item['id'];
  echo "<tr class='table-dark text-dark'>";
  echo "<td><form style='display:inline;' class='none' id='productedit$storageitemid' action='?accessToken=$accessToken' method='POST'>
    <input type='hidden' name='ID' id='ID' value=$storageitemid >
    <button type='button' name='button' ";
  echo "onClick=\"document.getElementById('productedit$storageitemid').submit();\">$name</button>
  </form></td>";
  echo "<td>" . $item['lotnumber'] . "</td>";
  echo "<td>" . $item['qty'] . "</td>";
  $totalQty += $item['qty'];

  echo "</tr>";

}
echo "<tr class='table-dark text-dark'>
           <td></td><td></td><td>$totalQty</td>
         </tr>";

?>
</tbody>
</table>
