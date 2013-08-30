<?php
if (!defined('IN_KANCART'))
{
    header('HTTP/1.1 404 Not Found');
    die();
}


class kancart_order_cancel_action extends UserAuthorizedAction {

    public function execute() {
        $orderId = $this->getParam('order_id');
        if (isset($orderId)) {
            if (!OrderService::singleton()->cancel($orderId, $_SESSION['user_id'])) {
                $this->setError('', $GLOBALS['err']->get_all());
                return;
            }
            $oneOrderInfo = OrderService::singleton()->getOneOrderInfoById($_SESSION['user_id'], $orderId);
            $this->setSuccess(array('order' => $oneOrderInfo));
        }
    }

}

?>
