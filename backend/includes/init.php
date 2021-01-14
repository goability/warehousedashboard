<?php
/*
Set constants
Set include path
base requires for log and config

*/
define('VENDOR_NAME', "Ability");
define('PRODUCT_NAME', "Warehouse");
define('NAME_SPACE', VENDOR_NAME."\\".PRODUCT_NAME);

define('SITE_CLASS_DIR', strtolower(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR."classes")));
define('API_CLASS_DIR', strtolower(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR."classes")));
define('HANDHELD_CLASS_DIR', strtolower(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR."classes")));
define('VENDOR_DIR', strtolower(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."vendor")));
define('PUBLIC_DIR', strtolower(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."public")));
define('VIEW_DIR', strtolower(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR."views")));
define('RESOURCES_IMAGE_BASE_PATH', strtolower(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR."resources")));
define('CONFIG_DIR', PUBLIC_DIR.DIRECTORY_SEPARATOR."config");

set_include_path(get_include_path().PATH_SEPARATOR.VENDOR_DIR.PATH_SEPARATOR.SITE_CLASS_DIR.PATH_SEPARATOR.API_CLASS_DIR.PATH_SEPARATOR.HANDHELD_CLASS_DIR);
set_include_path(get_include_path().PATH_SEPARATOR.PUBLIC_DIR);
set_include_path(get_include_path().PATH_SEPARATOR.VIEW_DIR);
set_include_path(get_include_path().PATH_SEPARATOR.SITE_CLASS_DIR.DIRECTORY_SEPARATOR.VENDOR_NAME.DIRECTORY_SEPARATOR.PRODUCT_NAME);


require_once(VENDOR_DIR.DIRECTORY_SEPARATOR."autoload.php");
require_once("Autoloader.php");
require_once("Log.php");
require_once("ConfigurationManager.php");
