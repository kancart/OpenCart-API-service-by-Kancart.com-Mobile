<?php

class ServiceFactory {

    private static $serviceCache = array();

    public static function factory($serviceName, $singleton = true) {
        if (empty($serviceName)) {
            throw new Exception('Service name is required .');
        }
        $serviceClassName = $serviceName . 'Service';
        if (isset(self::$serviceCache[$serviceClassName])) {
            if ($singleton) {
                return self::$serviceCache[$serviceClassName];
            } else {
                return new $serviceClassName;
            }
        }
        $classFilePath = '';
        if (!self::serviceExists($serviceClassName, $classFilePath)) {
            throw new Exception($serviceClassName . ' not exists');
        }
        kc_include_once($classFilePath);
        $instance = new $serviceClassName;
        self::$serviceCache[$serviceClassName] = $instance;
        return $instance;
    }

    public static function serviceExists($serviceClassName, &$classFilePath) {
        $serviceFile = $serviceClassName . '.php';
        if (($classFilePath = self::getServicePath($serviceFile))) { //apply for different version)        
            return true;
        }
        $classFilePath = KANCART_ROOT . '/services/' . $serviceFile;
        return kc_file_exists($classFilePath);
    }

    public static function getServicePath($serviceFile, $currentVersion = KC_CART_VERSION) { //v3.X  v3.1.X  v.3.1.3
        if ($currentVersion < '1.5') {
            $version = '1.5.0-';
        } else if ($currentVersion >= '1.5.3') {
            $version = '1.5.3+';
        } else {
            $version = '1.5+';
        }
        $filePath = KANCART_ROOT . '/services/v' . $version . '/' . $serviceFile;
        if (kc_file_exists($filePath)) {
            return $filePath;
        }

        return false;
    }

}

?>
