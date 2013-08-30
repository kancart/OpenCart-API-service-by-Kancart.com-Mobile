<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_checkout_done_action extends UserAuthorizedAction {

    public function execute() {
        switch ($_REQUEST['checkout_type']) {
            case 'cart':
                if ($_REQUEST['payment_method_id'] == 'paypal') {
                    $actionInstance = ActionFactory::factory('KanCart.ShoppingCart.PayPalWPS.Done');
                    $actionInstance->init();
                    $actionInstance->execute();
                    $this->result = $actionInstance->getResult();
                } else {
                    $kancartPaymentService = ServiceFactory::factory('KancartPayment');
                    list($result, $order_id) = $kancartPaymentService->kancartPaymentDone($_REQUEST['order_id'], $_REQUEST['custom_kc_comments'], $_REQUEST['payment_status']);
                    if ($result === TRUE) {
                        $orderService = ServiceFactory::factory('Order');
                        $info = $orderService->getPaymentOrderInfoById($order_id);
                        $this->setSuccess($info);
                    } else {
                        $this->setError('0xFFFF', $order_id);
                    }
                }
            case 'order':
                break;
            default : break;
        }
    }

}

?>
