<?php
if (!defined('IN_KANCART'))
{
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_order_complete_action extends UserAuthorizedAction {

    public function execute() {
        $orderId = $this->getParam('order_id');
        if (isset($orderId)) {
            if (!OrderService::singleton()->complete($orderId, $_SESSION['user_id'])) {
                $this->setError('', $GLOBALS['err']->get_all());
                return;
            }
            $this->setSuccess();
        }
    }

}

?>
