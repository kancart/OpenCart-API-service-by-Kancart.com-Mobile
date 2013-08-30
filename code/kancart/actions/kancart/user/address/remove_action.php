<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_address_remove_action extends UserAuthorizedAction {

    public function execute() {
        $userService = ServiceFactory::factory('User');
        $addressBookId = intval($this->getParam('address_book_id'));
        if ($addressBookId) {
            $result = $userService->deleteAddress($addressBookId);
            if (true === $result) {
                $this->setSuccess();
                return;
            }
            $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER, join(',', $result));
        }
        $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER, 'Address book is empty');
    }

}

?>
