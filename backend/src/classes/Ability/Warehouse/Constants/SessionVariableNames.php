<?php

namespace Ability\Warehouse\Constants;

  abstract class SessionVariableNames{

    public const EXPIRE_TIME            = "expires_unix_time";
    public const USER_ID                = "userID";
    public const ACCESS_TOKEN           = "AccessToken";
    public const CURRENT_USER           = "currentUsername";
    public const CURRENT_EMAIL          = "currentEmailAddress";
    public const CURRENT_USER_FULL_NAME = "currentUserDisplayName";
    public const CURRENT_USER_NICK_NAME = "currentUserNickName";
    public const ACCESSIBLE_RESOURCES   = "AccessibleResources";
    public const LOADED_RESOURCES       = "LoadedResources";
    public const IS_MASQUERADING        = "IsMasquerading";
    public const MASQUERADING_USER_ID   = "MasqueradingUserID";
    public const SINGLE_PROVIDER        = "SingleProvider";
    public const RECORD_OWNERSHIP_COUNT = "RecordOwnershipCount";
    public const IS_CLIENT              = "IsClient";
    public const IS_ADMIN               = "IsAdministrator";
    public const IS_PROVIDER            = "IsProvider";
    public const IS_EMPLOYEE            = "IsEmployee";
    public const IS_MANAGER             = "IsManager";
    public const STORAGE_ITEMS          = "StorageItems";
    public const OWNED_PALLETS          = "OwnedPallets";
    public const CONFIG_SITE            = "ConfigSite";
    public const CLIENT_PALLET_SCAN_MODE  = "ClientPalletScanMode";
    public const CURRENT_FACILITYNAME     = "CurrentFacilityName";
    public const CURRENT_FACILITYID       = "CurrentFacilityID";
    public const STOCKER_CLIENT_SCAN_MODE = "StockerScanMode";
    public const STOCKER_CLIENT_SCAN_STORAGE_REQUEST_ID = "StockerClientScanRequestID";
    public const STOCKER_CLIENT_SCAN_LAST_BIN           = "StockerClientScanLastBin";
    public const STOCKER_CLIENT_SCAN_LAST_QTY           = "StockerClientScanLastQty";
    public const STOCKER_CLIENT_SCAN_LAST_ITEM          = "StockerClientScanLastItem";
    public const CURRENT_PROVIDER_ID                    = "CurrentProviderID";    
    public const CURRENT_PROVIDER_NAME                  = "CurrentProviderName";
    public const CURRENT_PROVIDER_LOGO_PATH             = "CurrentProviderLogoPath";
  }
