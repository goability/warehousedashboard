<?php
/*
Display a login form with Forgot username/password links
Include text region for message to show login failures/logged out, etc

*/
namespace Ability\Warehouse;

$apiURL = ConfigurationManager::GetParameter("APIURL") . "/Login";
$callbackMethodName = "CloudServiceResponseHandlers.login";

?>
<div class="container" id="loginContainer" style="display:inline;margin-left:5px;">
  <form methd="POST">
    <div class="row" style="max-width:400;">
      <div class="col-1">Username</div>
      <div class="col-1"><input type="text" name="username" id="username"></div>
      <div class="w-100"></div>
      <div class="col-1">Password</div>
      <div class="col-1"><input type="password" name="password" id="password"></div>
    </div>
    <div class="row">
      <div class="col-1" style="font-size: smaller;"><a href="/Forgot">Forgot password</a></div>
      <div class="col-3" style="display:inline;">
        <button type="button" name="login" id="loginbutton" value="Login" onclick="PWH_UIService.loginUser('<?php echo $apiURL . '\',\'' . $callbackMethodName; ?>')">Login</button>
        &nbsp;<span style="font-size:smaller;color:Blue;" id="loginStatusMessage" name="loginStatusMessage">
          <?php if (isset($loginMessage)){ echo "<br>".$loginMessage;}?>
          </span>
          <br><a href="/Signup">Create Account</a>
      </div>
    </div>
  </form>
  <script type="text/javascript">
  function submitForm(event) {
      if (event.keyCode === 13) {
        // Cancel the default action, if needed
        event.preventDefault();
        // Trigger the button element with a click
        document.getElementById("loginbutton").click();
      }
    }
    var inputpassword = document.getElementById("password");
    inputpassword.addEventListener("keyup",(event)=>{
          submitForm(event);
        });
    var inputusername = document.getElementById("username");
    inputusername.addEventListener("keyup",(event)=>{
          submitForm(event);
        });
  </script>
</div>
