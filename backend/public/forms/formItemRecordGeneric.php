<?php

namespace Ability\Warehouse;

$imagePath = null;
if (!empty($this->ImageFilename)){
  $imagePath =  RESOURCE_DIR . '/' . $this->DB_Fields['ownerid'] . '/' . static::$ResourceName . '/images/' . $this->ImageFilename;
}
?>
<div class="resourceForm">
  <div class="card">
    <div class="card-body">
      <?php echo $this->ShowFormNavigationSelect(); ?>
    </div>
    <div class="card-body">
      <h2 class="card-title"><?php

      if ($this->FormMode==="CREATE"){
        echo "Add New";
      }
      else{
        echo $this->GetDisplayText();
        if (!empty($this->ExtraProfileHeader)){
          echo "<br><h5>" . $this->ExtraProfileHeader . "</h5>";
        }
      }
      ?>

    </h5>
      <?php
        if (!empty($imagePath)){ ?>
        <img  class="card-img-top"
              src="<?php echo($imagePath); ?>"
             alt="<?php echo $this->GetDisplayText(); ?>" >
      <?php  } ?>
    </div>
    <div class="card-body">
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
