<?php

namespace Ability\Warehouse\Constants;

  abstract class TransactionType{

    public const USER_LOGIN           = "USER_LOGIN";
    public const USER_LOGOUT          = "USER_LOGOUT";
    public const USER_SIGNUP          = "USER_SIGNUP";
    public const USER_ADD             = "USER_ADD";
    public const USER_UPDATE          = "USER_UPDATE";
    public const USER_CHANGE_PASSWORD = "USER_CHANGE_PASSWORD";
    public const USER_DELETE          = "USER_DELETE";

    public const ITEM_ADD        = "ITEM_ADD";
    public const ITEM_DELETE     = "ITEM_DELETE";
    public const ITEM_UPDATE     = "ITEM_UPDATE";


    public const STORE_REQUEST  = "STORE_REQUEST";
    public const STORE_APPROVE  = "STORE_APPROVE";
    public const STORE_REJECT   = "STORE_REJECT";
    public const STORE_FULFILL  = "STORE_FULFILL";
    public const STORE_CANCEL   = "STORE_CANCEL";

    public const SHIP_REQUEST  = "SHIP_REQUEST";
    public const SHIP_APPROVE  = "SHIP_APPROVE";
    public const SHIP_REJECT   = "SHIP_REJECT";
    public const SHIP_FULFILL  = "SHIP_FULFILL";
    public const SHIP_CANCEL   = "SHIP_CANCEL";

    public const CLIENT_ADD    = "CLIENT_ADD";
    public const CLIENT_REMOVE = "CLIENT_REMOVE";

    public const PALLET_ADD           = "PALLET_ADD";
    public const PALLET_ASSIGN_BIN    = "PALLET_ASSIGN_BIN";
    public const PALLET_CHANGE_BIN    = "PALLET_CHANGE_BIN";
    public const PALLET_DELETE        = "PALLET_REMOVE";

    public const BIN_ADD              = "BIN_ADD";
    public const BIN_DELETE           = "BIN_REMOVE";

    public const MASQUERADE_START     = "MASQUERADE_START";
    public const MASQUERADE_END       = "MASQUERADE_END";

  }
