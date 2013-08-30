<?php

init();

function init() {
    // Registry
    $registry = new Registry();
    $backPost = $_POST;   //back up $_POST Avoid signature error
    $currency = isset($_POST['currency']) ? $_POST['currency'] : 'USD';

    $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    $registry->set('db', $db);
    $registry->set('session', new Session());

    $config = new Config();
    if (get_project_version() > '1.5') {  // Store     
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') = '" . $db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname(dirname($_SERVER['PHP_SELF']), '/.\\')) . '/') . "'");
        } else {
            $store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`url`, 'www.', '') = '" . $db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/.\\') . '/') . "'");
        }

        if ($store_query->num_rows) {
            $config->set('config_store_id', $store_query->row['store_id']);
        } else {
            $config->set('config_store_id', 0);
        }

        $query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0' OR store_id = '" . (int) $config->get('config_store_id') . "' ORDER BY store_id ASC");
        foreach ($query->rows as $setting) {
            if (isset($setting['serialized']) && $setting['serialized']) {
                $config->set($setting['key'], unserialize($setting['value']));
            } else {
                $config->set($setting['key'], $setting['value']);
            }
        }

        if (!$store_query->num_rows) {
            $config->set('config_url', HTTP_SERVER);
            $config->set('config_ssl', HTTPS_SERVER);
        }
    } else {
        $query = $db->query("SELECT * FROM " . DB_PREFIX . "setting");

        foreach ($query->rows as $setting) {
            $config->set($setting['key'], $setting['value']);
        }

        $query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE url = '" . $db->escape('http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "' OR url = '" . $db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");

        foreach ($query->row as $key => $value) {
            $config->set('config_' . $key, $value);
        }

        define('HTTP_SERVER', $config->get('config_url'));
        define('HTTP_IMAGE', HTTP_SERVER . 'image/');

        if ($config->get('config_ssl')) {
            define('HTTPS_SERVER', 'https://' . substr($config->get('config_url'), 7));
            define('HTTPS_IMAGE', HTTPS_SERVER . 'image/');
        } else {
            define('HTTPS_SERVER', HTTP_SERVER);
            define('HTTPS_IMAGE', HTTP_IMAGE);
        }
    }
    
    $registry->set('load', new Loader($registry));
    $registry->set('config', $config);
    $registry->set('request', new Request());
    $registry->set('cache', new Cache());
    $registry->set('customer', new Customer($registry));
    $registry->set('tax', new Tax($registry));
    $registry->set('weight', new Weight($registry));
    $registry->set('cart', new Cart($registry));

    // make sure that first init language and then init currency,
    // cause the currency model will use the language model
    init_lanaguage($registry);
    init_currency($registry, $currency);
    commons_def($registry);
    $_POST = $backPost;

    $GLOBALS['register'] = $registry;
}

function commons_def($registry) {

    $config = $registry->get('config');

    $urls = $config->get('config_ssl');
    $url = $config->get('config_url');
    $ssl = isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'));
    if (empty($url) && !$ssl) {
        $config->set('config_url', getBaseUrl());
    } else if (empty($urls) && $ssl) {
        $config->set('config_ssl', getBaseUrl());
    }

    if (!defined('HTTP_SERVER')) {
        define('HTTP_SERVER', $config->get('config_url'));

        if ($config->get('config_ssl')) {
            define('HTTPS_SERVER', 'https://' . substr($config->get('config_url'), 7));
        } else {
            define('HTTPS_SERVER', HTTP_SERVER);
        }
    }

    if (!defined('HTTP_IMAGE')) {
        define('HTTP_IMAGE', HTTP_SERVER . 'image/');
        if ($config->get('config_ssl')) {
            define('HTTPS_IMAGE', HTTPS_SERVER . 'image/');
        } else {
            define('HTTPS_IMAGE', HTTP_IMAGE);
        }
    }

    $version = get_project_version();
    define('KC_CART_VERSION', $version);
}

