<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_addresses_update_action extends BaseAction {

    private function updateAddress($shippingAddressId, $address) {
        $checkoutService = ServiceFactory::factory('Checkout');
        $checkoutService->updateAddress($shippingAddressId, $address);
    }

    private function addAddress($shippingAddress) {
        $checkoutService = ServiceFactory::factory('Checkout');
        $checkoutService->addAddress($shippingAddress);
    }

    public function execute() {
        $shippingAddressBookId = max($_REQUEST['shipping_address_book_id'], $_REQUEST['billing_address_book_id']);
        $shippingAddressJson = max($_REQUEST['shipping_address'], $_REQUEST['billing_address']);
        if ($shippingAddressBookId) {
            $shippingAddress = array();
            if ($shippingAddressJson) {
                $shippingAddressJson = htmlspecialchars_decode($shippingAddressJson, ENT_COMPAT);
                $this->exportAddressToRequest(json_decode($shippingAddressJson, true));
                $shippingAddress = prepare_address();
            }
            $this->updateAddress($shippingAddressBookId, $shippingAddress);
        } else {
            //add a new address to db
            $shippingAddressJson = htmlspecialchars_decode($shippingAddressJson, ENT_COMPAT);
            $this->exportAddressToRequest(json_decode($shippingAddressJson, true));
            $this->addAddress(prepare_address());
        }
        $this->setSuccess(ServiceFactory::factory('Checkout')->detail());
    }

    private function exportAddressToRequest($address) {
        foreach ($address as $key => $val) {
            $_REQUEST[$key] = $val;
        }
    }

}

?>
