<?php

namespace Ability\Warehouse\Constants;

abstract class ConfigurationParameterNames{

    public const PRODUCT_NAME   = "ProductName";
    public const DEBUG_MODE     = "DebugMode";
    public const RESOURCE_DIR   = "ResourceDir";
    public const SESSIONING     = "Sessioning";
    public const TOKEN_TTL_PASSWORD_RESET      = "TimeoutPasswordReset";
    public const TOKEN_TTL_SESSION_AUTOLOGOUT  = "TimeoutAutoLogout";
    public const Logging         = "Logging";
    public const LOG_BASEPATH    = "LogBasePath";
    public const LOG_FILE_PREFIX = "LogFilePrefix";
    public const LOG_FILE_EXT    = "LogFileExtension";//file extension
    public const MAX_LOGS_INFO   = "LogFilesToRetainInfo";
    public const MAX_LOGS_WARN   = "LogFilesToRetainWarn";
    public const MAX_LOGS_ERROR  = "LogFilesToRetainError";
    public const MAX_LOGS_DEBUG  = "LogFilesToRetainDebug";
    public const DATABASE                 = "Database";
    public const DATABASE_TYPE            = "type";
    public const DATABASE_TYPE_MYSQL      = "mysql";
    public const DATABASE_TYPE_POSTGRES   = "postgres";
    public const MAX_RECORD_LIMIT         = "MaxRecordLimit";
    public const SESSION_NAME             = "SessionName";
    public const SESSION_NAME_HANDHELD    = self::PRODUCT_NAME . "handheld";
  }
