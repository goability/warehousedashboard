<?php
namespace Ability\Warehouse;

$currentUserID = SessionManager::GetCurrentUserID();
$palletInventory = InventoryManager::GetPalletInventory([$this->ID], $currentUserID);

echo "<h2>" . $this->GetDisplayText() . " Inventory</h2>";

echo "<ul class='list-group'>";
foreach ($palletInventory as $storage) {
  echo "<li class='list-group-item'>" . $storage['itemname'] . " - " . $storage['item_qty'];
}
?>
</ul>
