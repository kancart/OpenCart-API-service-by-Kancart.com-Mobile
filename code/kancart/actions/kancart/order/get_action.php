<?php
if (!defined('IN_KANCART'))
{
    header('HTTP/1.1 404 Not Found');
    die();
}


class kancart_order_get_action extends UserAuthorizedAction {

    public function execute() {
        $orderId = $this->getParam('order_id');
        $orderService = ServiceFactory::factory('Order');
        $oneOrderInfo = $orderService->getOneOrderInfoById($orderId);
        $this->setSuccess(array('order' => $oneOrderInfo));
    }

}

?>
