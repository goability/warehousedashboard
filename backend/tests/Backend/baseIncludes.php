<?php

try
{
  $baseDirectory = dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..";
  $includeDirectory = $baseDirectory.DIRECTORY_SEPARATOR."includes";
  set_include_path($includeDirectory);
  require_once("autoload-site.php");
}
catch (Exception $e){
  error_log("Error occured during class autoloading for SITE: " );
  echo "Error with logger setup";
  die();
}
