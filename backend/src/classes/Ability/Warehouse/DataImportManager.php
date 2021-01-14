<?php
/**
 * Utility class for data importing
 */

namespace Ability\Warehouse;

class DataImportManager
{

  static function ImportUsers($sourceTableName){

    //archive current users to - backup-table-users


    if (!DataProvider::ExecuteSQL("SHOW TABLES LIKE $sourceTableName")){
      return "Source table $sourceTableName Does not exist";
    }
    $sql = "CREATE TABLE `backup-users` LIKE `user`";
    if (!DataProvider::ExecuteSQL($sql)){
      $statusMessage = "ERROR Backing up table.  Make sure 'backup-users' does not already exist!";
    }
    else{
      $sql = "INSERT INTO `backup-users` SELECT * FROM `user`";
      $numBackedupRows = DataProvider::ExecuteSQL($sql);
      if (!$numBackedupRows){
        $statusMessage = "ERROR while backing up records";
      }
      else{
          $defaultPassword = ConfigurationManager::GetParameter("DefaultPassword");
          $userDefaultPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
          $sql = "INSERT INTO `user` (`firstname`, `lastname`, `profilename`, `emailaddress`, `upasswd`, `verified`) VALUES (SELECT `firstname`, `lastname`, `profilename`, `emailaddress`, `upasswd`, `verified` FROM `$sourceTableName`)";

          $numImportedRows = DataProvider::ExecuteSQL($sql);
          if (!$numImportedRows){
            $statusMessage = "Error while inserting new records.  Table is currently empty but it is backedup.";
          }
          else{
            $statusMessage = "Success<br>$numImportedRows rows backed up<br>$numImportedRows rows imported";
          }
      }
    }
    return $statusMessage;
  }
}
