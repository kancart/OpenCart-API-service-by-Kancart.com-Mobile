<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class KancartResult {

    const STATUS_FAIL = 'fail';
    const STATUS_SUCCESS = 'success';
    const ERROR_SYSTEM_INVALID_API = '0x0001';
    const ERROR_SYSTEM_INVALID_SESSION_KEY = '0x0002';
    const ERROR_SYSTEM_TIME_OVER_TEM_MIN = '0x0003';
    const ERROR_SYSTEM_INVALID_RESPONSE_FORMAT = '0x0004';
    const ERROR_SYSTEM_INVALID_API_VERSION = '0x0005';
    const ERROR_SYSTEM_INVALID_ENCRYPTION_METHOD = '0x0006';
    const ERROR_SYTEM_LANAGE_IS_NOT_SUPPORTED = '0x0007';
    const ERROR_SYSTEM_CURRENCY_IS_NOT_SUPPORTED = '0x0008';
    const ERROR_SYSTEM_AUTHENTICATION_FAILED = '0x0009';
    const ERROR_SYSTEM_TIME_OUT = '0x0010';
    const ERROR_SYSTEM_DATA = '0x0011';
    const ERROR_SYSTEM_DATABASE = '0x0012';
    const ERROR_SYSETM_SERVER = '0x0013';
    const ERROR_SYSTEM_PERMISSION_DENIED = '0x0014';
    const ERROR_SYSTEM_SERVICE_UNAVAILABLE = '0x0015';
    const ERROR_SYSTEM_INVALID_SIGNATURE = '0x0016';
    const ERROR_SYSTEM_INVALID_SESSION_ID = '0x0017';
    const ERROR_SYSTEM_INVALID_METHOD = '0x0018';
    const ERROR_USER_INVALID_LOGIN_OR_PASSWORD = '0x1001';
    const ERROR_USER_LOGIN_PASSWORD_REQUIRED = '0x1002';
    const ERROR_USER_VERIFICATION_CODE = '0x1003';
    const ERROR_USER_INVALID_ADDRESS_ID = '0x1004';
    const ERROR_USER_INVALID_RETURN_FIELDS = '0x1005';
    const ERROR_USER_NO_INFORMATION_IN_THE_REGION = '0x1006';
    const ERROR_USER_AUTHENTICATION_PROBLEM = '0x1007';
    const ERROR_USER_NOT_LOGGED_IN = '0x1008';
    const ERROR_USER_ALREAD_LOGGED_IN = '0x1009';
    const ERROR_USER_INVALID_USER_DATA = '0x1010';
    const ERROR_USER_INPUT_PARAMETER = '0x1011';
    const ERROR_CATEGORY_INVALID_RETURN_FIELDS = '0x2001';
    const ERROR_CATEGORY_INPUT_PARAMETER = '0x2002';
    const ERROR_CATEGORY_NO_SUB_CATEGORY = '0x2003';
    const ERROR_ITEM_INVALID_RETURN_FIELDS = '0x3001';
    const ERROR_ITEM_INPUT_PARAMETER = '0x3002';
    const ERROR_POSTAGE_INVALID_RETURN_FIELDS = '0x4001';
    const ERROR_POSTAGE_INPUT_PARAMETER = '0x4002';
    const ERROR_CART_INVALID_ITEM_ID = '0x5001';
    const ERROR_CART_PRODUCT_IS_UNAVAILABLE = '0x5002';
    const ERROR_CART_INPUT_PARAMETER = '0x5003';
    const ERROR_ORDER_INVALID_RETURN_FIELDS = '0x6001';
    const ERROR_ORDER_INVALID_ORDER_ID = '0x6002';
    const ERROR_ORDER_INPUT_PARAMETER = '0x6003';
    const ERROR_FAVORITES_USER_NOT_EXIST = '0x7001';
    const ERROR_FAVORITES_INVALID_ITEM_ID = '0x7002';
    const ERROR_FAVORITES_INVALID_RETURN_FIELDS = '0x8001';
    const ERROR_RATING_INVALID_ITEM_ID = '0x8002';
    const ERROR_RATING_INPUT_PARAMETER = '0x8003';
    const ERROR_RATING_USER_NOT_EXIST = '0x8004';
    const ERROR_UNKNOWN_ERROR = '0xFFFF';

    static $errorDesc = array(
        self::ERROR_SYSTEM_INVALID_API => 'Invalid API (System)',
        self::ERROR_SYSTEM_INVALID_SESSION_KEY => 'Invalid SessionKey (System)',
        self::ERROR_SYSTEM_TIME_OVER_TEM_MIN => 'Time error over 10min (System)',
        self::ERROR_SYSTEM_INVALID_RESPONSE_FORMAT => 'Invalid response format (System)',
        self::ERROR_SYSTEM_INVALID_API_VERSION => 'Invalid API version (System)',
        self::ERROR_SYSTEM_INVALID_ENCRYPTION_METHOD => 'Invalid encryption method (System)',
        self::ERROR_SYTEM_LANAGE_IS_NOT_SUPPORTED => 'Language is not supported (System)',
        self::ERROR_SYSTEM_CURRENCY_IS_NOT_SUPPORTED => 'Currency is not supported (System)',
        self::ERROR_SYSTEM_AUTHENTICATION_FAILED => 'Authentication failed (System)',
        self::ERROR_SYSTEM_TIME_OUT => 'Time out (System)',
        self::ERROR_SYSTEM_DATA => 'Data error (System)',
        self::ERROR_SYSTEM_DATABASE => 'DataBase error (System)',
        self::ERROR_SYSETM_SERVER => 'Server error (System)',
        self::ERROR_SYSTEM_PERMISSION_DENIED => 'Permission denied (System)',
        self::ERROR_SYSTEM_SERVICE_UNAVAILABLE => 'Service unavailable (System)',
        self::ERROR_SYSTEM_INVALID_SIGNATURE => 'Invalid signature (System)',
        self::ERROR_SYSTEM_INVALID_SESSION_ID => 'Invalid session ID (System)',
        self::ERROR_SYSTEM_INVALID_METHOD => 'Invalid method (System)',
        self::ERROR_USER_INVALID_LOGIN_OR_PASSWORD => 'Invalid login or password (User)',
        self::ERROR_USER_LOGIN_PASSWORD_REQUIRED => 'Login and password are required (User)',
        self::ERROR_USER_VERIFICATION_CODE => 'Verification code error (User)',
        self::ERROR_USER_INVALID_ADDRESS_ID => 'Invalid AddressID (User)',
        self::ERROR_USER_INVALID_RETURN_FIELDS => 'Invalid return fields (User)',
        self::ERROR_USER_NO_INFORMATION_IN_THE_REGION => 'No information in the region (User)',
        self::ERROR_USER_AUTHENTICATION_PROBLEM => 'User authentication problem (User)',
        self::ERROR_USER_NOT_LOGGED_IN => 'User not logged in (User)',
        self::ERROR_USER_ALREAD_LOGGED_IN => 'You are already logged in (User)',
        self::ERROR_USER_INVALID_USER_DATA => 'Invalid user data (User)',
        self::ERROR_USER_INPUT_PARAMETER => 'Input parameter error (User)',
        self::ERROR_CATEGORY_INVALID_RETURN_FIELDS => 'Invalid return fields (Category)',
        self::ERROR_CATEGORY_INPUT_PARAMETER => 'Input parameter error (Category)',
        self::ERROR_CATEGORY_NO_SUB_CATEGORY => 'No subcategory in it. (Category)',
        self::ERROR_ITEM_INVALID_RETURN_FIELDS => 'Invalid return fields (Item)',
        self::ERROR_ITEM_INPUT_PARAMETER => 'Input parameter error (Item)',
        self::ERROR_POSTAGE_INVALID_RETURN_FIELDS => 'Invalid return fields (Postage)',
        self::ERROR_POSTAGE_INPUT_PARAMETER => 'Input parameter error (Postage)',
        self::ERROR_CART_INVALID_ITEM_ID => 'Invalid ItemID (Cart)',
        self::ERROR_CART_PRODUCT_IS_UNAVAILABLE => 'Product is unavailable (Cart)',
        self::ERROR_CART_INPUT_PARAMETER => 'Input parameter error (Cart)',
        self::ERROR_ORDER_INVALID_RETURN_FIELDS => 'Invalid return fields (Order)',
        self::ERROR_ORDER_INVALID_ORDER_ID => 'Invalid OrderID (Order)',
        self::ERROR_ORDER_INPUT_PARAMETER => 'Input parameter error (Order)',
        self::ERROR_FAVORITES_USER_NOT_EXIST => 'User does not exist (Favorites)',
        self::ERROR_FAVORITES_INVALID_ITEM_ID => 'Invalid ItemID (Favorites)',
        self::ERROR_FAVORITES_INVALID_RETURN_FIELDS => 'Invalid return fields (Rating)',
        self::ERROR_RATING_INVALID_ITEM_ID => 'Invalid ItemID (Rating)',
        self::ERROR_RATING_INPUT_PARAMETER => 'Input parameter error (Rating)',
        self::ERROR_RATING_USER_NOT_EXIST => 'User does not exist (Rating)',
        self::ERROR_UNKNOWN_ERROR => 'Unknown error .'
    );

    const SHOPPING_CART = 'shopping_cart';
    const LOGIN = 'login';
    const CHECKOUT_REVIEW = 'checkout_review';

    protected $result;
    protected $code;
    protected $info;
    protected $fields;

    public function setSuccess($info = null, $fields = null) {
        $this->result = self::STATUS_SUCCESS;
        $this->code = '0x0000';
        $this->info = $info;
        $this->fields = $fields;
    }

    public function setError($code, $msg = null, $redirect_to_page = false) {
        $this->result = self::STATUS_FAIL;
        $this->code = $code;
        $this->info = array();
        if (is_null($msg)) {
            $this->info['err_msg'] = self::$errorDesc[$code];
        } else {
            $this->info['err_msg'] = $msg;
        }
        if (is_null($this->info['err_msg'])) {
            $this->info['err_msg'] = 'Undefied error';
        }

        if ($redirect_to_page) {
            $this->info['redirect_to_page'] = $redirect_to_page;
        }
    }

    public function returnResult() {
        return array('result' => $this->result, 'code' => $this->code, 'info' => $this->info);
    }

}

?>
