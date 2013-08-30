<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * Checkout utility
 * @package services
 * @author hujs
 */
class CheckoutService extends BaseService {

    public function __construct() {
        parent::__construct();
        $this->language->load('checkout/cart');
    }

    /**
     * get checkout detail information
     * 
     */
    public function detail() {
        $detail = array();
        $detail['is_virtual'] = $is_virtual = $this->isVirtual();
        $detail['shipping_address'] = $this->getShippingAddress();
        $detail['billing_address'] = $this->getBillingAddress();
        $detail['review_orders'] = array($this->getReviewOrder());
        if (!$detail['review_orders'][0]['selected_shipping_method_id']) {
            $detail['need_select_shipping_method'] = true;
        }
        $detail['need_billing_address'] = defined('NEED_BILLING_ADDRESS') ? NEED_BILLING_ADDRESS : false;
        $detail['need_shipping_address'] = !$is_virtual && !ServiceFactory::factory('Store')->checkAddressIntegrity($detail['shipping_address']);
        $detail['payment_methods'] = $this->getPaymentMethods();
        $detail['price_infos'] = $this->getPriceInfos();
        $detail['messages'] = $this->getCheckoutMessages();
        return $detail;
    }

    public function getCheckoutMessages() {
        if (isset($this->session->data['checkout_messages']) && $this->session->data['checkout_messages']) {
            $msg = $this->session->data['checkout_messages'];
            unset($this->session->data['checkout_messages']);
            return $msg;
        }
        return array();
    }

    private function isVirtual() {
        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            if (!$product['download']) {
                return false;
            }
        }

