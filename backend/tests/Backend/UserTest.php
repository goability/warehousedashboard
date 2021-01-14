<?php
/*
  User Tests
*/
declare(strict_types=1);
namespace Ability\Warehouse;

use PHPUnit\Framework\TestCase;

require_once("baseIncludes.php");
require_once("User.php");

final class UserTest extends TestCase
{
  public function testCanCreate() : void
  {

    ConfigurationManager::LoadAllResourceConfigs();
    DataProvider::LoadPrepareStatements();

    $obj = new User(0);

    $this->assertInstanceOf(
      User::class, $obj
    );
  }
}
