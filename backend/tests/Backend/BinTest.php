<?php
/*
  User Tests
*/
declare(strict_types=1);
namespace Ability\Warehouse;

use PHPUnit\Framework\TestCase;

require_once("baseIncludes.php");
require_once("Storagebin.php");

final class BinTest extends TestCase
{
  public function testCanCreate() : void
  {

    ConfigurationManager::LoadAllResourceConfigs();
    DataProvider::LoadPrepareStatements();

    $obj = new Storagebin(0);

    $this->assertInstanceOf(
      Storagebin::class, $obj
    );
  }
}
