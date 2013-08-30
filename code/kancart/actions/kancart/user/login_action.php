<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_login_action extends BaseAction {

    public function execute() {
        $userService = ServiceFactory::factory('User');
        $username = is_null($this->getParam('uname')) ? '' : trim($this->getParam('uname'));
        if (empty($username)) {
            $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER, 'User name is empty.');
            return;
        }
        $encryptedPassword = is_null($this->getParam('pwd')) ? '' : trim($this->getParam('pwd'));
        $password = CryptoUtil::Crypto($encryptedPassword, 'AES-256', KANCART_APP_SECRET, false);
        if (!$password) {
            $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER, 'Password is empty.');
            return;
        }
        $loginInfo = array(
            'email' => $username,
            'password' => $password
        );
        $login = $userService->login($loginInfo);
        if (is_string($login)) {
            $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER, $login);
            return;
        }

        $cacheKey = $this->customer->getCustomerGroupId() . '-' . $this->config->get('config_customer_price');
        if ($this->config->get('config_tax')) {
            $query = $this->db->query("SELECT gz.geo_zone_id FROM " . DB_PREFIX . "geo_zone gz LEFT JOIN " . DB_PREFIX . "zone_to_geo_zone z2gz ON (z2gz.geo_zone_id = gz.geo_zone_id) WHERE (z2gz.country_id = '0' OR z2gz.country_id = '" . (int) $this->customer->country_id . "') AND (z2gz.zone_id = '0' OR z2gz.zone_id = '" . (int) $this->customer->zone_id . "')");
            if ($query->num_rows) {
                $cacheKey .= '-1-' . $query->row['geo_zone_id'];
            } else {
                $cacheKey .= '-1-0';
            }
        } else {
            $cacheKey .= '-0-0';
        }
        $info = array(
            'sessionkey' => md5($username . uniqid(mt_rand(), true)),
            'cachekey' => $cacheKey);
        $this->setSuccess($info);
    }

}

?>
