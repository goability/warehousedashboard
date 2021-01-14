<?php

namespace Ability\Warehouse;

$imagePath = null;
if (!empty($this->ImageFilename)){
  $imagePath =  '/' . RESOURCE_DIR . '/' . $this->DB_Fields['ownerid'] . '/' . static::$ResourceName . '/images/' . $this->ImageFilename;
}
$lastShipmentDate = $lastStorageDate = $countItemsInStorage = 0;
if ($this->ID>0){
  $lastShipmentDate = DataProvider::GetMostRecentShipStoreDateForItem($this->ID, 'shipment');
  if($lastShipmentDate!='pending'){
    $lastShipmentDate = Util::GetFormattedDateMySQL($lastShipmentDate);
  }
  $lastStorageDate  = DataProvider::GetMostRecentShipStoreDateForItem($this->ID, 'storage');
  if($lastStorageDate!='pending'){
    $lastStorageDate = Util::GetFormattedDateMySQL($lastStorageDate);
  }
  $userID = SessionManager::GetCurrentUserID();
  $inv = InventoryManager::GetProductInventory($userID, $this->ID);
  $itemsInStorage = !empty(array_values($inv)[0]) ? array_values(array_values($inv)[0])[0] : null;

  $countItemsInStorage = !is_null($itemsInStorage) ? $itemsInStorage["qty"] : 0;

  if (empty($lastStorageDate)){
    $lastStorageDate = "none";
  }
  if (empty($lastShipmentDate)){
    $lastShipmentDate = "none";
  }
}

?>
<div class="resourceForm">
  <div id="product-inventory-info-strip" class="product-stock-info-strip <?php if ($this->FormMode==='CREATE'){ echo('hidden'); } ?>">
      <div class="row ">
        <div class="col-sm-3">
          <strong>In-Stock :</strong><span id='item-instock-count'><?php echo $countItemsInStorage; ?></span>
        </div>
        <div class="col-sm-9">
          <strong>Most Recent:</strong>
          <span style="background:#DDD2A0">storage: </span>

           <?php echo $lastStorageDate; ?>
        <span style="background:#DDD2A0">shipped: </span><?php echo $lastShipmentDate; ?>
        </div>
      </div>
  </div>
  <div class="card">
    <div class="card-body">
      <?php echo $this->ShowFormNavigationSelect();
      ?>
    </div>

    <div class="card-body resource-item">
      <h5 class="card-title"><?php
            if ($this->FormMode==="CREATE"){
              echo "Add New";
            }
            else{
              echo $this->GetDisplayText();
            }?></h5>
      <?php
        if (!empty($imagePath)){ ?>
        <img  class="card-img-top"
              src="<?php echo($imagePath); ?>"
              alt="<?php echo $this->GetDisplayText(); ?>" >
      <?php  } ?>
          <table>

            <form class="" action="<?php echo(static::$ResourceName . '?accessToken=' . SessionManager::GetAccessToken());?>" method="post">
              <input readonly type="hidden" id="MODE" name="MODE" value="<?php echo $this->FormMode; ?>"></input>
              <input readonly type="hidden" id="ID" name="ID" value ="<?php echo $this->ID; ?>"></input>
              <input type="hidden" name="accessToken" value="<?php echo SessionManager::GetAccessToken();?>" id="accessToken">
                <?php
                  $this->showFormRecordFields();
                ?>
                <tr>
                    <td colspan=2 align=left><button type="submit" name="submit"><?php echo $this->FormMode;?></button></td>
                </tr>
              </form>
                <?php
                $this->showFormdependentCollections();
                ?>
            </table>
    </div>
  </div>
</div>
