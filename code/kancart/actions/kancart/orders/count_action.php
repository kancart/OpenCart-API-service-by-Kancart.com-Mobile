<?php
if (!defined('IN_KANCART'))
{
    header('HTTP/1.1 404 Not Found');
    die();
}


class kancart_orders_count_action extends UserAuthorizedAction {

    public function execute() {
         $this->setSuccess(array(
            'order_counts' => array(
                array( 'status_ids' => 'all',
                      'status_name' => 'My Orders',
                      'count' => ServiceFactory::factory('Order')->getUserOrderCounts())
                )
            )
        );
    }

}

?>
