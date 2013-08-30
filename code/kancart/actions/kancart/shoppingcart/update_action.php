<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_update_action extends BaseAction {

    public function validate() {
        if (!parent::validate()) {
            return false;
        }
        $cartItemId = $this->getParam('cart_item_id');
        $qty = $this->getParam('qty');
        $validateInfo = array();
        if (!isset($cartItemId)) {
            $validateInfo[] = 'Cart item id is not specified .';
        }
        if (!isset($qty) || !is_numeric($qty) || $qty <= 0) {
            $validateInfo[] = 'Qty is not valid.';
        }
        if ($validateInfo) {
            $this->setError(KancartResult::ERROR_CART_INPUT_PARAMETER, $validateInfo);
            return false;
        }
        return true;
    }

    public function execute() {
        $cartItemId = $this->getParam('cart_item_id');
        $qty = intval($this->getParam('qty')) > 0 ? intval($this->getParam('qty')) : 1;
        $cartService = ServiceFactory::factory('ShoppingCart');

        if (method_exists($cartService, 'checkMinimunOrder')) {
            $error = $cartService->checkMinimunOrder(intval($cartItemId), $qty);
        } else {
            $error = array();
        }
        if (is_array($error) && sizeof($error) == 0) {
            $cartService->update($cartItemId, $qty);
            $result = $cartService->get();
        } else {
            $result = $cartService->get();
            $result['messages'] = $error;
        }

        $this->setSuccess($result);
    }

}

?>
