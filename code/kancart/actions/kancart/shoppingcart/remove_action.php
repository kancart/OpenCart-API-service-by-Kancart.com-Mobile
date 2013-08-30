<?php
if (!defined('IN_KANCART'))
{
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_remove_action extends BaseAction {

    public function validate() {
        if (!parent::validate()) {
            return false;
        }
        $cartItemId = $this->getParam('cart_item_id');
        if (!isset($cartItemId) || $cartItemId=='') {
            $this->setError(KancartResult::ERROR_CART_INPUT_PARAMETER, 'cart item id is not specified .');
            return false;
        }
        return true;
    }

    public function execute() {
        $cartItemId = $this->getParam('cart_item_id');
        $cartService = ServiceFactory::factory('ShoppingCart');
        $cartService->remove($cartItemId);
        $this->setSuccess($cartService->get());
    }

}

?>
