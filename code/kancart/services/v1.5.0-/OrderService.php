<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * Order Service, Utility
 * @package services 
 */
class OrderService extends BaseService {

    /** new
     * get user orders information
     * @param type $userId
     * @return type 
     * @author hujs
     */
    public function getOrderInfos(array $parameter) {
        $orderInfos = array();

        $userId = $parameter['customer_id'];
        $pageNo = $parameter['page_no'];
        $pageSize = $parameter['page_size'];

        $orders = $this->getOrderList($pageNo, $pageSize);
        foreach ($orders as $order) {
            $orderItem = array();

            $this->initOrderDetail($orderItem, $order);
            $orderItem['price_infos'] = $this->getPriceInfos($orderItem, $order);
            $orderItem['order_items'] = $this->getOrderItems($order);
            $orderItem['order_status'] = ServiceFactory::factory('Store')->getOrderStatauses();

            $orderInfos[] = $orderItem;
        }

        return array('total_results' => $this->getUserOrderCounts($userId), 'orders' => $orderInfos);
    }

    /**
     * get order detail information
     * @param type $orderId
     * @return type
     * @author hujs
     */
    public function getOneOrderInfoById($orderId) {
        $orderItem = array();

        $order = $this->getOneOrder($orderId);
        if ($order) {
            $this->initOrderDetail($orderItem, $order);
            $orderItem['price_infos'] = $this->getPriceInfos($orderItem, $order);
            $orderItem['order_items'] = $this->getOrderItems($order);
            $orderItem['order_status'] = $this->getOrderHistory($orderId);
            $orderItem['shipping_address'] = $this->getShippingAddress($order);
            $orderItem['billing_address'] = $this->getBillingAddress($order);
            empty($orderItem['shipping_address']['city']) && $orderItem['shipping_address'] = $orderItem['billing_address'];
        }

        return $orderItem;
    }

    public function getPaymentOrderInfoById($orderId, $tx = '') {
        $orderItem = array();

        $order = $this->getPaymentOrder($orderId);
        if ($order) {
            $orderItem['display_id'] = $order['order_id'];
            $orderItem['shipping_address'] = $this->getPaymentAddress($order);
            $orderItem['price_infos'] = $this->getPaymentPriceInfos($order);
            $orderItem['order_items'] = $this->getPaymentOrderItems($order);

            $total = $order['total'] * $order['value'];
            $currency = $order['currency'];
        } else {
            $total = 0;
            $currency = $this->currency->getCode();
        }

        return array(
            'transaction_id' => $tx,
            'payment_total' => $total,
            'currency' => $currency,
            'order_id' => $orderId,
            'orders' => sizeof($orderItem) ? array($orderItem) : false
        );
    }

