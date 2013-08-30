<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_get_action extends BaseAction {

    public function execute() {
        $shoppingCartInfo = ServiceFactory::factory('ShoppingCart')->get();
        $this->setSuccess($shoppingCartInfo);
    }

}

?>
