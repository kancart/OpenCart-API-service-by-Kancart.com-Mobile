<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_address_update_action extends UserAuthorizedAction {

    public function execute() {
        $userService = ServiceFactory::factory('User');

        $address = prepare_address();
        $updateResult = $userService->updateAddress($address);
        if ($updateResult === true) {
            $this->setSuccess();
            return;
        }
        $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER, join(',', $updateResult));
    }

}

?>
