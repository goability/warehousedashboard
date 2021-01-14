<?php
namespace Ability\Warehouse;
/*
PostgreSQL DataProvider
*/
class PostgreSQLDataProvider extends DataProvider {

  // Holds prepared statement objects
  public static $_preparedStatementObjects;
  public static $DBType = 'postgres';

  function __construct($configuration) {
    parent::__construct($configuration);
  }
  /*
    Prepare a single DB statement
  */
  protected function prepareSingleStatement($statementName, $statementString)
  {
    $success = true;
    try {
      if(pg_prepare($this->handler, $statementName,  $statementString))
      {
          Log::info("SUCCESSFUL PREPARE FOR $statementName - $statementString");
      }
      else
      {
        Log::error("Error preparing $statementName using  " . $statementString);
      }

    } catch (\Exception $e) {
        Log::error("Error preparing statement" . $e->getMessage());
        $success = false;
    }
    return $success;
  }

  /*
  insert a new record
  */
  public function insertrecord($resourceName, $fieldData){
    return $this->_sqlExecuteStatement(Constants\SqlPrepareTypes::SQL_INSERT . $resourceName, $fieldData );
  }
  public function updaterecord($resourceName, $fieldData){
    return $this->_sqlExecuteStatement(Constants\SqlPrepareTypes::SQL_UPDATE . $resourceName, $fieldData );
  }

  /*  _sqlExecuteStatement - Execute the $sql statement
  @param $prepareStatementName - name of the previously prepared statement
  @param $queryParameters - string array of parameters in order of indexes in prepared statement
  @returns records[]
  */
  protected function _sqlExecuteStatement($preparedStatementName, $queryParameters)
  {
      $rows = null;

      try {
        $result = pg_execute($this->handler, $preparedStatementName, $queryParameters);

        if (!$result)
        {
          Log::error("Error executing SQL statement $preparedStatementName");
        }
        else{
          $rows = pg_fetch_all($result, PGSQL_ASSOC);
        }

      } catch (\Exception $e) {
          Log::error($e->getMessage());
      }

      return $rows;
  }
}
