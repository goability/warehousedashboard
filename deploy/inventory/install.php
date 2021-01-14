<?php
/*
*
*  Install Script:
*  Run this file in root folder, parent of DocumentRoot
*      "php install.php"
*
*
*/
    $currentDirectory = getcwd();

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

    ini_set('include_path', "$currentDirectory" . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "views;$currentDirectory" . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "classes;$currentDirectory" . DIRECTORY_SEPARATOR . "vendor");
} else {
    echo 'This is a server not using Windows!';
}
