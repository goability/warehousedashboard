<?php
namespace Ability\Warehouse;

/**
 *
 * Represents how a User resource owns this object
 * This detail is used when building things like drop-downs for resources
 *  that this user owns or co-owns
 * i.e.  The examples below show the three cases.
 *     (1) Direct ownership - Storage item
 *     (2) InDirect ownership - StoragePallet (user owns Provider)
 *          note that this one will need to also traverse #3
 *     (3) Multi-ownership - StorageProvider (owned by multiple users)
 */
class ResourceOwnershipInfo
{
  public $OwnedByFieldName;                 //ownerid
  public $OwnerResourceTableName;           //(1)provider OR (2)providerowners
  public $OwnerResourceOwnedByFieldName;    //(1)ownerid OR (2)userid
  public $OwnerResourceIndexFieldName;      //usually id
  public $CoOwnerResourceTableName;         //providerowners
  public $CoOwnerResourceOwnedByFieldName; //userid
  public $CoOwnerResourceFieldName;         //facilityid
  function __construct( $resourceOwnedByFieldName,
                        $ownerResourceTableName,
                        $ownerResourceOwnedByFieldName,
                        $ownerResourceIndexFieldName,
                        $coOwnerResourceTableName,
                        $coOwnerOwnedByFieldName,
                        $coOwnerResourceFieldName)
  {
/*
    Log::debug("ResourceOwnership Created ONCE for [$OwnerResourceTableName]-- Input Params were $resourceOwnedByFieldName,
                      $ownerResourceTableName,
                      $ownerResourceOwnedByFieldName,
                      $ownerResourceIndexFieldName,
                      $coOwnerResourceTableName,
                      $coOwnerOwnedByFieldName,
                      $coOwnerResourceFieldName");
*/
    $this->OwnedByFieldName                = $resourceOwnedByFieldName;
    $this->OwnerResourceTableName          = $ownerResourceTableName;
    $this->OwnerResourceOwnedByFieldName   = $ownerResourceOwnedByFieldName;
    $this->OwnerResourceIndexFieldName     = $ownerResourceIndexFieldName;
    $this->CoOwnerResourceTableName        = $coOwnerResourceTableName;
    $this->CoOwnerResourceOwnedByFieldName = $coOwnerOwnedByFieldName;
    $this->CoOwnerResourceFieldName        = $coOwnerResourceFieldName;

  }
}
