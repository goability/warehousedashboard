class PWH_UIService{

  static getAPIURL(){
    var apiURL = General.readCookie('APIURL');
    return apiURL;
  }
  static getSiteURL(){
    var apiURL = General.readCookie('SiteURL');
    return apiURL;
  }
  static ConfirmDialog(msg){
      return confirm(msg);
  }
  static Masquerade(otherUserName, clientID){

    var siteURL = PWH_UIService.getSiteURL();

    if (PWH_UIService.ConfirmDialog(`Login as user ${otherUserName} ?`)){

      var accessToken = General.readCookie('accessToken');
      var url = `${siteURL}/Masquerade?accessToken=${accessToken}&start&clientID=${clientID}`;

      General.setWindowURL(url);

      //todo need to update that auth code userID OR add masquearde check
      return true;
    }
    return false;
  }
  static EndMasquerade(){

    var siteURL = PWH_UIService.getSiteURL();

    var accessToken = General.readCookie('accessToken');
    var url = `${siteURL}/Masquerade?accessToken=${accessToken}&end`;
    General.setWindowURL(url);
  }

  /*
  http://localhost:8888/api/storagefacility/2/disassociate/facilityowners/user?userid=3
  */
  static Disassociate(elementID, apiURL, associativeCollectionName,
              foreignResourceName,
              foreignResourceIndexFieldname,
              primaryRecordID,
              foreignResourceID, prompt=true)
  {
    if (prompt && window.confirm('Remove this association?')){

    var url = `${apiURL}/${primaryRecordID}/disassociate/${associativeCollectionName}/${foreignResourceName}?${foreignResourceIndexFieldname}=${foreignResourceID}`;
    //// TODO: ADD CONFIRM DIALOGS FOR SENSITIVE TXNs (and a key for this txn)
    var data = {
      callbackDataItems: { }
      };
    CloudService.DELETE(`${elementID}LIST`, url, null, `CloudServiceResponseHandlers.disassociate('${elementID}', ${foreignResourceID})`);
    }
  }
  /*
    Authenticate and Login a user
  */
  static loginUser(apiURL, $callbackMethodName){

        var u = $("#username").val();
        var p = $("#password").val();
        //  CloudServiceResponseHandler will be called using data returned
        //     when called from the LOGIN wrapper :
        //       update the page URL on success or update textbox failure message
        CloudService.LOGIN('loginStatusMessage',
                                  `${apiURL}`,
                                  [u,p],
                                  `${$callbackMethodName}`,
          );
      }
      /*
      * TODO: validate form data
      */
      static validateFormData(){
        return true;
      }
  /*
    Call CloudService to enroll a user
  */
  static signupUser(){

    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    if (!PWH_UIService.validateFormData()){

    }
    else{
      var e     = $("#emailaddress").val();
      var u     = $("#username").val();
      var p     = $("#password").val();
      var fn    = $("#firstname").val();
      var ln    = $("#lastname").val();
      var city  = $("#city").val();
      var state = $("#signup-state :selected").val();
      var zip   = $("#zipcode").val();

      //map the requests into an array
      // TODO: THIS IS NOT WORKING, it is returning entire element
      var userTypeRequests = $('[name="user-type-request"]:checked').map(function (){
        return $(this).val();

        });

      var requestDataObject = {
          EmailAddress: e,
          Username: u,
          Password: p,
          Firstname: fn,
          Lastname: ln,
          City: city,
          State: state,
          Zipcode: zip,
          UserTypeRequests : {}
        };

      //CloudServiceResponseHandlers will:
      //       update the page URL on success or update textbox failure message
      CloudService.SIGNUP( 'loginStatusMessage',
                            apiURL + '/Signup',
                            requestDataObject,
                            'CloudServiceResponseHandlers.signup'
                          );
      }
    }
    /*
    Ensure passwords are same and any other validation
    */
    static validatePassword(){
      if ($("#password").val() != $("#vpassword").val()){

        $("#password").addClass("is-invalid");
        $("#vpassword").addClass("is-invalid");
        $("#submit-change-password").attr("disabled", true);
        return false;
      }
      else{
        $("#password").removeClass("is-invalid");
        $("#vpassword").removeClass("is-invalid");
        $("#password").addClass("is-valid");
        $("#vpassword").addClass("is-valid");
        $("#submit-change-password").attr("disabled", false);
        return true;
      }
    }
    /*
    Ensure emailaddress are same and any other validation
    */
    static validateEmailAddressesMatch(){

      var apiURL = PWH_UIService.getAPIURL();
      var siteURL = PWH_UIService.getSiteURL();

      if (  $("#emailaddress").val() != $("#emailaddressv").val() ||
            $("#emailaddress").val()==''
          ) {
            $("#emailaddress").addClass("is-invalid");
            $("#emailaddressv").addClass("is-invalid");
            $("#submit").attr("disabled", true);
        return false;
      }
      else{
        $("#emailaddress").removeClass("is-invalid");
        $("#emailaddressv").removeClass("is-invalid");
        $("#emailaddressv").addClass("is-valid");
        $("#emailaddressv").addClass("is-valid");
        $("#submit").attr("disabled", false);
        return true;
      }
    }


    /*
    Request a password reset link be sent to an emailaddress
    */
    static SendPasswordResetLink(authCode){

      var apiURL = PWH_UIService.getAPIURL();
      var siteURL = PWH_UIService.getSiteURL();
      var emailaddress = $("#emailaddress").val();
      var resetStep = $("#resetStep").val();

      CloudService.SEND_PASSWORD_RESET_LINK(  'passwordResetStatusMessage',
                                            apiURL + '/Reset',
                                            [emailaddress, authCode, resetStep],
                                            'CloudServiceResponseHandlers.SendPasswordResetLink'
                                          );

  }
  /*
    Send a request to the CloudService to change a user's password
  */
  static ChangePassword(accessToken){

    var userID          = $("#userID").val();
    var password_raw    = $("#password").val();
    var resetStep       = $("#resetStep").val();

    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    CloudService.RESET_PASSWORD(  'passwordResetStatusMessage',
                                  apiURL + '/Reset',
                                  [userID, password_raw, accessToken, resetStep],
                                  'CloudServiceResponseHandlers.ChangePassword'
                                );
    return false;

  }

  /*
  * Based on click of Ship Or Store, show the relative menu
  */
  static toggleShipStoreNav(action){


    switch(action){

      case 'ship':
      case 'store':
        $("#ship-store-detail").show();
        $("#button-store").hide();
        $("#button-ship").hide();
        $("#record-delete").hide();
        $("#record-add").hide();

        if (action=='store'){
          $("#recipientRow").hide();
          $("#ship-store-lot").attr('list',"");
        }
        else{
                    $("#ship-store-lot").attr('list', 'lotlist');
        }

        $("#button-do-ship-store").text(action);
        break;

      default:
        $("#ship-store-detail").hide();
        $("#button-store").show();
        $("#button-ship").show();
        $("#record-delete").show();
        $("#record-add").show();
        $("#recipientRow").hide();
      }


      return false;
  }
  static cancelShipStore(requestid, accessToken, approved=true){

    //Send to the Cloud Service
    var action = $(`#button-cancel-ship-store-${requestid}`).text();
    var currentstate = (approved) ? 'approval' : 'request';

    if(null===action){
      alert('error finding button cancel ship store in DOM');
    }
    if (action.includes('SHIP')){
      if (PWH_UIService.ConfirmDialog(`Cancel this Shipment ${currentstate} ?`)){
        CloudService.CANCEL_SHIP_REQUEST(requestid, accessToken);
      }
    }
    else if (action.includes('STORE')){
      if (PWH_UIService.ConfirmDialog(`Cancel this Storage ${currentstate} ?`)){
        CloudService.CANCEL_STORE_REQUEST(requestid, accessToken);
      }
    }
  }
  static doShipStore(itemid, accessToken){

// TODO: make this more modern, instead of waiting for form to be submitted,
//  populate lot drop down with qtys that work

    var errorMessage = "";
    var date_orig  = new Date($("#ship-store-date_needed").val());
    var day             = date_orig.getDate();
    var month           = date_orig.getMonth() + 1;
    var year            = date_orig.getFullYear();
    var lotnumber       = '';
    var current_qty     = 0;
    var lot  = $("#ship-store-lot").val();

    if (lot.length>0){
      var lotFieldValues = lot.split(',');
      var lotnumber       = lotFieldValues[0];
      var current_qty     = (lotnumber.length>1) ? parseInt(lotFieldValues[1]) : 0;
    }
    else {
      current_qty     = parseInt($("#confirmed_pulled_qty").val());
    }


    var name            = $("#ship-store-name").val();
    var qty_requested   = parseInt($("#ship-store-qty").val());
    var tag             = $("#ship-store-tag").val();
    var date_needed     = `${year}-${month}-${day} 12:00:00`;
    var notes           = $("#ship-store-notes").val();
    var userid_receiver = $("#userid_receiver :selected").val();
    var action = $("#button-do-ship-store").text();

    if (action==='ship' && (qty_requested > current_qty) ){
      if (lotnumber.length>0){
        errorMessage += `Selected lot [${lotnumber}] does not have ${qty_requested} items.`;
      }
      else{
        errorMessage += `There are not ${qty_requested} items to ship.`;
      }
    }

    if (errorMessage.length>1){
        $("#ship-store-ErrorMessage").html(errorMessage)
    }
    else{

      var postData = {
        itemid:       itemid,
        name:         $("#ship-store-name").val(),
        qty:          $("#ship-store-qty").val(),
        lotnumber:    (lotnumber != 'AnyLot') ? lotnumber : '',
        tag:          $("#ship-store-tag").val(),
        date_needed:  `${year}-${month}-${day} 12:00:00`,
        notes:        $("#ship-store-notes").val(),
        userid_receiver:     $("#userid_receiver :selected").val(),
        userid_approver: 0,
      };


      if (action==='ship'){
        postData.userid_puller = 0;
        postData.date_approved = '1970-01-01 00:00:00';
        postData.date_shipped = '1970-01-01 00:00:00';

        CloudService.SHIP_REQUEST(postData, accessToken);

      }
      else if (action==='store'){
        postData.userid_stocker  = 0;
        postData.date_approved  = '1970-01-01 00:00:00';
        postData.date_stored   = '1970-01-01 00:00:00';

        CloudService.STORE_REQUEST(postData, accessToken);
      }
    }
  }
  /*
  *  @param type = Shipping or Storage
  */
  static showHideApprove(requestID, type, show, divName="approve"){

    if (!show){
        $(`#approveButton${type}${requestID}`).prop('disabled', true);

      //this is a cancel, clear the items that were added.
      var targetPalletList = $(`#targetPalletID${type}${requestID}`);

      if (null!==targetPalletList){

        $(`#targetPalletID${type}${requestID}`).empty();

        //renenable the originals
        $(`#selectedPalletID${type}${requestID}`).removeAttr('disabled');
        if (type=='Storage'){
          var orig_qty = $(`#qty_orig${requestID}`).val();
          $(`#palletqty${requestID}`).val(orig_qty);
          $(`#qty_remaining${requestID}`).val(orig_qty);
        }

        $.each($(`#selectedPalletID${type}${requestID} > option`), function(){
              this.disabled = false;
              });
      }
    }
    if (show){
      $(`#div_${divName}_${type}${requestID}`).show();
    }
    else{
      $(`#div_${divName}_${type}${requestID}`).hide();
    }
  }
  /*
  * 1.) Create an association to palletInventory for this storage
  * 2.) Update the date_approved and userID on the storage to show it was approved
  */
  static approveStorageRequest(storageID, userID, lotnumber=null, tag=null, itemID=null){

    // For each pallet requested for storage, add an association with that qty

    var targetPallets = $(`#targetPalletIDStorage${storageID} > option`);


    if (targetPallets.length>0){
        $.each(targetPallets,
            function(){
                  var apiURL = PWH_UIService.getAPIURL();
                  var optionData  = this.value.split('$');
                  var palletID    = optionData[0];
                  if (palletID == -1){
                    //Just close the storage request.
                    CloudServiceResponseHandlers.approveStorageRequest(storageID, userID);
                    }
                  else{//Not a client driven palletID
                    var qtyStoring  = optionData[1];
                    var lotnumber   = $(`#store-rowData-lotnumber-${storageID}`).html();
                    var tag         = $(`#store-rowData-tag-${storageID}`).html();
                    var itemID      = $(`#store-rowData-itemid-${storageID}`).html();

                    // NOTE: THIS ORDER MUST MATCH THE ORDER in the Pallet Resource Additional params config
                    var postData    = { qty: `${qtyStoring}`, lotnumber: `${lotnumber}`, tag: `${tag}`, itemid:`${itemID}` };
                    var apiURL = apiURL + `/storagepallet/${palletID}/associate/palletinventory/storage?storageid=${storageID}`;

                    var callbackMethod = `CloudServiceResponseHandlers.approveStorageRequest(${storageID}, ${userID})`;
                    var callbackErrorMethod = `CloudServiceResponseHandlers.approveStorageRequestFailure(${storageID})`;
                    CloudService.Call("POST", null, apiURL, postData,callbackMethod,callbackErrorMethod);
                  }
                });
          }
          else{
            alert('no pallets');
          }

  }
  /*
  * Update the date_approved and userID on the storage to show it was approved
  */
  static approveShippingRequest(shipmentID, userID, lotnumber=null, tag=null){

    // Send the cloud request, (the callback will remove the li element)

    var lotnumber = $(`#ship-rowData-lotnumber-${shipmentID}`).html();
    var tag = $(`#ship-rowData-tag-${shipmentID}`).html();
    var postData = { lotnumber: `${lotnumber}`, tag: `${tag}` };
    var palletIDsCSV = "";
    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    //Grab all the selected palletIDs

    $.each($(`#targetPalletIDShipping${shipmentID} > option`), function(){
                  palletIDsCSV += `${this.value},`;
            });
    palletIDsCSV = palletIDsCSV.substring(0, palletIDsCSV.length-1);

    apiURL += `/shipment/${shipmentID}/approve/${userID}?palletIDs=${palletIDsCSV}`;

    CloudService.Call("PUT", null, apiURL,postData,null);

    $(`#requestShippingRowItem${shipmentID}`).hide();

  }
  static claimStorageRequest(storageID, userID){

    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    apiURL += `/storage/${storageID}/claim/${userID}`;
    CloudService.Call("PUT", null, apiURL,null,null);

    $(`#pendingStorageRowItem${storageID}`).hide();
  }
  static claimShipmentRequest(shipmentID, userID){
    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    apiURL += `/shipment/${shipmentID}/claim/${userID}`;
    CloudService.Call("PUT", null, apiURL,null,null);

    $(`#pendingShipmentRowItem${shipmentID}`).hide();
  }
  static unclaimStorageRequest(storageID, userID){

    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    apiURL += `/storage/${storageID}/unclaim/${userID}`;
    CloudService.Call("PUT", null, apiURL,null,null);

    $(`#pendingStorageRowItem${storageID}`).hide();
  }
  static storeStorageItem(storageID, userID){

    var qty_orig = $(`#in_process_StorageRowItem${storageID}qty_orig`).val();
    var qty = $(`#in_process_StorageRowItem${storageID}qty`).val();
    var palletID = $(`#in_process_StorageRowItem${storageID}palletID`).val();

    if (qty_orig<qty){
      alert(`You are adding more items than the original storage request  of ${qty_orig}`);
    }
    else if (qty_orig!=qty){
      alert(`Are you missing some items?`);
    }
    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    apiURL += `/storage/${storageID}/store/${userID}?qty=${qty}&palletID=${palletID}`;

    alert("Nothing actually stored yet.  You should be doing this on the handheld  at /inventory/");
    $(`#in_process_StorageRowItem${storageID}`).hide();

  }
  static shipStorageItem(shipmentID, userID){

    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();
    var qty_orig = $(`#in_process_ShipmentRowItem${shipmentID}qty_orig`).val();
    var qty = $(`#in_process_ShipmentRowItem${shipmentID}qty`).val();
    var palletID = $(`#in_process_ShipmentRowItem${shipmentID}palletID`).val();

    if (qty_orig<qty){
      alert(`You are removing more items than the original shipment request  of ${qty_orig}`);
    }
    else if (qty_orig!=qty){
      alert(`The shipment request is not accurate`);
    }

    apiURL += `/shipment/${shipmentID}/ship/${userID}?qty=${qty}&palletID=${palletID}`;

    alert("Nothing shipped yet.  You should be doing this on the handheld at /stocker/.");

    $(`#in_process_ShipmentRowItem${shipmentID}`).hide();

  }
  static selectPalletForShipORStorage(type, requestID, selectedPalletID=null){

    var elementObj = $(`#selectedPalletID${type}${requestID} :selected`);
    var palletText = elementObj.text().split(']')[0].substr(1);
    var selectedPalletIDToken = selectedPalletID;

    if (null==selectedPalletID){
      selectedPalletID = elementObj.val();
    }
    if (type=='Storage'){
      var qty = $(`#palletqty${requestID}`).val();
      var orig_qty = $(`#qty_remaining${requestID}`).val();
      if (selectedPalletID == -1){
        if (qty!=orig_qty){
          alert('Full value must be used for client assigned pallet ids');
          qty = orig_qty;
        }
      }
      palletText = `${palletText} -  ${qty}`;
      selectedPalletIDToken = `${selectedPalletID}$${qty}`

      var remainingQty = orig_qty - qty;
      $(`#palletqty${requestID}`).val(remainingQty);
      $(`#qty_remaining${requestID}`).val(remainingQty);

      if (remainingQty<1)
      {
        $(`#assignbutton${requestID}`).attr('disabled', 'disabled');
        $(`#selectedPalletID${type}${requestID}`).attr('disabled', 'disabled');
      }
    }

    $(`#approveButton${type}${requestID}`).prop('disabled', false);
    $(`#targetPalletID${type}${requestID}`).append(`<option value="${selectedPalletIDToken}">${palletText}</option>`);
    $(`#selectedPalletID${type}${requestID} option[value='${selectedPalletID}']`).prop('disabled', 'disabled');
  }
  /*
  Click handler for Result per page Select
  */
  static reportNavigationPerPageIntervalChange(previousRange, rangeValue){

    //Set the property on the form and submit it

    $('#resultsMaxPerPageInterval').val(rangeValue);
    $('#formRecordFilterSelect').submit();

    return true;
  }
  /*
  Click handler for Range / Page Select
  */
  static reportNavigationPageIndexStart(previousIndexStart, newIndexStart){

    //Set the property on the form and submit it
    $('#offsetValue').val(newIndexStart);
    $('#formRecordFilterSelect').submit();
    return true;
  }
  static ResetAndGoHome(){

    var siteURL = PWH_UIService.getSiteURL();
    var url = siteURL + '';

    General.deleteAllCookies();
    General.setWindowURL(url);
  }
}
