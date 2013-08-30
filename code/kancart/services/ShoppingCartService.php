<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * ShoppingCart Service,Utility
 * @package services 
 * @author hujs
 */
class ShoppingCartService extends BaseService {

    private $hasStockError = false;

    public function __construct() {
        parent::__construct();
        $this->language->load('checkout/cart');
    }

    /**
     * Get Cart detailed information
     * @author hujs
     */
    public function get() {
        $result = array();
        $this->initShoppingCartGetReslut($result);
        $this->hasStockError = !$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'));
        $result['cart_items'] = $this->getProducts();
        $result['price_infos'] = $this->getPriceInfo();
        $result['cart_items_count'] = $this->cart->countProducts();
        $result['payment_methods'] = $this->getPaymentMethods();
        $result['messages'] = !$this->hasStockError ? array() : array($this->language->get('error_stock'));
        $result['valid_to_checkout'] = !$this->hasStockError;
        return $result;
    }

    public function getPaymentMethods() {
        return array();
    }

    /**
     * get products information
     * @param type $cart
     * @return array
     * @author hujs
     */
    public function getProducts() {
        $currency = $this->currency->getCode();
        $items = array();
        $products = $this->cart->getProducts();
        $priceStatus = ($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price');
        $this->load->model('tool/image');
        foreach ($products as $product) {
            if ($priceStatus) {
                $price = $this->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')));
            } else {
                $price = false;
            }

            $stock = $product['stock'] ? true : !(!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'));
            $item = array(
                'cart_item_id' => $product['key'],
                'cart_item_key' => '',
                'out_of_stock' => !$stock,
                'item_id' => $product['product_id'],
                'item_title' => $product['name'],
                'thumbnail_pic_url' => empty($product['image']) ? HTTP_IMAGE . 'no_image.jpg' : $this->model_tool_image->resize($product['image'], $this->config->get('config_image_cart_width') * 4, $this->config->get('config_image_cart_height') * 4),
                'currency' => $currency,
                'item_price' => $price,
                'qty' => $product['quantity'],
                'display_attributes' => $this->getDisplayAttributes($product['option']),
                'short_description' => '',
            );
            $items[] = $item;
        }
        return $items;
    }

    private function getDisplayAttributes($productOption) {
        $displayAttributes = array();
        foreach ($productOption as $option) {
            $displayAttributes[] = $option['name'] . ': ' . (isset($option['value']) ? $option['value'] : $option['option_value']);
        }
        return sizeof($displayAttributes) ? ' - ' . join('<br/> - ', $displayAttributes) : '';
    }

    /**
     * initialization of cart information
     * @param type $result
     * @author hujs
     */
    public function initShoppingCartGetReslut(&$result) {
        $result['cart_items_count'] = 0;
        $result['cart_items'] = array();
        $result['messages'] = array();
        $result['price_infos'] = array();
        $result['payment_methods'] = array();
        $result['valid_to_checkout'] = true;
        $result['is_virtual'] = false;
    }

    /**
     * get price information
     * @return int
     * @author hujs
     */
    public function getPriceInfo() {
        $currency = $this->currency->getCode();
        $total_data = array();
        $total = 0;
        $taxes = $this->cart->getTaxes();

        if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
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
        }

        $priceInfo = array();
        $position = 0;
        $totalSortOrder = $this->config->get('total_sort_order');
        foreach ($total_data as $total) {
            if (isset($total['value']) && abs($total['value']) > 0) {
                $priceInfo[] = array(
                    'title' => str_replace(':', '', $total['title']),
                    'currency' => $currency,
                    'type' => $totalSortOrder == $total['sort_order'] ? 'total' : '',
                    'price' => $this->format($total['value']),
                    'position' => $position++
                );
            }
        }
        return $priceInfo;
    }

    /**
     * add goods into cart
     * @param type $goods
     * @return type 
     * @author hujs
     */
    public function add($productId, $quantity = 1, $option = array()) {
        $this->cart->add($productId, $quantity, $option);
        $this->clearShippingAndPayment();
    }

    /**
     * update product's quantity and attributes
     * in cart by product id
     * @param type $arr
     * @return type 
     * @author hujs
     */
    public function update($cartItemId, $quantity) {
        $this->cart->update($cartItemId, $quantity);
        $this->clearShippingAndPayment();
    }

    private function clearShippingAndPayment() {
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['shipping_method']);
        unset($this->session->data['payment_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['reward']);
    }

    /**
     * remove goods from cart by product key
     * @access  public
     * @param   integer $id
     * @return  void
     * @author hujs
     */
    public function remove($cartItemId) {
        $this->cart->remove($cartItemId);
        $this->clearShippingAndPayment();
    }

    /**
     * check Minimum order amount is required
     * Apply to opencart 1.5.0
     * @param type $productId
     * @param type $quantity
     * @return type
     * @author hujs
     */
    public function checkMinimunOrder($productId, $quantity) {
        $this->load->model('catalog/product');

        $error = array();
        $product_info = $this->model_catalog_product->getProduct($productId);
        $product_total = 0;

        foreach ($this->session->data['cart'] as $key => $value) {
            $product = explode(':', $key);
            if ($product[0] == $productId) {
                $product_total += $value;
            }
        }
        if ($product_info['minimum'] > ($product_total + $quantity)) {
            $error[] = sprintf($this->language->get('error_minimum'), $product_info['name'], $product_info['minimum']);
        }

        return $error;
    }

}

?>
