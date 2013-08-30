<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_coupons_update_action extends BaseAction {

    public function execute() {
        $checkoutService = ServiceFactory::factory('Checkout');
        $couponCode = $_REQUEST['coupon_id'];
        $checkoutService->updateCoupon($couponCode);
        $this->setSuccess($checkoutService->detail());
    }

}

?>
