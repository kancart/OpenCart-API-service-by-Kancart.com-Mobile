<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_checkout_detail_action extends UserAuthorizedAction {

    public function execute() {
        if (!$this->cart->hasProducts()) {
            $this->setSuccess(array(
                'redirect_to_page' => 'shopping_cart',
                'messages' => array('Shopping Cart is empty.')
            ));
            return;
        }
        $this->setSuccess(ServiceFactory::factory('Checkout')->detail());
    }

}

?>
