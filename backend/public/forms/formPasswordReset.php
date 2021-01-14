<?php

namespace Ability\Warehouse;

/*
Show password reset form.
 - Form is ONLY shown after validation of the AuthCode, which was set when the
     email was sent out and added to the URL

On button click:
  - Verify passwords match
  -  Submit to password reset URL API via cloudService
  -   Handle Result: Forward window.location to /Login

*/
$apiURL = ConfigurationManager::GetParameter("APIURL") . "/Reset";


?>

<form id="reset-password-form" class="form-horizontal"
  onsubmit="PWH_UIService.ChangePassword('<?php echo $accessToken; ?>')"
  method="post" action="<?php echo $siteURL; ?>" >
  <fieldset>
    <legend>Reset Password</legend>
    <div class="form-group">
      <label class="col-sm-4" for="password">Password</label>
      <div class="col-sm-6">
        <input type="password" id="password" class="form-control">
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4" for="vpassword">Password (again)</label>
      <div class="col-sm-6">
        <input type="password" id="vpassword" class="form-control">
        <input type="hidden" name="resetStep" value="reset" id="resetStep">
        <input type="hidden" name="accessToken" value="<?php echo $accessToken;?>" id="accessToken">
        <input type="hidden" name="userID" value="<?php echo $userID;?>" id="userID">
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-12">
        <button id="submit-change-password" type="submit" disabled="true">Reset Password</button>
      </div>
    </div>
  </fieldset>
</form>
<div class="col-sm-12">
  <span style="font-size:smaller;color:Black;" id="passwordResetStatusMessage" name="passwordResetStatusMessage"></span>
</div>
<script type="text/javascript">
  $("#password").focusout(PWH_UIService.validatePassword);
  $("#vpassword").focusout(PWH_UIService.validatePassword);
</script>
