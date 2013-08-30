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
        $productService = ServiceFactory::factory('Product');
        $products = $this->cart->getProducts();
        foreach ($products as $product) {
            $productInfo = $productService->getProduct($product['product_id']);
            $item = array(
                'cart_item_id' => $product['key'],
                'cart_item_key' => '',
                'item_id' => $product['product_id'],
                'item_title' => $productInfo['item_title'] . (!$product['stock'] ? '<font color = \'red\'>***' : ''),
                'thumbnail_pic_url' => $productInfo['thumbnail_pic_url'],
                'currency' => $currency,
                'item_price' => $this->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
                'qty' => $product['quantity'],
                'display_attributes' => $this->getDisplayAttributes($product['option']),
                'item_url' => $productInfo['item_url'],
                'short_description' => $productInfo['short_description'],
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
        $sort_order = array();
        $this->load->model('checkout/extension');
        $results = $this->model_checkout_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get($value['key'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);
        $totalIdx = -1;
        foreach ($results as $result) {
            $this->load->model('total/' . $result['key']);
            $this->{'model_total_' . $result['key']}->getTotal($total_data, $total, $taxes);
            if ($result['key'] !== 'total') {
                ++$totalIdx;
            }
        }

        $sort_order = array();

        foreach ($total_data as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }
        array_multisort($sort_order, SORT_ASC, $total_data);

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

}

?>
