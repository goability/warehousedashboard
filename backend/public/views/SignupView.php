<?php
namespace Ability\Warehouse;
/*
Display a login form with Forgot username/password links
Include text region for message to show login failures/logged out, etc

*/

?>
<div class="content-container" id="signupContainer">
  <form class="">
    <div class="row" style="">
      <div class="col-2">Email Address</div>
      <div class="col-2"><input type="text" name="emailaddress" id="emailaddress"></div>
      <div class="w-100"></div>
      <div class="col-2">Profile Name</div>
      <div class="col-2"><input type="text" name="username" id="username"></div>
      <div class="w-100"></div>
      <div class="col-2">Password</div>
      <div class="col-2"><input type="password" name="password" id="password"></div>
      <div class="w-100"></div>
      <div class="col-2">Verify Password</div>
      <div class="col-2"><input type="password" name="vpassword" id="vpassword"></div>
      <div class="w-100"></div>
      <div class="col-2">First Name</div>
      <div class="col-2"><input type="text" name="firstname" id="firstname"></div>
      <div class="w-100"></div>
      <div class="col-2">Last Name</div>
      <div class="col-2"><input type="text" name="lastname" id="lastname"></div>
      <div class="w-100"></div>
      <div class="col-2">City</div>
      <div class="col-2"><input type="text" name="city" id="city"></div>
      <div class="w-100"></div>
      <div class="col-2">State</div>
      <!-- https://www.freeformatter.com/usa-state-list-html-select.html -->
      <div class="col-2">
        <select id="signup-state">
          	<option value="AL">Alabama</option>
          	<option value="AK">Alaska</option>
          	<option value="AZ">Arizona</option>
          	<option value="AR">Arkansas</option>
          	<option value="CA">California</option>
          	<option value="CO">Colorado</option>
          	<option value="CT">Connecticut</option>
          	<option value="DE">Delaware</option>
          	<option value="DC">District Of Columbia</option>
          	<option value="FL">Florida</option>
          	<option value="GA">Georgia</option>
          	<option value="HI">Hawaii</option>
          	<option value="ID">Idaho</option>
          	<option value="IL">Illinois</option>
          	<option value="IN">Indiana</option>
          	<option value="IA">Iowa</option>
          	<option value="KS">Kansas</option>
          	<option value="KY">Kentucky</option>
          	<option value="LA">Louisiana</option>
          	<option value="ME">Maine</option>
          	<option value="MD">Maryland</option>
          	<option value="MA">Massachusetts</option>
          	<option value="MI">Michigan</option>
          	<option value="MN">Minnesota</option>
          	<option value="MS">Mississippi</option>
          	<option value="MO">Missouri</option>
          	<option value="MT">Montana</option>
          	<option value="NE">Nebraska</option>
          	<option value="NV">Nevada</option>
          	<option value="NH">New Hampshire</option>
          	<option value="NJ">New Jersey</option>
          	<option value="NM">New Mexico</option>
          	<option value="NY">New York</option>
          	<option value="NC">North Carolina</option>
          	<option value="ND">North Dakota</option>
          	<option value="OH">Ohio</option>
          	<option value="OK">Oklahoma</option>
          	<option value="OR">Oregon</option>
          	<option value="PA">Pennsylvania</option>
          	<option value="RI">Rhode Island</option>
          	<option value="SC">South Carolina</option>
          	<option value="SD">South Dakota</option>
          	<option value="TN">Tennessee</option>
          	<option value="TX">Texas</option>
          	<option value="UT">Utah</option>
          	<option value="VT">Vermont</option>
          	<option value="VA">Virginia</option>
          	<option value="WA">Washington</option>
          	<option value="WV">West Virginia</option>
          	<option value="WI">Wisconsin</option>
          	<option value="WY">Wyoming</option>
          </select>
      </div>
      <div class="w-100"></div>
      <div class="col-2">Zipcode</div>
      <div class="col-2"><input type="text" name="zipcode" id="zipcode"></div>
      <div class="w-100"></div>
      <div class="col-12" style="background-color: lightYellow; margin-left:3px;">
        <h4>How can we help you ?</h4>
        <input type="checkbox" name="user-type-request" id="usertype-Client" value="client">
          <label for="usertype-Client">I need to store items</label><br>
        <input type="checkbox" name="user-type-request" id="usertype-User" value="user">
          <label for="usertype-User">I would like more information</label><br>
        <input type="checkbox" name="user-type-request" id="usertype-Facilityowner" value="owner">
          <label for="usertype-Facilityowner">I own a facility</label><br>
        <input type="checkbox" name="user-type-request" id="usertype-Provider" value="provider">
          <label for="usertype-Provider">I provider storage services</label><br>
        <input type="checkbox" name="user-type-request" id="usertype-Employee" value="employee">
          <label for="usertype-Employee">I work for a provider or facility</label><br>
        <input type="checkbox" name="user-type-request" id="usertype-Client" value="client">
          <label for="usertype-Client">I receive shipments from a client at this facility</label><br>
      </div>
    </div>
    <div class="row">
      <div class="col-2" style="font-size: smaller;"><a href="/Login">Login instead</a></div>
      <div class="col-2" style="display:inline;">
        <button type="button" name="login" value="Login" onclick="PWH_UIService.signupUser()">Signup</button>
          <div style="font-size:smaller;color:Red;" id="signupStatusMessage" name="signupStatusMessage"></div>
        </div>
      </div>
    </div>
  </form>
</div>
<script type="text/javascript">
  $("#password").focusout(PWH_UIService.validatePassword);
  $("#vpassword").focusout(PWH_UIService.validatePassword);
</script>
