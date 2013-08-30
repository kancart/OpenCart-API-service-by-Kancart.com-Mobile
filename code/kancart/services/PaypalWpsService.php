<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * Paypal Web Payment Standard , Utility
 * @package services 
 */
class PaypalWpsService extends BaseService {

    public function paypalWpsDone() {
        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
        }
    }

    /**
     * create a order
     * Apply to opencart 1.5.0
     * @return type
     * @author hujs
     */
    public function placeOrder() {
        $errors = array();
        if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $errors[] = $this->language->get('error_stock');
        }

        if ($this->cart->hasShipping()) {
            if (!isset($this->session->data['shipping_address_id']) || !$this->session->data['shipping_address_id']) {
                $errors[] = 'Please select shipping address.';
            }
        } else {
            unset($this->session->data['shipping_address_id']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        if ($errors) {
            return array(FALSE, $errors);
        }

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

        $sort_order = array();

        foreach ($total_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $total_data);

        $this->language->load('checkout/checkout');

        $data = array();
        $data['store_id'] = $this->config->get('config_store_id');
        $data['store_name'] = $this->config->get('config_name');
        if ($data['store_id']) {
            $data['store_url'] = $this->config->get('config_url');
        } else {
            $data['store_url'] = HTTP_SERVER;
        }

        $data['customer_id'] = $this->customer->getId();
        $data['customer_group_id'] = $this->customer->getCustomerGroupId();
        $data['firstname'] = $this->customer->getFirstName();
        $data['lastname'] = $this->customer->getLastName();
        $data['email'] = $this->customer->getEmail();
        $data['telephone'] = $this->customer->getTelephone();
        $data['fax'] = $this->customer->getFax();
        $this->load->model('account/address');

        $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);

        $data['payment_firstname'] = $payment_address['firstname'];
        $data['payment_lastname'] = $payment_address['lastname'];
        $data['payment_company'] = $payment_address['company'];
        $data['payment_address_1'] = $payment_address['address_1'];
        $data['payment_address_2'] = $payment_address['address_2'];
        $data['payment_city'] = $payment_address['city'];
        $data['payment_postcode'] = $payment_address['postcode'];
        $data['payment_zone'] = $payment_address['zone'];
        $data['payment_zone_id'] = $payment_address['zone_id'];
        $data['payment_country'] = $payment_address['country'];
        $data['payment_country_id'] = $payment_address['country_id'];
        $data['payment_address_format'] = $payment_address['address_format'];

        if (isset($this->session->data['payment_method']['title'])) {
            $data['payment_method'] = $this->session->data['payment_method']['title'];
        } else {
            $data['payment_method'] = '';
        }

        if ($this->cart->hasShipping()) {
            $shipping_address_id = $this->session->data['shipping_address_id'];

            $shipping_address = $this->model_account_address->getAddress($shipping_address_id);

            $data['shipping_firstname'] = $shipping_address['firstname'];
            $data['shipping_lastname'] = $shipping_address['lastname'];
            $data['shipping_company'] = $shipping_address['company'];
            $data['shipping_address_1'] = $shipping_address['address_1'];
            $data['shipping_address_2'] = $shipping_address['address_2'];
            $data['shipping_city'] = $shipping_address['city'];
            $data['shipping_postcode'] = $shipping_address['postcode'];
            $data['shipping_zone'] = $shipping_address['zone'];
            $data['shipping_zone_id'] = $shipping_address['zone_id'];
            $data['shipping_country'] = $shipping_address['country'];
            $data['shipping_country_id'] = $shipping_address['country_id'];
            $data['shipping_address_format'] = $shipping_address['address_format'];

            if (isset($this->session->data['shipping_method']['title'])) {
                $data['shipping_method'] = $this->session->data['shipping_method']['title'];
            } else {
                $data['shipping_method'] = '';
            }
        } else {
            $data['shipping_firstname'] = '';
            $data['shipping_lastname'] = '';
            $data['shipping_company'] = '';
            $data['shipping_address_1'] = '';
            $data['shipping_address_2'] = '';
            $data['shipping_city'] = '';
            $data['shipping_postcode'] = '';
            $data['shipping_zone'] = '';
            $data['shipping_zone_id'] = '';
            $data['shipping_country'] = '';
            $data['shipping_country_id'] = '';
            $data['shipping_address_format'] = '';
            $data['shipping_method'] = '';
        }

        if ($this->cart->hasShipping()) {
            $this->setZone($shipping_address['country_id'], $shipping_address['zone_id']);
        } else {
            $this->setZone($payment_address['country_id'], $payment_address['zone_id']);
        }

        $product_data = array();

        foreach ($this->cart->getProducts() as $product) {
            $option_data = array();

            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id' => $option['option_id'],
                    'option_value_id' => $option['option_value_id'],
                    'name' => $option['name'],
                    'value' => $option['option_value'],
                    'type' => $option['type']
                );
            }

            $product_data[] = array(
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'download' => $product['download'],
                'quantity' => $product['quantity'],
                'subtract' => $product['subtract'],
                'price' => $product['price'],
                'total' => $product['total'],
                'tax' => $this->getTax($product['total'], $product['tax_class_id'])
            );
        }

        // Gift Voucher
        if (isset($this->session->data['vouchers']) && $this->session->data['vouchers']) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $product_data[] = array(
                    'product_id' => 0,
                    'name' => $voucher['description'],
                    'model' => '',
                    'option' => array(),
                    'download' => array(),
                    'quantity' => 1,
                    'subtract' => false,
                    'price' => $voucher['amount'],
                    'total' => $voucher['amount'],
                    'tax' => 0
                );
            }
        }

        $data['products'] = $product_data;
        $data['totals'] = $total_data;
        $data['comment'] = isset($_REQUEST['custom_kc_comments']) ? $_REQUEST['custom_kc_comments'] : 'From mobile';
        $data['total'] = $total;
        method_exists($this->cart, 'getTotalRewardPoints') && $data['reward'] = $this->cart->getTotalRewardPoints();

        if (isset($this->request->cookie['tracking'])) {
            $this->load->model('affiliate/affiliate');
            $affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);
            if ($affiliate_info) {
                $data['affiliate_id'] = $affiliate_info['affiliate_id'];
                $data['commission'] = ($total / 100) * $affiliate_info['commission'];
            } else {
                $data['affiliate_id'] = 0;
                $data['commission'] = 0;
            }
        } else {
            $data['affiliate_id'] = 0;
            $data['commission'] = 0;
        }

        $data['language_id'] = $this->config->get('config_language_id');
        $data['currency_id'] = $this->currency->getId();
        $data['currency_code'] = $this->currency->getCode();
        $data['currency_value'] = $this->currency->getValue($this->currency->getCode());
        $data['ip'] = $this->request->server['REMOTE_ADDR'];

        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
        } else {
            $data['forwarded_ip'] = '';
        }

        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            $data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
        } else {
            $data['user_agent'] = '';
        }

        if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
            $data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $data['accept_language'] = '';
        }

        $this->load->model('checkout/order');

        if (method_exists($this->model_checkout_order, 'create')) {
            $this->session->data['order_id'] = $this->model_checkout_order->create($data);
        } else {
            $this->session->data['order_id'] = $this->model_checkout_order->addOrder($data);
        }

        // Gift Voucher
        if (isset($this->session->data['vouchers']) && is_array($this->session->data['vouchers'])) {
            $this->load->model('checkout/voucher');

            foreach ($this->session->data['vouchers'] as $voucher) {
                $this->model_checkout_voucher->addVoucher($this->session->data['order_id'], $voucher);
            }
        }

        return array(TRUE, NULL);
    }

    /**
     * Apply to opencart 1.5.0
     * @return type
     * @author hujs
     */
    public function buildWpsRedirectParams($returnUrl = '', $cancelUrl = '') {
        $params = array();
        if (!$this->config->get('pp_standard_test')) {
            $params['action'] = 'https://www.paypal.com/cgi-bin/webscr';
        } else {
            $params['action'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        }

        $this->language->load('payment/pp_standard');
        $this->load->model('checkout/order');
        $this->order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // Check for supported currency, otherwise convert to USD.
        $currencies = array('AUD', 'CAD', 'EUR', 'GBP', 'JPY', 'USD', 'NZD', 'CHF', 'HKD', 'SGD', 'SEK', 'DKK', 'PLN', 'NOK', 'HUF', 'CZK', 'ILS', 'MXN');
        if (in_array($this->order_info['currency_code'], $currencies)) {
            $currency = $this->order_info['currency_code'];
        } else {
            $currency = 'USD';
        }

        $params['cmd'] = '_cart';
        $params['upload'] = '1';

        $product_total = 0;
        $i = 1;
        foreach ($this->cart->getProducts() as $product) {
            $price = $this->currency->format($product['price'], $currency, FALSE, FALSE);
            $params['item_number_' . $i . ''] = $product['model'];
            $params['item_name_' . $i . ''] = $product['name'];
            $params['amount_' . $i . ''] = $price;
            $params['quantity_' . $i . ''] = $product['quantity'];
            $params['weight_' . $i . ''] = $product['weight'];
            $product_total += ($price * $product['quantity']);
            if (!empty($product['option'])) {
                $x = 0;
                foreach ($product['option'] as $res) {
                    $params['on' . $x . '_' . $i . ''] = $res['name'];
                    $params['os' . $x . '_' . $i . ''] = $res['value'];
                    $x++;
                }
            }
            $i++;
        }

        //shipping total
        $params['item_number_' . $i . ''] = '';
        $params['item_name_' . $i . ''] = $this->language->get('text_total');
        $params['amount_' . $i . ''] = $this->currency->format($this->order_info['total'] - $this->cart->getSubTotal(), $currency, false, false);
        $params['quantity_' . $i . ''] = 1;
        $params['weight_' . $i . ''] = 0;

        $params['business'] = $this->config->get('pp_standard_email');
        $params['currency_code'] = $currency;
        $params['first_name'] = html_entity_decode($this->order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');
        $params['last_name'] = html_entity_decode($this->order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
        $params['address1'] = html_entity_decode($this->order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');
        $params['address2'] = html_entity_decode($this->order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');
        $params['city'] = html_entity_decode($this->order_info['payment_city'], ENT_QUOTES, 'UTF-8');
        $params['zip'] = html_entity_decode($this->order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');
        $params['country'] = $this->order_info['payment_iso_code_2'];
        $params['email'] = $this->order_info['email'];
        $params['invoice'] = $this->session->data['order_id'] . ' - ' . html_entity_decode($this->order_info['payment_firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($this->order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
        $params['lc'] = $this->session->data['language'];
        $params['custom'] = $this->session->data['order_id'];
        $params['rm'] = '2';
        $params['charset'] = 'utf-8';
        $params['no_note'] = '1';

        if (!$this->config->get('pp_standard_transaction')) {
            $params['paymentaction'] = 'authorization';
        } else {
            $params['paymentaction'] = 'sale';
        }

        $this->url = new Url($this->config->get('config_url'), $this->config->get('config_ssl'));
        $params['return'] = $returnUrl;
        $params['cancel_return'] = $cancelUrl;
        $params['notify_url'] = $this->url->link('payment/pp_standard/callback');

        $this->load->library('encryption');
        $encryption = new Encryption($this->config->get('config_encryption'));
        $params['custom'] = $encryption->encrypt($this->session->data['order_id']);
        return $params;
    }

}

?>
