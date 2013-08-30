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

    private $hasShipping = true;

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
        if ($this->hasShipping && !$detail['review_orders'][0]['selected_shipping_method_id']) {
            $detail['need_select_shipping_method'] = true;
        }
        $detail['need_billing_address'] = defined('NEED_BILLING_ADDRESS') ? NEED_BILLING_ADDRESS : false;
        $detail['need_shipping_address'] = !$is_virtual && !ServiceFactory::factory('Store')->checkAddressIntegrity($detail['shipping_address']);
        $detail['payment_methods'] = $this->getPaymentMethods();
        $this->applyPaypalWpsPayment();
        $detail['price_infos'] = $this->getPriceInfos();
        $detail['messages'] = $this->getCheckoutMessages();
        return $detail;
    }

    public function applyPaypalWpsPayment() {
        $this->session->data['payment_method'] = $this->session->data['payment_methods']['pp_standard'];
    }

    public function getCheckoutMessages() {
        if ($this->session->data['checkout_messages']) {
            $msg = $this->session->data['checkout_messages'];
            unset($this->session->data['checkout_messages']);
            return $msg;
        }
        return array();
    }

    public function getReviewOrder() {
        $order = array();
        $order['cart_items'] = $this->getOrderItems();
        $order['shipping_methods'] = $this->getOrderShippingMethods();
        $selectedShippingMethod = !$this->hasShipping ? 1 : $this->selectShippingMethod();
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

    private function isVirtual() {
        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            if (!$product['download']) {
                return false;
            }
        }

        return true;
    }

    public function getShippingAddress() {
        $sendTo = $this->getSendToAddressId();
        if ($sendTo) {
            $userService = ServiceFactory::factory('User');
            $addr = $userService->getAddress($sendTo);
            $this->tax->setZone($addr['country_id'], $addr['zone_id']);
            return $addr;
        }
        return array();
    }

    public function getBillingAddress() {
        $billto = $this->getBillToAddressId();
        if ($billto) {
            $userService = ServiceFactory::factory('User');
            $billingAddress = $userService->getAddress($billto);
            if (!$this->hasShipping) {
                $this->tax->setZone($billingAddress['country_id'], $billingAddress['zone_id']);
            }
            return $billingAddress;
        }
        return array();
    }

    public function getBillToAddressId() {
        if (!isset($this->session->data['payment_address_id']) && isset($this->session->data['shipping_address_id']) && $this->session->data['shipping_address_id']) {
            $this->session->data['payment_address_id'] = $this->session->data['shipping_address_id'];
        }
        if (!isset($this->session->data['payment_address_id'])) {
            $this->session->data['payment_address_id'] = $this->customer->getAddressId();
        } else if ($this->session->data['payment_address_id'] != $this->session->data['shipping_address_id']) {
            $this->session->data['payment_address_id'] = $this->checkAddress($this->session->data['payment_address_id']);
        }

        return $this->session->data['payment_address_id'];
    }

    public function getPaymentMethods() {
        $method_data = array();
        $this->load->model('checkout/extension');
        $results = $this->model_checkout_extension->getExtensions('payment');
        $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);

        foreach ($results as $result) {
            $this->load->model('payment/' . $result['key']);
            $method = $this->{'model_payment_' . $result['key']}->getMethod($payment_address);
            if ($method) {
                $method_data[$result['key']] = $method;
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
        if (!$this->session->data['shipping_method']) {
            $this->session->data['shipping_method'] = $this->defalutSelectFirstShippingQuote();
        }
        if ($this->session->data['shipping_method']['id']) {
            return $this->session->data['shipping_method']['id'];
        } else if ($this->session->data['shipping_method']['code']) {
            return $this->session->data['shipping_method']['code'];
        }

        return false;
    }

    private function defalutSelectFirstShippingQuote() {
        if ($this->session->data['shipping_methods']) {
            foreach ($this->session->data['shipping_methods'] as $method) {
                foreach ($method['quote'] as $quote) {
                    return $quote;
                }
            }
        }
        return '';
    }

    /**
     * 
     * @return boolean|string
     * @author hujs
     */
    public function getOrderShippingMethods() {

        $currency = $this->currency->getCode();
        if (!$this->session->data['shipping_methods']) {
            $this->session->data['shipping_methods'] = array();
        }
        $availableShippingMethods = array();

        if (!$this->cart->hasShipping()) {
            unset($this->session->data['shipping_address_id']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);

            //      $this->tax->setZone($this->config->get('config_country_id'), $this->config->get('config_zone_id'));  confirm
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
            $this->tax->setZone($shipping_address['country_id'], $shipping_address['zone_id']);
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
     * get shipping methods rely on version
     * @author hujs
     */
    private function setShippingMethods($shipping_address) {
        if (!isset($this->session->data['shipping_methods']) || !$this->config->get('config_shipping_session')) {
            $quote_data = array();
            $this->load->model('checkout/extension');
            $results = $this->model_checkout_extension->getExtensions('shipping');

            foreach ($results as $result) {
                $this->load->model('shipping/' . $result['key']);
                $quote = $this->{'model_shipping_' . $result['key']}->getQuote($shipping_address);
                if ($quote) {
                    $quote_data[$result['key']] = array(
                        'title' => $quote['title'],
                        'quote' => $quote['quote'],
                        'sort_order' => $quote['sort_order'],
                        'error' => $quote['error']
                    );
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

    public function getOrderItems() {
        $shoppingCart = ServiceFactory::factory('ShoppingCart');
        return $shoppingCart->getProducts();
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
        $this->language->load('checkout/payment');
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