function getBaseUrl($path = '') {
    $protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
    /* 域名或IP地址 */
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        /* 端口 */
        if (isset($_SERVER['SERVER_PORT'])) {
            $port = ':' . $_SERVER['SERVER_PORT'];

            if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                $port = '';
            }
        } else {
            $port = '';
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'] . $port;
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $host = $_SERVER['SERVER_ADDR'] . $port;
        }
    }

    $root = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
    if (substr($root, -1) != '/') {
        $root .= '/';
    }

    return $protocol . $host . $root . $path;
}

function init_currency($registry, $currencyCode = 'USD') {
    $currency = new Currency($registry);
    if (!$currency->has($currencyCode)) {
        $config = $registry->get('config');
        $currencyCode = $config->get('config_currency');
    }
    $currency->set($currencyCode);
    $registry->set('currency', $currency);
}

/**
 * set current language
 * @global type $config
 * @author hujs
 */
function init_lanaguage($registry) {

    $config = $registry->get('config');
    $db = $registry->get('db');

    $languages = array();
    $query = $db->query("SELECT * FROM " . DB_PREFIX . "language where status = 1");

    foreach ($query->rows as $result) {
        $result['code'] = strtolower($result['code']);
        $languages[$result['code']] = array(
            'language_id' => $result['language_id'],
            'name' => $result['name'],
            'code' => $result['code'],
            'locale' => $result['locale'],
            'directory' => $result['directory'],
            'filename' => $result['filename']
        );
    }

    if (isset($_REQUEST['language']) && array_key_exists($_REQUEST['language'], $languages)) {
        $code = $_REQUEST['language'];
    } elseif (isset($_SESSION['language']) && array_key_exists($_SESSION['language'], $languages)) {
        $code = $_SESSION['language'];
    } else {
        $code = $config->get('config_language');
    }

    if (!isset($_SESSION['language']) || $_SESSION['language'] != $code) {
        $_SESSION['language'] = $code;
    }

    $code = strtolower($code);
    $languages_id = intval($languages[$code]['language_id']);
    $language = $languages[$code]['directory'];

    $config->set('config_language_id', $languages_id);
    $config->set('config_language', $languages[$code]['code']);
    $languageModel = new Language($language);
    $registry->set('language', $languageModel);
}

function prepare_address() {
    $address = array(
        'lastname' => isset($_REQUEST['lastname']) ? trim($_REQUEST['lastname']) : '',
        'firstname' => isset($_REQUEST['firstname']) ? trim($_REQUEST['firstname']) : '',
        'country_id' => isset($_REQUEST['country_id']) ? intval($_REQUEST['country_id']) : 0,
        'zone_id' => isset($_REQUEST['zone_id']) ? intval($_REQUEST['zone_id']) : 0,
        'city' => isset($_REQUEST['city']) ? trim($_REQUEST['city']) : '',
        'address_1' => isset($_REQUEST['address1']) ? trim($_REQUEST['address1']) : '',
        'address_2' => isset($_REQUEST['address2']) ? trim($_REQUEST['address2']) : '',
        'postcode' => isset($_REQUEST['postcode']) ? trim($_REQUEST['postcode']) : '',
        'telephone' => isset($_REQUEST['telephone']) ? trim($_REQUEST['telephone']) : ''
    );
    $address['state'] = isset($_REQUEST['state']) ? trim($_REQUEST['state']) : '';
    if (isset($_REQUEST['address_book_id']) && intval($_REQUEST['address_book_id']) > 0) {
        $address['address_id'] = intval($_REQUEST['address_book_id']);
    }
    return $address;
}

function get_project_version($reflash = false) {
    if (!$reflash && isset($_SESSION['KC_VERSION'])) {
        return $_SESSION['KC_VERSION'];
    } else if (defined('VERSION')) {
        return VERSION;
    } else {
        $root = str_replace('\\', '/', dirname(DIR_SYSTEM));
        $content = file_get_contents($root . '/index.php');
        $matches = array();
        if ($content) {
            if (preg_match("/define\s*\(\s*'VERSION'\s*,\s*'(.+)'\s*\)/i", $content, $matches)) {
                $_SESSION['KC_VERSION'] = $matches[1];
                return $matches[1];
            }
        }
    }
    return false;
}

?>
