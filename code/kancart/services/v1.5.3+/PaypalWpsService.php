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
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
        }
    }

    /**
     * create a order
     * Apply to opencart 1.5.5
     * @return type
     * @author hujs
     */
    public function placeOrder() {
        $errors = array();
        if ($this->cart->hasShipping()) {
            // Validate if shipping address has been set.		
            $this->load->model('account/address');

            if ($this->customer->isLogged() && isset($this->session->data['shipping_address_id'])) {
                $shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
            } elseif (isset($this->session->data['guest'])) {
                $shipping_address = $this->session->data['guest']['shipping'];
            }

            if (empty($shipping_address)) {
                $errors[] = 'Shipping address is empty.';
            }

            // Validate if shipping method has been set.	
            if (!isset($this->session->data['shipping_method'])) {
                $errors[] = 'Shipping method is empty.';
            }
        } else {
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Validate if payment address has been set.
        $this->load->model('account/address');

        if ($this->customer->isLogged() && isset($this->session->data['payment_address_id'])) {
            $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
        } elseif (isset($this->session->data['guest'])) {
            $payment_address = $this->session->data['guest']['payment'];
        }

        if (empty($payment_address)) {
            $errors[] = 'Billing address is empty.';
        }

        // Validate if payment method has been set.	
        if (!isset($this->session->data['payment_method'])) {
            $errors[] = 'Payment method is empty.';
        }

        // Validate cart has products and has stock.	
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $errors[] = 'Shopping cart is empty, or some product out of stock.';
        }

        // Validate minimum quantity requirments.			
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $errors[] = "the quantity of product {$product['name']} less than the product minimum.";
                break;
            }
        }

        if (sizeof($errors) < 1) {
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

            $data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
            $data['store_id'] = $this->config->get('config_store_id');
            $data['store_name'] = $this->config->get('config_name');

            if ($data['store_id']) {
                $data['store_url'] = $this->config->get('config_url');
            } else {
                $data['store_url'] = HTTP_SERVER;
            }

            if ($this->customer->isLogged()) {
                $data['customer_id'] = $this->customer->getId();
                $data['customer_group_id'] = $this->customer->getCustomerGroupId();
                $data['firstname'] = $this->customer->getFirstName();
                $data['lastname'] = $this->customer->getLastName();
                $data['email'] = $this->customer->getEmail();
                $data['telephone'] = $this->customer->getTelephone();
                $data['fax'] = $this->customer->getFax();

                $this->load->model('account/address');

                $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
            } elseif (isset($this->session->data['guest'])) {
                $data['customer_id'] = 0;
                $data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
                $data['firstname'] = $this->session->data['guest']['firstname'];
                $data['lastname'] = $this->session->data['guest']['lastname'];
                $data['email'] = $this->session->data['guest']['email'];
                $data['telephone'] = $this->session->data['guest']['telephone'];
                $data['fax'] = $this->session->data['guest']['fax'];

                $payment_address = $this->session->data['guest']['payment'];
            }

            $data['payment_firstname'] = $payment_address['firstname'];
            $data['payment_lastname'] = $payment_address['lastname'];
            $data['payment_company'] = $payment_address['company'];
            $data['payment_company_id'] = $payment_address['company_id'];
            $data['payment_tax_id'] = $payment_address['tax_id'];
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

            if (isset($this->session->data['payment_method']['code'])) {
                $data['payment_code'] = $this->session->data['payment_method']['code'];
            } else {
                $data['payment_code'] = '';
            }

            if ($this->cart->hasShipping()) {
                if ($this->customer->isLogged()) {
                    $this->load->model('account/address');

                    $shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
                } elseif (isset($this->session->data['guest'])) {
                    $shipping_address = $this->session->data['guest']['shipping'];
                }

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

                if (isset($this->session->data['shipping_method']['code'])) {
                    $data['shipping_code'] = $this->session->data['shipping_method']['code'];
                } else {
                    $data['shipping_code'] = '';
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
                $data['shipping_code'] = '';
            }

            $product_data = array();

            foreach ($this->cart->getProducts() as $product) {
                $option_data = array();

                foreach ($product['option'] as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['option_value'];
                    } else {
                        $value = $this->encryption->decrypt($option['option_value']);
                    }

                    $option_data[] = array(
                        'product_option_id' => $option['product_option_id'],
                        'product_option_value_id' => $option['product_option_value_id'],
                        'option_id' => $option['option_id'],
                        'option_value_id' => $option['option_value_id'],
                        'name' => $option['name'],
                        'value' => $value,
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
                    'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
                    'reward' => $product['reward']
                );
            }

            // Gift Voucher
            $voucher_data = array();

            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $voucher) {
                    $voucher_data[] = array(
                        'description' => $voucher['description'],
                        'code' => substr(md5(mt_rand()), 0, 10),
                        'to_name' => $voucher['to_name'],
                        'to_email' => $voucher['to_email'],
                        'from_name' => $voucher['from_name'],
                        'from_email' => $voucher['from_email'],
                        'voucher_theme_id' => $voucher['voucher_theme_id'],
                        'message' => $voucher['message'],
                        'amount' => $voucher['amount']
                    );
                }
            }

            $data['products'] = $product_data;
            $data['vouchers'] = $voucher_data;
            $data['totals'] = $total_data;
            $data['comment'] = $this->session->data['comment'];
            $data['total'] = $total;

            if (isset($this->request->cookie['tracking'])) {
                $this->load->model('affiliate/affiliate');

                $affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);
                $subtotal = $this->cart->getSubTotal();

                if ($affiliate_info) {
                    $data['affiliate_id'] = $affiliate_info['affiliate_id'];
                    $data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
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

            $this->session->data['order_id'] = $this->model_checkout_order->addOrder($data);

            return array(TRUE, NULL);
        } else {
            return array(FALSE, $errors);
        }
    }

    /**
     * Apply to opencart 1.5.5
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
        $currency = $this->order_info['currency_code'];
     
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
        $params['notify_url'] = $this->url->link('payment/pp_standard/callback', '', 'SSL');
        $params['custom'] = $this->session->data['order_id'];
        return $params;
    }

}

?>
