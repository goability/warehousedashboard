<?php
namespace Ability\Warehouse;

/*
Show password reset form - Get Emails
 - Email must be typed twice
 - Minimal Captcha
 - Temp auth-code must be added to this form to block bots

On button click:
  - Submit form to api/Reset with $AuthCode in GET
  - Show status message indicating that "Reset link sent if account was valid matching the email address ";
*/

//Get a temp auth-code that is valid in the DB for a short time
// NO USERID is provided for this, and that is ok.  This is used to ensure we are the ones allowing this to happen
//Everytime this page is built, a new authCode is constructed and starts the flow
namespace Ability\Warehouse;

Log::debug('getting a temp authcode');
$timeoutSecs = ConfigurationManager::GetParameter("Sessioning")->PasswordRecoveryTimeoutSecs;

$authCode = DataProvider::GET_AUTH_CODE($timeoutSecs);
$siteURL = ConfigurationManager::GetParameter('SiteURL');

?>
<div class="container" id="forgotPasswordContainer" style="display:inline;">
  <form id="reset-password-form-email" class="form-horizontal" action="<?php echo $siteURL; ?>" onsubmit="PWH_UIService.SendPasswordResetLink('<?php echo $authCode; ?>')" method="post">

    <fieldset>
      <legend>Reset Password</legend>
      <div class="form-group">
        <label class="col-sm-4" for="emailaddress">Email Address</label>
        <div class="col-sm-6">
          <input type="text" name="emailaddress" id="emailaddress" placeholder="Enter your email address" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-4" for="emailaddressv">Email Address (verify)</label>
        <div class="col-sm-6">
          <input type="text" name="emailaddressv" id="emailaddressv" placeholder="Enter your email address again please" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-12">
          <button id="submit" name="submit" type="submit" name="button" disabled="true">Send Reset Link</button>
          <input type="hidden" name="resetStep" value="request" id="resetStep">
        </div>
      </div>
    </fieldset>
  </form>
  <div class="col-sm-12">
    <span style="font-size:smaller;color:Black;" id="passwordResetStatusMessage" name="passwordResetStatusMessage"></span>
  </div>
</div>
<script type="text/javascript">
  //$("#emailaddress").focusout(PWH_UIService.validateEmailAddressesMatch);
  $("#emailaddressv").focusout(PWH_UIService.validateEmailAddressesMatch);
  $("#passwordResetStatusMessage").text('an email will be sent if an account is found matching that emailaddress');


</script>
