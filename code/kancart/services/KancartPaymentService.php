<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class KancartPaymentService extends BaseService {

    public function placeOrder($method) {
        $this->session->data['payment_method'] = array(
            'id' => 'mobile',
            'title' => $method,
            'sort_order' => 1
        );
        $paypal = ServiceFactory::factory('PaypalWps');
        list($result, $mesg) = $paypal->placeOrder();
        $order_id = $this->session->data['order_id'];
        $this->load->model('checkout/order');
        if ($result === true) {
            $comments = 'From mobile payment ' . $method;
            $this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('config_order_status_id'), $comments);
            $paypal->paypalWpsDone();
            return array(true, $order_id, array());
        } else {
            $message = is_array($mesg) ? join('<br>', $mesg) : $mesg;
            return array(false, $order_id, $message);
        }
    }

    public function kancartPaymentDone($order_id, $custom_kc_comments, $payment_status) {
        $pending = $this->config->get('config_order_status_id');

        if ($this->config->has('pp_standard_processed_status_id')) {
            $processing = $this->config->get('pp_standard_processed_status_id');
        } else {
            $query = $this->db->query('SELECT order_status_id FROM op_order_status WHERE LOWER(`name`) = \'processing\' LIMIT 1');
            $processing = $query->num_rows > 0 ? $query->row['order_status_id'] : 2;
        }

        $order_status_id = (strtolower($payment_status) == 'succeed') ? $processing : $pending;
        $this->load->model('checkout/order');
        $this->model_checkout_order->update($order_id, $order_status_id, $custom_kc_comments, 1);
        return array(TRUE, $order_id);
    }

}

?>
