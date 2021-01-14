<?php
class Autoloader
{

    static public function _loader($className, $basePath){

    if ($className===null || strlen($className)<1 ){
     return;
    }
    $fqClassName = $className;
    if(strpos($className,NAME_SPACE)===false){
      $fqClassName = NAME_SPACE . "\\" . $className;
    }
    if (!class_exists($fqClassName, false)){
      $fqUpper = NAME_SPACE . "\\" . ucfirst($className);

      if (class_exists($fqUpper,false)){
        return;
      }
    }

    $pathToFile = $basePath.DIRECTORY_SEPARATOR.$fqClassName.".php";

    try {
      // To fix namespaces on Unix, must replace \ with /

      if (DIRECTORY_SEPARATOR=='/'){
          $pathToFile = str_replace("\\", DIRECTORY_SEPARATOR, $pathToFile);
      }

      if (file_exists($pathToFile)){
        try {
          require_once($pathToFile);
        } catch (\Exception $e) {
          error_log("Error with loading file again");
        }
      }
      else{

        // TODO: fix this at a higher level
        //  Fix any OS specific path issues built into namespace
        $ns = str_replace("\\", DIRECTORY_SEPARATOR,NAME_SPACE);
        $pathToFile = $basePath.DIRECTORY_SEPARATOR.$ns.DIRECTORY_SEPARATOR.$className.".php";

        if (file_exists($pathToFile)){
          try {
            require_once($pathToFile);
          } catch (\Exception $e) {
            error_log("Error with auto-loader : " . $e->getTraceAsString());
          }
        }
      }
    } catch (\Exception $e) {
      error_log("File load exception for class [$className] from path: ".$pathToFile);
      error_log($e->getMessage());
    }
  }
  static public function SiteLoader($className){
    self::_loader($className, SITE_CLASS_DIR);
  }
  static public function APILoader($className){
    self::_loader($className, API_CLASS_DIR);
  }
  static public function HandheldLoader($className){
    self::_loader($className, HANDHELD_CLASS_DIR);
  }
}
