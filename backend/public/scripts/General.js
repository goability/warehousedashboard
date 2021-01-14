class General {

  static createCookie(name,value,days) {
        if (value=='null'){
          return;
        }
        if (days) {
            var date = new Date();
            date.setTime(date.getTime()+(days*24*60*60*1000));
            var expires = "; expires="+date.toGMTString();
        }
        else var expires = "";
        document.cookie = name+"="+value+expires+"; path=/";
  }
  static readCookie(name) {
      var nameEQ = name + "=";
      var ca = document.cookie.split(';');
      for(var i=0;i < ca.length;i++) {
          var c = ca[i];
          while (c.charAt(0)==' ') c = c.substring(1,c.length);
          if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
      }

      return null;
  }
  static deleteCookie(name) {
    document.cookie = name+"=;expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

  }
  static deleteAllCookies() {
    //https://stackoverflow.com/questions/179355/clearing-all-cookies-with-javascript
    var cookies = document.cookie.split(";");

    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i];
        var eqPos = cookie.indexOf("=");
        var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
        document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
    }
  }
  static setWindowURL(url){
    window.location.assign(url);
  }
  static reload(){
    window.location.reload();
  }
  static getUserID(){
    return General.readCookie('userID');
  }
  static getAccessToken(){
    return General.readCookie('accessToken');
  }
}
