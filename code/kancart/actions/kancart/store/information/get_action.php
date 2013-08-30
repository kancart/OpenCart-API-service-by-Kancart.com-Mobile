<?php

class kancart_store_information_get_action extends BaseAction {

    public function execute() {
        $storeService = ServiceFactory::factory('Store');
        $this->setSuccess($storeService->getStoreInfo());
    }

}

?>
