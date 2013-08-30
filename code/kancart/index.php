<?php

ini_set('display_errors', true);
define('IN_KANCART', true);
define('API_VERSION', '1.1');
define('KANCART_ROOT', str_replace('\\', '/', dirname(__FILE__)));
define('DIR_ROOT', dirname(KANCART_ROOT));

require_once KANCART_ROOT . '/KancartHelper.php';
require_once DIR_ROOT . '/config.php';

// VirtualQMOD
if (file_exists(DIR_ROOT . '/vqmod/vqmod.php')) {
    require_once(DIR_ROOT . '/vqmod/vqmod.php');
    $vqmod = new VQMod();

    require_once($vqmod->modCheck(DIR_SYSTEM . 'startup.php'));
    require_once($vqmod->modCheck(DIR_SYSTEM . 'library/customer.php'));
    require_once($vqmod->modCheck(DIR_SYSTEM . 'library/affiliate.php'));
    require_once($vqmod->modCheck(DIR_SYSTEM . 'library/currency.php'));
    require_once($vqmod->modCheck(DIR_SYSTEM . 'library/tax.php'));
    require_once($vqmod->modCheck(DIR_SYSTEM . 'library/weight.php'));
    require_once($vqmod->modCheck(DIR_SYSTEM . 'library/length.php'));
    require_once($vqmod->modCheck(DIR_SYSTEM . 'library/cart.php'));
} else {
    require_once DIR_SYSTEM . 'startup.php';
    require_once DIR_SYSTEM . 'library/customer.php';
    require_once DIR_SYSTEM . 'library/currency.php';
    require_once DIR_SYSTEM . 'library/tax.php';
    require_once DIR_SYSTEM . 'library/weight.php';
    require_once DIR_SYSTEM . 'library/cart.php';
}

kc_include_once(KANCART_ROOT . '/ErrorHandler.php');
kc_include_once(KANCART_ROOT . '/configure.php');
kc_include_once(KANCART_ROOT . '/util/CryptoUtil.php');
kc_include_once(KANCART_ROOT . '/ActionFactory.php');
kc_include_once(KANCART_ROOT . '/ServiceFactory.php');
kc_include_once(KANCART_ROOT . '/KancartResult.php');
kc_include_once(KANCART_ROOT . '/Exceptions.php');
kc_include_once(KANCART_ROOT . '/common-functions.php');
kc_include_once(KANCART_ROOT . '/actions/BaseAction.php');
kc_include_once(KANCART_ROOT . '/actions/UserAuthorizedAction.php');
kc_include_once(KANCART_ROOT . '/services/BaseService.php');
kc_include_once(KANCART_ROOT . '/Logger.php');

try {
    $actionInstance = ActionFactory::factory(isset($_REQUEST['method']) ? $_REQUEST['method'] : '');
    $actionInstance->init();
    if ($actionInstance->validate()) {
        $actionInstance->execute();
    }
    $result = $actionInstance->getResult();
    die(json_encode($result->returnResult()));
} catch (EmptyMethodException $e) {
    die('KanCart OpenAPI v' . API_VERSION . ' is installed on Opencart v' . KC_CART_VERSION . '. OpenCart Plugin v' . KANCART_PLUGIN_VERSION);
} catch (Exception $e) {
    die(json_encode(array('result' => KancartResult::STATUS_FAIL, 'code' => KancartResult::ERROR_UNKNOWN_ERROR, 'info' => $e->getMessage() . ',' . $e->getTraceAsString())));
}
?>
