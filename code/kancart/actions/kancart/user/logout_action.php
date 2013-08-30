<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_logout_action extends UserAuthorizedAction {

    public function execute() {
        $userService = ServiceFactory::factory('User');
        $userService->logout();
        $this->setSuccess();
    }

}

?>