        return true;
    }

    public function getReviewOrder() {
        $order = array();
        $order['cart_items'] = ServiceFactory::factory('ShoppingCart')->getProducts();
        $order['shipping_methods'] = $this->getOrderShippingMethods();
        $selectedShippingMethod = $this->selectShippingMethod();
        $order['selected_shipping_method_id'] = $selectedShippingMethod;
        return $order;
    }

    private function getSendToAddressId() {
        if (!isset($this->session->data['shipping_address_id']) || empty($this->session->data['shipping_address_id'])) {
            $this->session->data['shipping_address_id'] = $this->customer->getAddressId();
        }
        $this->session->data['shipping_address_id'] = $this->checkAddress($this->session->data['shipping_address_id']);

        return $this->session->data['shipping_address_id'];
    }

    public function checkAddress($addressId) {
        $this->load->model('account/address');
        if (!$this->model_account_address->getAddress($addressId)) { //shipping address id is not exist
            $addresses = $this->model_account_address->getAddresses();
            if ($addresses) {
                $newDefaultAddressId = $addresses[0]['address_id'];
                if ($this->customer->getAddressId() == $addressId) {//default address id is not exist
                    $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int) $newDefaultAddressId . "' WHERE customer_id = '" . (int) $this->customer->getId() . "'");
                }
                $addressId = $newDefaultAddressId;
            }
        }

        return $addressId;
    }

    public function getShippingAddress() {
        $sendTo = $this->getSendToAddressId();
        if ($sendTo) {
            $userService = ServiceFactory::factory('User');
            $addressInfo = $userService->getAddress($sendTo);
            $this->session->data['shipping_country_id'] = $addressInfo['country_id'];
            $this->session->data['shipping_zone_id'] = $addressInfo['zone_id'];
            $this->session->data['shipping_postcode'] = $addressInfo['postcode'];

            return $addressInfo;
        }
        return array();
    }

    public function getBillingAddress() {
        $billto = $this->getBillToAddressId();
        if ($billto) {
            $userService = ServiceFactory::factory('User');
            $billingAddress = $userService->getAddress($billto);
            $this->session->data['payment_country_id'] = $billingAddress['country_id'];
            $this->session->data['payment_zone_id'] = $billingAddress['zone_id'];
            return $billingAddress;
        }
        return array();
    }

    public function getBillToAddressId() {
        if (!isset($this->session->data['payment_address_id']) && isset($this->session->data['shipping_address_id']) && $this->session->data['shipping_address_id']) {
            $this->session->data['payment_address_id'] = $this->session->data['shipping_address_id'];
        }

        if (!isset($this->session->data['payment_address_id']) || empty($this->session->data['payment_address_id'])) {
            $this->session->data['payment_address_id'] = $this->customer->getAddressId();
        }

        if ($this->session->data['payment_address_id'] != $this->session->data['shipping_address_id']) {
            $this->session->data['payment_address_id'] = $this->checkAddress($this->session->data['payment_address_id']);
        }

        return $this->session->data['payment_address_id'];
    }

    public function getPaymentMethods() {
        $this->load->model('account/address');

        if ($this->customer->isLogged() && isset($this->session->data['payment_address_id'])) {
            $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
        } elseif (isset($this->session->data['guest'])) {
            $payment_address = $this->session->data['guest']['payment'];
        }

        // Totals
        $total_data = array();
        $total = 0;
        $taxes = $this->cart->getTaxes();
        $this->load->model('setting/extension');
        $sort_order = array();
        $results = $this->model_setting_extension->getExtensions('total');
        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
        }
        array_multisort($sort_order, SORT_ASC, $results);
        foreach ($results as $result) {
            if ($this->config->get($result['code'] . '_status')) {
                $this->load->model('total/' . $result['code']);
                $this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
            }
        }

        // Payment Methods
        $method_data = array();
        $this->load->model('setting/extension');
        $results = $this->model_setting_extension->getExtensions('payment');

        foreach ($results as $result) {
            if ($this->config->get($result['code'] . '_status')) {
                $this->load->model('payment/' . $result['code']);

                $method = $this->{'model_payment_' . $result['code']}->getMethod($payment_address, $total);

                if ($method) {
                    $method_data[$result['code']] = $method;
                }
            }
        }

        $sort_order = array();
        foreach ($method_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }
        array_multisort($sort_order, SORT_ASC, $method_data);
        $this->session->data['payment_methods'] = $method_data;

        $availablePayment = array();
        $paypalWpsPayment = array();
        foreach ($this->session->data['payment_methods'] as $key => $p) {
            if ($key == 'pp_standard') {
                $paypalWpsPayment = array(
                    'pm_id' => 'paypal',
                    'pm_titel' => $p['title'],
                    'pm_code' => $p['id'],
                    'img_url' => ''
                );
            }
        }
        if ($paypalWpsPayment) {
            $availablePayment[] = $paypalWpsPayment;
        }
        return $availablePayment;
    }

    public function selectShippingMethod() {
        if (!isset($this->session->data['shipping_method']) || !$this->session->data['shipping_method']) {
            reset($this->session->data['shipping_methods']);
            $firstShippingMethod = current($this->session->data['shipping_methods']);
            reset($firstShippingMethod['quote']);
            $this->session->data['shipping_method'] = current($firstShippingMethod['quote']);
        }
        if (isset($this->session->data['shipping_method']['id'])) {
            return $this->session->data['shipping_method']['id'];
        } else if (isset($this->session->data['shipping_method']['code'])) {
            return $this->session->data['shipping_method']['code'];
        }

        return 1;
    }

    /**
     * 
     * @return boolean|string
     * @author hujs
     */
    public function getOrderShippingMethods() {

        $currency = $this->currency->getCode();
        if (!isset($this->session->data['shipping_methods']) || !$this->session->data['shipping_methods']) {
            $this->session->data['shipping_methods'] = array();
        }
        $availableShippingMethods = array();

        if (!$this->cart->hasShipping()) {
            unset($this->session->data['shipping_address_id']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);

            $this->hasShipping = false;
            return array(array(
                    'sm_id' => 1,
                    'title' => 'Free Shipping',
                    'price' => 0,
                    'currency' => $currency,
                    'description' => ''
                    ));
        }

        $this->load->model('account/address');
        $shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
        if ($shipping_address) {
            $this->setShippingMethods($shipping_address);

            foreach ($this->session->data['shipping_methods'] as $key => $method) {
                foreach ($method['quote'] as $quote) {
                    $shippingMethod = array();
                    $shippingMethod['sm_id'] = isset($quote['id']) ? $quote['id'] : $quote['code'];
                    $shippingMethod['title'] = $method['title'];
                    $shippingMethod['price'] = $this->format($quote['cost']);
                    $shippingMethod['currency'] = $currency;
                    $shippingMethod['description'] = isset($quote['text']) ? $quote['text'] : '';
                    $availableShippingMethods[] = $shippingMethod;
                }
            }
        }

        return $availableShippingMethods;
    }

    /**
     * get shipping methods 
     * @author hujs
     */
    private function setShippingMethods($shipping_address) {
        if (!sizeof($this->session->data['shipping_methods'])) {
            // Shipping Methods
            $quote_data = array();
            $this->load->model('setting/extension');
            $results = $this->model_setting_extension->getExtensions('shipping');

            foreach ($results as $result) {
                if ($this->config->get($result['code'] . '_status')) {
                    $this->load->model('shipping/' . $result['code']);

                    $quote = $this->{'model_shipping_' . $result['code']}->getQuote($shipping_address);

                    if ($quote) {
                        $quote_data[$result['code']] = array(
                            'title' => $quote['title'],
                            'quote' => $quote['quote'],
                            'sort_order' => $quote['sort_order'],
                            'error' => $quote['error']
                        );
                    }
                }
            }

            $sort_order = array();
            foreach ($quote_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }
            array_multisort($sort_order, SORT_ASC, $quote_data);
            $this->session->data['shipping_methods'] = $quote_data;
        }
    }

    public function getPriceInfos() {
        $shoppingCart = ServiceFactory::factory('ShoppingCart');
        return $shoppingCart->getPriceInfo();
    }

    public function addAddress($address) {
        if ($this->customer->isLogged()) {
            $result = ServiceFactory::factory('User')->addAddress($address);
            //for now,keep the two address same
            $this->session->data['shipping_address_id'] = $result;
            $this->session->data['payment_address_id'] = $result;
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['shipping_method']);
            return $result;
        }
    }

    public function updateAddress($addressBookId, $address = array()) {
        if ($this->customer->isLogged()) {
            if ($addressBookId) {
                if ($address) {
                    $address['address_id'] = $addressBookId;
                    $userService = ServiceFactory::factory('User');
                    $userService->updateAddress($address);
                }
                //for now,keep the two address same
                $this->session->data['shipping_address_id'] = $addressBookId;
                $this->session->data['payment_address_id'] = $addressBookId;
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['shipping_method']);
            }
        }
    }

    public function updateShippingMethod($shippingMethod) {
        if ($shippingMethod && $this->session->data['shipping_methods']) {
            $shipping = explode('.', $shippingMethod);
            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
        }
    }

    public function validateCoupon($couponCode) {
        $error = '';
        $this->load->model('checkout/coupon');
        $this->language->load('total/coupon');
        $coupon = $this->model_checkout_coupon->getCoupon($couponCode);

        if (!$coupon) {
            $error = $this->language->get('error_coupon');
        }
        if (!$error) {
            return true;
        }
        return $error;
    }

    public function updateCoupon($couponCode) {
        $ret = $this->validateCoupon($couponCode);
        if ($ret === true) {
            $this->session->data['coupon'] = $couponCode;
            $this->session->data['checkout_messages'] = array(
                array(
                    'type' => 'success',
                    'content' => $this->language->get('text_success')
                )
            );
            return true;
        }
        $this->session->data['checkout_messages'] = array(
            array(
                'type' => 'fail',
                'content' => $ret
            )
        );
        return false;
    }

}

?>
