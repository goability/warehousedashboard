/*
Call backend services
*/
class CloudService {

  static GET(elementID, callEndpoint, parameterData){

    CloudService.Call("get",elementID, callEndpoint, parameterData);
  }
  static POST(elementID, callEndpoint, parameterData, callbackMethodName=null, callbackOrderedData=null){

    var callbackMethod = CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);

    CloudService.Call("post",elementID, callEndpoint, parameterData, callbackMethod);
  }
  static PUT(elementID, callEndpoint, callbackMethodName, callbackOrderedData){

    var callbackMethod = CloudService.constructCallbackURL(callbackMethodName, callbackOrderedData);

    CloudService.Call("put",elementID, callEndpoint, null, callbackMethod);
  }
  static constructCallbackURL(callbackMethodName, callbackOrderedData, resultDataText=null){

    var callbackMethod = `${callbackMethodName}(`;

    callbackOrderedData.forEach( (parameterValue, i) => {
      var adjustedValue = parameterValue;
      if (typeof(parameterValue) === "string"){
        adjustedValue = `'${parameterValue}'`;
      }
      if (i>0){
        callbackMethod += ', ';
      }
      callbackMethod += adjustedValue;

    });
    if (null!=resultDataText){
      callbackMethod += `, ${resultData}`;
    }
    callbackMethod += ')';

    return callbackMethod;
  }
  static DELETE(elementID, callEndpoint, parameterData, callbackMethod){
    CloudService.Call("delete",elementID, callEndpoint, parameterData, callbackMethod);
  }
  /*
  Send a signup request POST
  @param: data - SignupData named array: data.Firstname, data.LastName, ...
  */
  static SIGNUP(elementID, callEndpoint, signupRequestObject, callbackMethodName){

    $.post(`${callEndpoint}`, signupRequestObject,
      function (result){

        var userID = isNaN(result.trim()) ? parseInt(result.trim()) : result;
        var callbackOrderedData = [elementID, userID];
        var callbackMethod = `${callbackMethodName}('${elementID}', ${userID})`;

        eval(callbackMethod);
      }
    );
  }
  /*
    Authenticate a user
    @param: data - ORDERED array username, password
  */
  static AUTHENTICATE(elementID, callEndpoint, data, callbackMethodName){

    $.post(`${callEndpoint}`, { username: data[0], password: data[1]},
      function (result){

        var userID = isNaN(result.trim()) ? parseInt(result.trim()) : result;
        var callbackOrderedData = [elementID, userID];
        var callbackMethod = `${callbackMethodName}('${elementID}', ${userID})`;

        eval(callbackMethod);
      }
    );
  }

  static LOGIN(elementID, callEndpoint, data, callbackMethodName){

    $.post(`${callEndpoint}`, { username: data[0], password: data[1]},
      function (result){

        var replyData = result.split(',');
        var userID = (isNaN(replyData[0].trim()) || replyData[0].trim()==='') ?
                          0 : replyData[0];
        var authCode = userID>0 ? replyData[1].trim() : '0';
        var callbackOrderedData = [elementID, userID, authCode];
        var callbackMethod = `${callbackMethodName}('${elementID}', ${userID}, '${authCode}')`;

        eval(callbackMethod);
      }
    ).fail(
      function(result){

        // TODO: message from API is not passed through yet, message on form
        //   is set from callbackhandler
        if (null!==result && null!==result.responseText){
          var replyArray = result.responseText.split(',');
          if (replyArray.length>1){
            console.log(replyArray[1]);
          }
        }

        var callbackMethod = `${callbackMethodName}('${elementID}', 0, '')`;

        eval(callbackMethod);
      }
    );
  }

  static SEND_PASSWORD_RESET_LINK(elementID, callEndpoint, data, callbackMethodName ){

    var emailaddress = data[0];
    $.post(`${callEndpoint}`, { emailaddress: data[0], authCode: data[1], resetStep: data[2]},
      function (result){
        //Expected reply is csv string: "userID, success"
        // note that the email is sent out from the API Server ..
        var replyData = result.split(',');
        var userID = (isNaN(replyData[0].trim()) || replyData[0].trim()==='') ?
                          0 : replyData[0];
        var success = replyData[1];
        var emailaddress = data[0];
        var callbackMethod = `${callbackMethodName}('${elementID}', '${emailaddress}', '${success}')`;

        eval(callbackMethod);
      }
    );
  }
  /*
    Call API to change password
    @param: data array[userid;password_raw,accesstoken]
  */
  static RESET_PASSWORD(elementID, callEndpoint, data, callbackMethodName)
  {

    $.post(`${callEndpoint}`, { userid: data[0], password_raw: data[1], accessToken: data[2], resetStep: data[3]},
      function (result){

        result =  (undefined===result) ? false : result;
        var callbackMethod = `${callbackMethodName}('${elementID}', ${result})`;

      }
    );
  }
  static CANCEL_SHIP_REQUEST(shipmentRequestID, accessToken){

    var apiURL = PWH_UIService.getAPIURL();

    var callEndpoint = apiURL += `/shipment/${shipmentRequestID}/cancelapproval`;
    $.post(`${callEndpoint}`, { requestid: shipmentRequestID, accessToken: accessToken},
      function (result){
          CloudServiceResponseHandlers.cancelapproval('SHIP', shipmentRequestID);
      }
    );
  }
  static CANCEL_STORE_REQUEST(storageRequestID, accessToken){

    var apiURL = PWH_UIService.getAPIURL();

    var callEndpoint = apiURL += `/storage/${storageRequestID}/cancelapproval`;
    $.post(`${callEndpoint}`, { requestid: storageRequestID, accessToken: accessToken},
      function (result){
        CloudServiceResponseHandlers.cancelapproval('STORE', storageRequestID);
      }
    );
  }
  static SHIP_REQUEST(postData, accessToken){

    var itemid = postData.itemid;
    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    postData.userid_requestor = General.getUserID();

    //shipment is a first class resource, so this call implies an INSERT
    $.post(`${apiURL}/shipment`, postData,
      function (result){
        window.location = `${siteURL}/Storageitem/${itemid}?accessToken=${accessToken}`;
      });

  }
  static STORE_REQUEST(postData, accessToken){
    var itemid = postData.itemid;



    var apiURL = PWH_UIService.getAPIURL();
    var siteURL = PWH_UIService.getSiteURL();

    var userid = postData.userid_requestor = General.getUserID();

    //storage is a first class resource, so this call implies an INSERT
    $.post(`${apiURL}/storage`, postData,
      function (result){
        window.location = `${siteURL}/Storageitem/${itemid}?accessToken=${accessToken}`;
      });
  }
  /*
    Call requested endpoint and pass results to callbackMethod
  */
  static Call(method, elementID, callEndpoint, postData=null, callbackMethod=null, callbackErrorMethod=null){

    $.ajax({
      method: `${method}`,
      url: `${callEndpoint}`,
      data: postData,
      fail: CloudService.HandleErrorReply,
      success: (result) => {
                        if (null!==callbackMethod){
                          eval(callbackMethod);
                        }
                      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        alert("Error: " + errorThrown);
        if (null!=callbackErrorMethod){
          eval(callbackErrorMethod);
        }
      },
      statusCode: {
        401: function(){
          alert("Auth failure");
        }
      }
    });
  }

  /*
  Generic handler for error replies
  */
  static HandleErrorReply(error){
      var msg = "An error occured with this call: " + error.status + " - " + error.statusText;
      console.error(msg);
  }
}
