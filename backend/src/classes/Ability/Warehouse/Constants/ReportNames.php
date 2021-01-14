<?php

namespace Ability\Warehouse\Constants;

  abstract class ReportNames {

    public const INVEN_PAL          = 1;
    public const INVEN_BIN          = 2;
    public const INVEN_PROD         = 3;
    public const INVEN_CLIENT       = 4;
    public const INVEN_LOT          = 5;
    public const INVEN_PAL_PENDING  = 7;

    public const TRANS_USER       = 21;
    public const TRANS_CLIENT     = 22;
    public const TRANS_RECEIVER   = 23;
    public const TRANS_PROVIDER   = 24;
    public const TRANS_EMPLOYEE   = 25;
    public const TRANS_PAL        = 26;
    public const TRANS_BIN        = 27;
    public const TRANS_ITEM       = 28;


    public const PEOPLE_CLIENT    = 51;
    public const PEOPLE_PROVIDER  = 52;
    public const PEOPLE_EMPLOYEE  = 53;
    public const PEOPLE_RECEIVER  = 54;

    public const REQUESTS_STORAGE = 61;
    public const REQUESTS_SHIPMENT = 62;

    public const TITLES = array(

      self::REQUESTS_STORAGE => "Storage Requests",
      self::REQUESTS_SHIPMENT => "Shipment Requests",
      self::INVEN_PAL        => "Pallet Inventory",
      self::INVEN_PAL_PENDING => "Pending Pallet Inventory",
      self::INVEN_BIN        => "Bin Inventory",
      self::INVEN_PROD       => "Product Inventory",
      self::INVEN_CLIENT     => "Client Inventory",
      self::INVEN_LOT        => "Lot Inventory",

      self::TRANS_USER       => "User Transactions",
      self::TRANS_CLIENT     => "Client Transactions",
      self::TRANS_PAL        => "Pallet Transactions",
      self::TRANS_RECEIVER   => "Receiver Transactions",
      self::TRANS_PROVIDER   => "Provider Transactions",
      self::TRANS_EMPLOYEE   => "Employee Transactions",
      self::TRANS_PAL        => "Pallet Transactions",
      self::TRANS_BIN        => "Bin Transactions",

      self::PEOPLE_CLIENT    => "Clients",
      self::PEOPLE_PROVIDER  => "Providers",
      self::PEOPLE_EMPLOYEE  => "Employees",
      self::PEOPLE_RECEIVER  => "Receivers"

    );

    public const MENU_NAMES = array(

      self::INVEN_PAL        => "Pallet",
      self::INVEN_PAL_PENDING => "Pallet Pending",
      self::INVEN_BIN        => "Bin",
      self::INVEN_PROD       => "Product",
      self::INVEN_CLIENT     => "Client",
      self::INVEN_LOT        => "Lot",

      self::TRANS_USER       => "User",
      self::TRANS_CLIENT     => "Client",
      self::TRANS_RECEIVER   => "Receiver",
      self::TRANS_PROVIDER   => "Provider",
      self::TRANS_EMPLOYEE   => "Employee",
      self::TRANS_PAL        => "Pallet",
      self::TRANS_BIN        => "Bin",
      self::TRANS_ITEM       => "Item",

      self::PEOPLE_CLIENT    => "Clients",
      self::PEOPLE_PROVIDER  => "Providers",
      self::PEOPLE_EMPLOYEE  => "Employees",
      self::PEOPLE_RECEIVER  => "Receivers",

      self::REQUESTS_STORAGE => "Storage",
      self::REQUESTS_SHIPMENT => "Shipment"

    );
  }
