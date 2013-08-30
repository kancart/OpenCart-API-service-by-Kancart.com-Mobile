<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_user_addresses_get_action extends UserAuthorizedAction {

    public function execute() {
        $service = ServiceFactory::factory('User');
        $addresses = $service->getAddresses();
        $this->setSuccess(array("addresses" => $addresses));
    }

}

?>
