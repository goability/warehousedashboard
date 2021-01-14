class CloudServiceResponseHandlers {

  /*
    Response handler for a successful record association
     -- Add the item to the list
     @param:
  */
  static associate(elementID, primaryResourceName, primaryResourceID, associativeCollectionName, foreignResourceName, foreignResourceIndexFieldname, foreignResourceID) {

    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();
    var associatedText = $(`#${elementID}SELECT :selected`).text();
    var associatedID = $(`#${elementID}SELECT :selected`).val();
    var listItemHTML = `<li id=${elementID}-item-${foreignResourceID} class='list-group-item' aria-hidden='true' style='margin:0; padding:1;'>`;
    var itemHTML = `<i id='nav-record-item-disassociate-${foreignResourceID}'
                      class='fa fa-trash nav-record-item-disassociate'
                      onclick =\"
                      PWH_UIService.Disassociate('${elementID}', '${apiURL}/${primaryResourceName}',
                            '${associativeCollectionName}',
                            '${foreignResourceName}',
                            '${foreignResourceIndexFieldname}',
                            '${primaryResourceID}',
                            '${foreignResourceID}');
                          \";></i>${associatedText}`;

    $(`#${elementID}LIST`).append(listItemHTML + itemHTML+ "</li>");
  }

  static disassociate(elementID, resourceID) {

    var elementName = `${elementID}-item-${resourceID}`;
    $(`#${elementID}-item-${resourceID}`).remove();
  }
  static authenticate(elementID, userID){

     if (userID < 1){
        $('#loginStatusMessage').text('Error Logging in');
      }
      else{
        $('#loginStatusMessage').text('Authenticated !');
      }
  }
  static login(elementID, userID, authCode){

    var siteURL = PWH_UIService.getSiteURL();

     if (userID < 1){
        $('#loginStatusMessage').text('Error Logging in');
      }
      else{

        $('#loginStatusMessage').text('Logged in!');

        General.createCookie('userID', `${userID}`);
        //Forward back to Login with authCode.  If matches with what was
        //  written by the API Login transaction, then a session will be created
        //  design:  this authCode has a very short lifespan and is removed from DB after validation
        window.location = `${siteURL}/Login?authCode=${authCode}&userID=${userID}`;
      }
  }
  static SendPasswordResetLink(elementID, emailaddress, success){

    var msg = `Please check your email.  A password reset link has been sent to ${emailaddress} which will allow you to reset your password.  The link will expire in 3 minutes.`;
    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();
    if (!success){
      msg = `Invalid request.  Please try again`;
    }
    $("#passwordResetStatusMessage").text(msg);

    window.location.assign(`${siteURL}/Login`);
  }
  static ChangePassword(elementID, success){

    var msg = "Password reset ";
    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();
    if (!success){
      msg += "Password reset failed."
    }
    else{
      msg += " Success.  You can now login with your new password.";
    }

    window.location.assign(`${siteURL}/Login`);
  }
  static signup(elementID, userID){

    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    if (userID < 1){
       $('#signupStatusMessage').replaceWith('Error Signing up, please try again.');
     }
     else{
       var link = $("<a href='/Login'>Login</a>")
       $('#signupStatusMessage').add("span").html(`Welcome ! You can now <a href="${siteURL}/Login">Login</a>`);

       setTimeout( () => {window.location = `${siteURL}/Login`}, 2000);
     }
  }
  static approveStorageRequest(storageID, userID){

    var apiURL = PWH_UIService.getAPIURL() + `/storage/${storageID}/approve/${userID}`;
    var result = CloudService.Call("PUT", null, apiURL,null,null);

    $(`#requestStorageRowItem${storageID}`).hide();
  }
  static approveStorageRequestFailure(storageID){

    PWH_UIService.showHideApprove(storageID, 'Storage', false)
  }
  static cancelapproval(type, requestID){

    $(`#history-row-${type}-${requestID}`).hide();
  }
}