    public function getPaymentOrder($order_id) {  //from model_account_order->getOrder
        $order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int) $order_id . "' AND customer_id = '" . (int) $this->customer->getId() . "'");

        if ($order_query->num_rows) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int) $order_query->row['payment_country_id'] . "'");

            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int) $order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int) $order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int) $order_query->row['shipping_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            } else {
                $shipping_zone_code = '';
            }

            return array(
                'order_id' => $order_query->row['order_id'],
                'invoice_no' => $order_query->row['invoice_no'],
                'invoice_prefix' => $order_query->row['invoice_prefix'],
                'store_id' => $order_query->row['store_id'],
                'store_name' => $order_query->row['store_name'],
                'store_url' => $order_query->row['store_url'],
                'customer_id' => $order_query->row['customer_id'],
                'firstname' => $order_query->row['firstname'],
                'lastname' => $order_query->row['lastname'],
                'telephone' => $order_query->row['telephone'],
                'fax' => $order_query->row['fax'],
                'email' => $order_query->row['email'],
                'payment_firstname' => $order_query->row['payment_firstname'],
                'payment_lastname' => $order_query->row['payment_lastname'],
                'payment_company' => $order_query->row['payment_company'],
                'payment_address_1' => $order_query->row['payment_address_1'],
                'payment_address_2' => $order_query->row['payment_address_2'],
                'payment_postcode' => $order_query->row['payment_postcode'],
                'payment_city' => $order_query->row['payment_city'],
                'payment_zone_id' => $order_query->row['payment_zone_id'],
                'payment_zone' => $order_query->row['payment_zone'],
                'payment_zone_code' => $payment_zone_code,
                'payment_country_id' => $order_query->row['payment_country_id'],
                'payment_country' => $order_query->row['payment_country'],
                'payment_iso_code_2' => $payment_iso_code_2,
                'payment_iso_code_3' => $payment_iso_code_3,
                'payment_address_format' => $order_query->row['payment_address_format'],
                'payment_method' => $order_query->row['payment_method'],
                'shipping_firstname' => $order_query->row['shipping_firstname'],
                'shipping_lastname' => $order_query->row['shipping_lastname'],
                'shipping_company' => $order_query->row['shipping_company'],
                'shipping_address_1' => $order_query->row['shipping_address_1'],
                'shipping_address_2' => $order_query->row['shipping_address_2'],
                'shipping_postcode' => $order_query->row['shipping_postcode'],
                'shipping_city' => $order_query->row['shipping_city'],
                'shipping_zone_id' => $order_query->row['shipping_zone_id'],
                'shipping_zone' => $order_query->row['shipping_zone'],
                'shipping_zone_code' => $shipping_zone_code,
                'shipping_country_id' => $order_query->row['shipping_country_id'],
                'shipping_country' => $order_query->row['shipping_country'],
                'shipping_iso_code_2' => $shipping_iso_code_2,
                'shipping_iso_code_3' => $shipping_iso_code_3,
                'shipping_address_format' => $order_query->row['shipping_address_format'],
                'shipping_method' => $order_query->row['shipping_method'],
                'comment' => $order_query->row['comment'],
                'total' => $order_query->row['total'],
                'order_status_id' => $order_query->row['order_status_id'],
                'language_id' => $order_query->row['language_id'],
                'currency_id' => $order_query->row['currency_id'],
                'currency' => $order_query->row['currency'],
                'value' => $order_query->row['value'],
                'date_modified' => $order_query->row['date_modified'],
                'date_added' => $order_query->row['date_added'],
                'ip' => $order_query->row['ip']
            );
        } else {
            return false;
        }
    }

    public function getPaymentAddress($address) {
        $addr = array(
            'city' => $address['payment_city'],
            'country_id' => $address['payment_country_id'],
            'zone_id' => $address['payment_zone_id'], //1
            'zone_name' => $address['payment_zone'], //2
            'state' => '', //3
            'address1' => $address['payment_address_1'],
            'address2' => $address['payment_address_2'],
        );

        return $addr;
    }

    public function getPaymentPriceInfos($order) {
        $info = array();

        $this->load->model('account/order');
        $rows = $this->model_account_order->getOrderTotals($order['order_id']);
        foreach ($rows as $row) {
            $title = substr($row['title'], -1) == ':' ? substr($row['title'], 0, -1) : $row['title'];
            if (strpos($title, ' ') === FALSE) {
                $title = strtolower($title);
            } else {
                $title = 'shipping';
            }
            $info[] = array(
                'type' => $row['title'],
                'home_currency_price' => $row['value'] * $order['value']
            );
        }

        return $info;
    }

    public function getPaymentOrderItems($order) {
        $items = array();
        $products = $this->model_account_order->getOrderProducts($order['order_id']);
        foreach ($products as $product) {
            $items[] = array(
                'order_item_key' => $product['product_id'],
                'item_title' => $product['name'],
                'category_name' => '',
                'home_currency_price' => $product['price'] * $order['value'],
                'qty' => $product['quantity']
            );
        }

        return $items;
    }

    /**
     * get one order detail information
     * @param type $orderItem
     * @param type $item
     * @author hujs
     */
    public function initOrderDetail(&$orderItem, $item) {
        $payMethod = array('pm_id' => '',
            'title' => $item['payment_method'],
            'description' => '');

        $orderItem = array('order_id' => $item['order_id'],
            'display_id' => $item['order_id'], //show id
            'uname' => $item['shipping_firstname'] . ' ' . $item['shipping_lastname'],
            'currency' => $item['currency'],
            'shipping_address' => array(),
            'billing_address' => array(),
            'payment_method' => $payMethod,
            'shipping_insurance' => '',
            'coupon' => $item['coupon_id'],
            'order_status' => array(),
            'last_status_id' => $item['order_status_id'], //get current status from history
            'order_tax' => $item['fax'],
            'order_date_start' => $item['date_added'],
            'order_date_finish' => '',
            'order_date_purchased' => $item['date_modified']);
    }

    /**
     * get order ship address
     * @param array $order
     * @return array
     * @author hujs
     */
    private function getShippingAddress(array $order) {

        $address = array('address_book_id' => '',
            'address_type' => 'ship',
            'lastname' => $order['shipping_lastname'],
            'firstname' => $order['shipping_firstname'],
            'telephone' => $order['telephone'],
            'mobile' => '',
            'gender' => '',
            'postcode' => $order['shipping_postcode'],
            'city' => $order['shipping_city'],
            'zone_id' => $order['shipping_zone_id'],
            'zone_code' => $order['shipping_zone_code'],
            'zone_name' => $order['shipping_zone'],
            'state' => '',
            'address1' => $order['shipping_address_1'],
            'address2' => $order['shipping_address_2'],
            'country_id' => $order['shipping_country_id'],
            'country_code' => $order['shipping_iso_code_3'],
            'country_name' => $order['shipping_country'],
            'company' => $order['shipping_company']);

        return $address;
    }

    /**
     * get order bill address
     * @param array $order
     * @return array
     * @author hujs
     */
    private function getBillingAddress(array $order) {

        $address = array('address_book_id' => '',
            'address_type' => 'bill',
            'lastname' => $order['payment_lastname'],
            'firstname' => $order['payment_firstname'],
            'telephone' => $order['telephone'],
            'mobile' => '',
            'gender' => '',
            'postcode' => $order['payment_postcode'],
            'city' => $order['payment_city'],
            'zone_id' => $order['payment_zone_id'],
            'zone_code' => $order['payment_zone_code'],
            'zone_name' => $order['payment_zone'],
            'state' => '',
            'address1' => $order['payment_address_1'],
            'address2' => $order['payment_address_2'],
            'country_id' => $order['payment_country_id'],
            'country_code' => $order['payment_iso_code_3'],
            'country_name' => $order['payment_country'],
            'company' => $order['payment_company']);

        return $address;
    }

    /**
     * get order price information
     * @global type $currencies
     * @param array $order
     * @author hujs
     */
    public function getPriceInfos(&$orderItem, array $order) {
        $info = array();
        $postion = 0;
        $orderCurrency = $order['currency'];

        $method = $order['shipping_method'];
        $shipingMethod = array('pm_id' => '', 'title' => $method, 'description' => '', 'price' => 0);

        $this->load->model('account/order');
        $rows = $this->model_account_order->getOrderTotals($order['order_id']);
        foreach ($rows as $row) {
            $title = substr($row['title'], -1) == ':' ? substr($row['title'], 0, -1) : $row['title'];
            $price = $row['value'] * $order['value'];
            $info[] = array(
                'title' => $title,
                'type' => strtolower($title),
                'price' => $price,
                'currency' => $orderCurrency,
                'position' => $postion++);

            if (ereg($method, $title)) {
                $shipingMethod['price'] = $price;
            }
        }
        $orderItem['shipping_method'] = $shipingMethod;

        return $info;
    }

    /**
     * get order items
     * @param array $order
     * @return array
     * @author hujs
     */
    public function getOrderItems(array $order) {
        $items = array();
        $orderId = $order['order_id'];

        $query = $this->db->query("select o.*, p.image
                                from " . DB_PREFIX . "order_product o
                                LEFT JOIN " . DB_PREFIX . "product p on(o.product_id = p.product_id)
                                where o.order_id = " . (int) $orderId);

        $index = 0;
        $productIds = array(); //create a array productId => index
        foreach ($query->rows as $row) {
            $productId = $row['product_id'];
            $items[] = array('order_item_id' => '',
                'item_id' => $productId,
                'display_id' => $productId,
                'order_item_key' => '',
                'display_attributes' => '',
                'attributes' => '',
                'item_title' => $row['name'],
                'thumbnail_pic_url' => HTTP_IMAGE . $row['image'],
                'qty' => $row['quantity'],
                'price' => $row['price'] * $order['value'],
                'final_price' => $row['price'] * $order['value'],
                'item_tax' => $row['tax'],
                'shipping_method' => '',
                'post_free' => false,
                'virtual_flag' => false);

            $productIds[$productId] = $index++;
        }

        $options = $this->db->query("SELECT  op.*, pr.product_id
                                    FROM  " . DB_PREFIX . "order_option op
                                    LEFT JOIN " . DB_PREFIX . "order_product pr ON (
                                            op.order_product_id = pr.order_product_id)
                                    WHERE op.order_id = " . (int) $orderId);

        foreach ($options->rows as $option) {
            $productId = $option['product_id'];
            $index = $productIds[$productId];
            $items[$index]['display_attributes'].=((empty($items[$index]['display_attributes'])) ? '  - ' : '<br>  - ') .
                    $option['name'] . ' ' . $option['value'];
        }

        return $items;
    }

    /**
     * get order history information by id
     * @param type $orderId
     * @return type
     * @author hujs
     */
    public function getOrderHistory($orderId) {

        $info = array();
        $postion = 0;

        $result = $this->db->query("SELECT os.order_status_id, date_added, os.name AS status, oh.comment, oh.notify 
            FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id 
            WHERE oh.order_id = '" . (int) $orderId . "' AND oh.notify = '1' AND os.language_id = '" . (int) $this->config->get('config_language_id') . "' ORDER BY oh.date_added");

        foreach ($result->rows as $row) {
            $info[] = array('status_id' => $row['order_status_id'],
                'status_name' => $row['status'],
                'display_text' => $row['status'],
                'language_id' => $this->config->get('config_language_id'),
                'date_added' => $row['date_added'],
                'comments' => nl2br($row['comment']),
                'position' => $postion++);
        }

        return $info;
    }

    /**
     * get orders information
     * @param type $userId
     * @return array
     * @author hujs
     */
    public function getOrderList($pageNo, $pageSize) {

        $start = ($pageNo - 1) * $pageSize;
        $userId = $_SESSION['customer_id'];

        $sql = "SELECT * FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) 
                WHERE customer_id = '" . $userId .
                "' AND o.order_status_id > '0' 
                AND os.language_id = '" . (int) $this->config->get('config_language_id') .
                "' ORDER BY o.order_id DESC LIMIT " . (int) $start . "," . (int) $pageSize;

        return $this->db->query($sql)->rows;
    }

    /**
     * get one order information by order id 
     * @param type $orderId
     * @return type
     */
    public function getOneOrder($orderId) {

        $this->load->model('account/order');
        return $this->model_account_order->getOrder($orderId);
    }

    /**
     * get user order count
     * @param type $userId
     * @return int
     * @author hujs
     */
    public function getUserOrderCounts() {

        $this->load->model('account/order');
        $count = $this->model_account_order->getTotalOrders();

        return $count;
    }

}

?>
