<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class ActionFactory {

    private static $actionCache = array();

    public static function factory($requestMethod) {
        $requestMethod = strtolower($requestMethod);
        if (empty($requestMethod)) {
            throw new EmptyMethodException('Missing method parameter');
        }
        if (isset(self::$actionCache[$requestMethod])) {
            return self::$actionCache;
        }
        if (!self::actionExists($requestMethod)) {
            throw new InvalidRequestException('Invalid request method .(' . $requestMethod . ')');
        }
        kc_include_once(self::getActionPath($requestMethod));
        $actionClassName = self::getActionClassName($requestMethod);
        $instance = new $actionClassName;
        self::$actionCache[$requestMethod] = $instance;
        return $instance;
    }

    public static function getActionClassName($requestMethod) {
        $arr = explode('.', $requestMethod);
        $className = join('_', $arr);
        return $className . '_action';
    }

    public static function getActionPath($requestMethod) {
        $actionPath = '';
        foreach (explode('.', $requestMethod) as $pathPart) {
            $actionPath = $actionPath . '/' . $pathPart;
        }
        return KANCART_ROOT . '/actions' . $actionPath . '_action.php';
    }

    public static function actionExists($requestMethod) {
        return kc_file_exists(self::getActionPath($requestMethod));
    }

}

?>
