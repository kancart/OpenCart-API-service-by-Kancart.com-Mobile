<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_checkout_action extends UserAuthorizedAction {

    public function execute() {
        $this->session->data['comment'] = trim($_REQUEST['custom_kc_comments']);
        $payment = trim($_REQUEST['payment_method_id']);
        switch ($payment) {
            case 'paypalwpp':
                $this->paypalwpp();
                break;
            case 'paypal':
                $this->paypal();
                break;
            default:
                $this->payorder($payment);
                break;
        }
    }

    public function paypalwpp() { //paypal express checkout
        $this->setError('', 'Error: Paypal Express Checkout is unavailable.');
    }

    public function paypal() { //Website Payments Standard     
        $this->session->data['payment_method'] = $this->session->data['payment_methods']['pp_standard'];

        $this->load->model('account/address');
        if ($this->customer->isLogged() && isset($this->session->data['payment_address_id'])) {
            $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
        } elseif (isset($this->session->data['guest'])) {
            $payment_address = $this->session->data['guest']['payment'];
        }

        if ($payment_address) {
            $total_data = array();
            $total = 0;
            $taxes = $this->cart->getTaxes();
            $sort_order = array();
            if (file_exists(DIR_APPLICATION . 'model/setting/extension.php')) {
                $this->load->model('setting/extension');
                $results = $this->model_setting_extension->getExtensions('total');
            } else {
                $this->load->model('checkout/extension');
                $results = $this->model_checkout_extension->getExtensions('total');
            }
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

            $this->load->model('payment/pp_standard');
            if ($this->model_payment_pp_standard->getMethod($payment_address, $total)) {//The checkout total the order must reach before this payment method becomes active.
                $paypalWpsService = ServiceFactory::factory('PaypalWps');
                list($result, $mesg) = $paypalWpsService->placeOrder();
                if ($result == true) {
                    $paypalParams = $paypalWpsService->buildWpsRedirectParams($_REQUEST['return_url'], $_REQUEST['cancel_url']);
                    $paypalRedirectUrl = $paypalParams['action'];
                    unset($paypalParams['action']);
                    $this->setSuccess(array(
                        'paypal_redirect_url' => $paypalRedirectUrl,
                        'paypal_params' => $paypalParams
                    ));
                    return;
                }
                $errorMsg = join('<br>', $mesg);
                $this->setError('', $errorMsg);
            } else {
                $this->setError('', 'Order amount does not meet the minimum requirement.');
            }
        } else {
            $this->setError('', 'Billing address is empty.');
        }
    }

    public function payorder($method) {
        if (empty($method)) {
            $this->setError('', 'Error: payment_method_id is empty.');
        } elseif (!$this->cart->hasProducts()) {
            $this->setError('', 'Error: ShoppingCart is empty.');
        } else {
            $payment = ServiceFactory::factory('KancartPayment');
            list($result, $order_id, $message) = $payment->placeOrder($method);
            if ($result === true) {
                $orderService = ServiceFactory::factory('Order');
                $info = $orderService->getPaymentOrderInfoById($order_id);
                $this->setSuccess($info);
            } else {
                $this->setError('', is_array($message) ? join('<br>', $message) : $message);
            }
        }
    }

}

?>