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
      <div class="card-title h5"><?php

      if ($this->FormMode==="CREATE"){
        echo "Add New";
      }
      else{
        echo $this->GetDisplayText();
        if (!empty($this->ExtraProfileHeader)){
          echo "<h5>" . $this->ExtraProfileHeader . "</h5>";
        }
      }
      ?>
      </div>
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
              <tr>
                <td colspan=2>
                  <?php if ( !empty($statusMessage)){
                      echo $statusMessage . "<br>";
                    }
                  ?>
                  <form class="" action="<?php echo(static::$ResourceName . '?accessToken=' . SessionManager::GetAccessToken());?>" method="post">

                    <b>Change password</b>
                  <table style="background:Tan;">
                    <tr>

                      <td>
                        <input readonly type="hidden" id="MODE" name="MODE" value="<?php echo $this->FormMode; ?>"></input>
                        <input readonly type="hidden" id="ID" name="ID" value ="<?php echo $this->ID; ?>"></input>
                        <input type="hidden" name="accessToken" value="<?php echo SessionManager::GetAccessToken();?>" id="accessToken">

                        <input type="hidden" name="change-password" value="1">
                        New Password:<input type="password" name="new-password" id="password" maxlength="20"></td>
                      <td>Confirm:<input type="password" name="password-confirm" id="vpassword" maxlength="20"></td>
                      <td colspan="2" align="center"><button type="submit" id="submit-change-password">Change Password</button></td>
                    </tr>
                  </table>
                </form>
                <script type="text/javascript">
                  $("#password").focusout(PWH_UIService.validatePassword);
                  $("#vpassword").focusout(PWH_UIService.validatePassword);
                </script>

                </td>
              </tr>
                <?php
                $this->showFormdependentCollections();
                ?>
            </table>
    </div>
  </div>
</div>
