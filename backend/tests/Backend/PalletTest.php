<?php
/*
  Pallet Tests
*/
declare(strict_types=1);
namespace Ability\Warehouse;

use PHPUnit\Framework\TestCase;

require_once("baseIncludes.php");
require_once("Storagepallet.php");

final class PalletTest extends TestCase
{
  public function testCanCreate() : void
  {

    ConfigurationManager::LoadAllResourceConfigs();
    DataProvider::LoadPrepareStatements();

    $obj = new Storagepallet(0);

    $this->assertInstanceOf(
      Storagepallet::class, $obj
    );
  }
}
