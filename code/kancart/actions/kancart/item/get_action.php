<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_item_get_action extends BaseAction {

    public function execute() {
        $productId = intval($this->getParam('item_id'));
        $productService = ServiceFactory::factory('Product');
        $product = $productService->getProduct($productId);
        if ($product) {
            $this->setSuccess(array('item' => $product));
        } else {
            $this->setError(KancartResult::ERROR_ITEM_INPUT_PARAMETER);
        }
    }

}

?>
