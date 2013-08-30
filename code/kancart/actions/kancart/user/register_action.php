<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_register_action extends BaseAction {

    public function execute() {
        $userService = ServiceFactory::factory('User');
        $username = is_null($this->getParam('email')) ? '' : trim($this->getParam('email'));
        $enCryptedPassword = is_null($this->getParam('pwd')) ? '' : trim($this->getParam('pwd'));
        $password = CryptoUtil::Crypto($enCryptedPassword, 'AES-256', KANCART_APP_SECRET, false);

        $this->language->load('account/register');
        if ((strlen(utf8_decode($username)) > 96) || !preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $username)) {
            $this->setError(KancartResult::ERROR_USER_INVALID_USER_DATA, $this->language->get('error_email'));
            return;
        }
        if (strlen($password) < 4 || strlen($password) > 20) {
            $this->setError(KancartResult::ERROR_USER_INVALID_USER_DATA, $this->language->get('error_password'));
            return;
        }

        $firstname = is_null($this->getParam('firstname')) ? '' : trim($this->getParam('firstname'));
        $lastname = is_null($this->getParam('lastname')) ? '' : trim($this->getParam('lastname'));
        $telephone = is_null($this->getParam('telephone')) ? '' : trim($this->getParam('telephone'));
        $regisetInfo = array(
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $username,
            'telephone' => $telephone,
            'password' => $password
        );
        if (!$userService->register($regisetInfo)) {
            $this->setError(KancartResult::ERROR_USER_INVALID_USER_DATA, $msg);
            return;
        }
        // succed registering
        $this->setSuccess();
    }

}

?>
