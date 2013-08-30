<?php
if (!defined('IN_KANCART'))
{
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_order_paypalwps_done_action extends UserAuthorizedAction {

    public function execute() {
        global $db, $ecs;
        $orderId = $this->getParam('order_id');
        $amt = $this->getParam('amt');
        $tx = $this->getParam('tx');
        $currency = $this->getParam('cc');
        $sql = 'select * from ' . $ecs->table('order_info') . ' where order_id = ' . $orderId;
        $order = $db->getRow($sql);
        if ($order) {
            if ($order['pay_status'] == PS_PAYED) {
                $this->setSuccess(array(
                    'transaction_id' => $tx,
                    'payment_total' => $amt,
                    'currency' => $currency,
                    'order_id' => $orderId,
                    'messages' => ''
                ));
                return;
            }
        }
        $this->setError('', 'can not get transaction info .');
    }

}

?>
