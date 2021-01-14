<?php
namespace Ability\Warehouse;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class Log {

  private static  $logger;
  public static   $Name="Site";
  public static   $DebugLevelEnabled;
  public static function GetInstance($logTypePrefix='Site'){
    if (empty(self::$logger)){
      self::init($logTypePrefix);
    }
    return self::$logger;
  }
  public static function init($logTypePrefix){

    $logConfig = ConfigurationManager::GetParameter("Logging");
    self::$Name                   = $logConfig->LogFilePrefix . $logTypePrefix;
    self::$DebugLevelEnabled      = $logConfig->LogLevel==='DEBUG' ? true:false;
    $logBasePath                  = $logConfig->LogBasePath;
    $logFileExtension             = $logConfig->LogFileExtension;
    $maxLogsInfo                  = $logConfig->LogFilesToRetainInfo;
    $maxLogsWarn                  = $logConfig->LogFilesToRetainWarn;
    $maxLogsError                 = $logConfig->LogFilesToRetainError;
    $maxLogsDebug                 = $logConfig->LogFilesToRetainDebug;

    $PATH_LOG_INFO = join(DIRECTORY_SEPARATOR, array($logBasePath, self::$Name . "_INFO" . $logFileExtension));
    $PATH_LOG_WARN = join(DIRECTORY_SEPARATOR, array($logBasePath, self::$Name . "_WARN" . $logFileExtension));
    $PATH_LOG_ERROR = join(DIRECTORY_SEPARATOR, array($logBasePath, self::$Name . "_ERROR" . $logFileExtension));
    $PATH_LOG_DEBUG = join(DIRECTORY_SEPARATOR, array($logBasePath, self::$Name . "_DEBUG" . $logFileExtension));

    self::$logger = new Logger(self::$Name);

    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_INFO, $maxLogsInfo,  Logger::INFO));
    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_WARN, $maxLogsWarn, Logger::WARNING));
    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_ERROR,  $maxLogsError, Logger::ERROR));
    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_DEBUG,  $maxLogsDebug, Logger::DEBUG));

  }
  public static function info($msg){
    self::GetInstance(self::$Name)->info($msg);
  }
  public static function error($msg){
    self::GetInstance(self::$Name)->err($msg);
  }
  public static function warning($msg){
    self::GetInstance(self::$Name)->warn($msg);
  }
  public static function debug($msg){
    if (self::$DebugLevelEnabled){
      self::GetInstance(self::$Name)->debug($msg);
    }
  }
}
