<?php

namespace Ability\Warehouse;

/*
* Build a top navigation menu-bar
 - Menu items change based on who is logged in

*/

//These are the default top menu items for any user


$topNavMenuItems        = array();
$staticMenuConfigItems  = ConfigurationManager::$NavTopStaticConfigItems;
$userIsAdmin            = false;

//If a user is logged in, add the resource specific accessible items
$userLoggedIn = SessionManager::IsActive();
if ($userLoggedIn){
    $accessTokenURL = "?accessToken=$accessToken";

    $currentuserID        = SessionManager::GetCurrentUserID();
    $currentUserName      = SessionManager::GetCurrentUsername();
    $currentEmailAddress  = SessionManager::GetCurrentEmailAddress();
    $userIsAdmin          = SessionManager::IsAdministrator();
    $accessToken          = SessionManager::GetAccessToken();

    $profileURL           = $userIsAdmin ? "/User" : "/User/$currentuserID";
    $profileURL           .= $accessTokenURL;

    // TODO: clean this up, should be in the configmanager
    //If user is logged in, replace menu item with logout and remove the Join link
    unset($staticMenuConfigItems["Signup"]);
    unset($staticMenuConfigItems["Login"]);


    $staticMenuConfigItems["Report"] = array(   "displayText"   => "Reports",
                                              "url"           => "/Report$accessTokenURL",
                                              "classes"       => "fa fa-file",
                                              "resourceName"  => "");

    $staticMenuConfigItems["User"] = array(   "displayText"   => "$currentUserName",
                                              "url"           => "$profileURL",
                                              "classes"       => "fa fa-user",
                                              "resourceName"  => "");

    $staticMenuConfigItems["Logout"] = array( "displayText"   => "Logout",
                                              "url"           => "/Logout",
                                              "classes"       => "fa fa-sign-out",
                                              "resourceName"  => "");

    //List of menu items that require a session.
    $topNavMenuItems = SessionManager::GetNavigationResources();

  }

?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <?php if ($userIsAdmin){ //SHOW ADMIN TAG
            echo "<span class='adminTag'>ADMIN</span>";
          }
        else if (SessionManager::IsMasquerading()){//SHOW MASK
            $unmaskButton = "<button class='fas fa-mask'";
            $unmaskButton .= " onclick=\"PWH_UIService.EndMasquerade()\"></button>";

            echo $unmaskButton;
        }
        $dashboardLinkText = ConfigurationManager::GetParameter("dashboardLinkText");
    ?>
  <a class="navbar-brand" href="/Dashboard<?php if(!empty($accessToken)){echo "?accessToken=$accessToken";}?>"><?php echo $dashboardLinkText; ?></a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <?php
        // Now iterate the top menu items
        foreach ($topNavMenuItems as $menuItemResource => $menuItemConfig) {

            $menuItemResourceName = $menuItemConfig["resourceName"];

            $menuItemURL = $menuItemConfig["url"];

            //skip the User Resource and add it manually by logout
            if ($menuItemResourceName!=='User'){

              // If it is not loging or logout, attach the accessToken
              if (  $menuItemResource!='Logout' &&
                    $menuItemResource!='Login' &&
                    !empty($accessToken)
                 ){
                $menuItemURL .= "?accessToken=$accessToken";
              }
              echo "<li class='nav-item'>";
              echo "<a class='nav-link' href='$menuItemURL'>" . $menuItemConfig['displayText'] . "</a>";
              echo "</li>";
            }
        }

    if ($userLoggedIn){
    ?>
    <form class="form-inline my-2 my-lg-0" method="post" action="/Search/?accessToken=<?php echo $accessToken; ?>">
      <input class="form-control mr-sm-2" id="searchString" name="searchString" type="search" placeholder="Search for items" aria-label="Search">
      <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
    </form>
    <?php
    }
        foreach ($staticMenuConfigItems as $menuItemResource => $menuItemConfig) {
            echo "<li class='nav-item'>";
            echo "<a class='nav-link " . $menuItemConfig['classes'] . "' href='" . $menuItemConfig['url']   . "'> " . $menuItemConfig['displayText'] . "</a>";
            echo "</li>";
          }
    ?>
    </ul>
  </div>
</nav>
