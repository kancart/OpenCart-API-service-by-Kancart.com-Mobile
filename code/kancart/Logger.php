<?php

if (!defined('KANCART_ROOT')) {
    define('KANCART_ROOT', str_replace('\\', '/', dirname(__FILE__)));
}
define('SYTEM_LOG_PATH', KANCART_ROOT . '/log.html');
define('IS_LOG_ENABLED', true);
define('DIE_ON_OPEN_LOG_FAILED', false);
define('IS_APPEND_DATE', true);

class Logger {

    private static $filePath = SYTEM_LOG_PATH;
    private static $dieOnOpenLogFailed = DIE_ON_OPEN_LOG_FAILED;
    private static $islogEnabled = IS_LOG_ENABLED;
    private static $isAppendDate = IS_APPEND_DATE;

    public static function log($data) {
        if (self::$islogEnabled) {
            $myFile = Logger::$filePath;
            $fh = fopen($myFile, 'a+');
            if (!$fh) {
                if (self::$dieOnOpenLogFailed) {
                    die('Can not open log file: ' . self::$filePath);
                }
                return;
            }
            flock($fh, LOCK_EX);
            if (self::$isAppendDate) {
                fwrite($fh, date('Y-m-d H:i:s') . ' - ' . htmlspecialchars($data) . '<br />');
            } else {
                fwrite($fh, htmlspecialchars($data) . '<br />');
            }

            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    public static function clean() {
        $fp = fopen(self::$filePath, 'w');
        if ($fp) {
            fwrite($fp, '');
            fclose($fp);
        }
    }

    public static function status() {
        return array(
            'logPath' => SYTEM_LOG_PATH,
            'isLogEnabled' => IS_LOG_ENABLED,
            'dieOnOpenLogFailed' => DIE_ON_OPEN_LOG_FAILED,
            'isAppendDate' => IS_APPEND_DATE
        );
    }

}
