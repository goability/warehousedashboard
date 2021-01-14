<?php
namespace Ability\Warehouse;

try
{
  set_include_path(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."includes".DIRECTORY_SEPARATOR);
  require_once("autoload-site.php");
}
catch (Exception $e){
  error_log("Error occured during class autoloading for SITE: " );
  echo "Error with logger setup";
  die();
}

try {
    Log::init('Site');
} catch (\Exception $e) {
  $msg = "Error with logger setup for Site";
  error_log($msg . $e->getMessage());
  echo $msg;
  die();
}


require("views/HeaderView.php");

try {

  $router = new \Ability\Warehouse\Router();
  $router->handle_route();

} catch (\Exception $e) {
  error_log("Error during routing ");
  error_log($e->getMessage());
  echo "Site Error " . $e->getMessage();
  exit;
}
require("./views/FooterView.php");
