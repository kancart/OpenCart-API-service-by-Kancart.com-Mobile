<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_items_get_action extends BaseAction {

    public function execute() {
        if ($this->isSortByAllowed($_REQUEST['order_by'])) {
            $sortOptions = explode(':', $_REQUEST['order_by']);
            $_REQUEST['sort_by'] = $sortOptions[0];
            $sortOptions[1] ? $_REQUEST['sort_order'] = $sortOptions[1] : $_REQUEST['sort_order'] = 'desc';
        } else {
            $_REQUEST['sort_by'] = 'p.product_id';
            $_REQUEST['sort_order'] = 'desc';
        }
        if(isset($_REQUEST['item_ids']) && $_REQUEST['item_ids']){
            $_REQUEST['item_ids'] = explode(',', $_REQUEST['item_ids']);
        }
        $productService = ServiceFactory::factory('Product');
        $this->setSuccess($productService->getProducts($_REQUEST));
    }

    public function isSortByAllowed($sortBy) {
        if (!$sortBy) {
            return false;
        }
        $storeService = ServiceFactory::factory('Store');
        $orderByFound = false;
        foreach ($storeService->getCategorySortOptions() as $options) {
            foreach ($options as $option) {
                if ($option['code'] == $sortBy) {
                    $orderByFound = true;
                    break;
                }
            }
            if ($orderByFound) {
                break;
            }
        }
        return $orderByFound;
    }

}

?>
