<?php
  require_once('init.php');
  require_once("Autoloader.php");
  $autoloader_function = function($classname){
    Autoloader::HandheldLoader($classname);
  };

  spl_autoload_register($autoloader_function);
