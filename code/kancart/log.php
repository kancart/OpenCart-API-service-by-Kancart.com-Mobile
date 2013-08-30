<?php

error_reporting(0);
ini_set('display_errors', true);
require_once 'Logger.php';
$logConfiguration = array(
    'logPath' => 'log.html',
    'isLogEnabled' => 'false',
    'dieOnOpenLogFailed' => 'false',
    'isAppendDate' => 'true'
);
if (isset($_REQUEST['clear'])) {
    Logger::clean();
    die('Log has been cleared .');
}

if (isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
    if ($action == 'config') {
        $logSample = file_get_contents('Logger.sample');
        foreach ($logConfiguration as $key => $val) {
            if (isset($_REQUEST[$key]) && !empty($_REQUEST[$key])) {
                $val = $_REQUEST[$key];
            }
            $logSample = str_replace("%$key%", $val, $logSample);
        }
        file_put_contents('Logger.php', $logSample);
    } else if ($action == 'status') {
        if ($_REQUEST['action'] == 'status') {
            die(json_encode(Logger::status()));
        }
    }
} else {
    $logContent = file_get_contents('log.html');
    if ($logContent == '') {
        die('Log is empty.');
    }
    die($logContent);
}
?>
