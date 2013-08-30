<?php
if (!defined('IN_KANCART'))
{
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_orders_get_action extends UserAuthorizedAction {

    public function execute() {
        $pageNo = $this->getParam('page_no');
        $pageSize = $this->getParam('page_size');
        
        $parameter = array('page_no'=>(isset($pageNo) && is_numeric($pageNo) ? intval($pageNo) : 1),
                           'page_size' => (isset($pageSize) && is_numeric($pageSize) ? (intval($pageSize) > 30 ? 30 : intval($pageSize)) : 10),
                           'status_id' => $this->getParam('status_id'),
                           'customer_id' => $_SESSION['customer_id']);
        
        $orderService = ServiceFactory::factory('Order');
        $orderInfos = $orderService->getOrderInfos($parameter);
        $this->setSuccess($orderInfos);
    }

}

?>
