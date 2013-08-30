<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_address_add_action extends UserAuthorizedAction {

    public function execute() {
        $userService = ServiceFactory::factory('User');
        $address = prepare_address();
        $result = $userService->addAddress($address);
        if (is_numeric($result)) {
            $this->setSuccess();
            return;
        }
        $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER, join(',', $result));
    }

}

?>
