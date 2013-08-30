<?php

class ProductTranslatorService extends BaseService {

    private $product;
    private $item = array();

    const OPTION_TYPE_SELECT = 'select';
    const OPTION_TYPE_CHECKBOX = 'multiselect';
    const OPTION_TYPE_MULTIPLE_SELECT = 'multiselect';
    const OPTION_TYPE_TEXT = 'text';
    const OPTION_TYPE_DATE = 'date';
    const OPTION_TYPE_TIME = 'time';
    const OPTION_TYPE_DATE_TIME = 'datetime';
    const IMAGE_RATE = 4;

    public function __construct() {
        parent::__construct();
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $this->language->load('product/product');
    }

    public function getTranslatedItem() {
        return $this->item;
    }

    public function getItemBaseInfo() {
        $this->item['item_id'] = $this->product['product_id'];
        $this->item['item_title'] = $this->product['name'];
        $this->item['item_url'] = HTTP_SERVER . 'index.php?route=product/product&product_id=' . $this->product['product_id'];
        $this->item['qty'] = $this->product['quantity'];
        $this->item['thumbnail_pic_url'] = empty($this->product['image']) ? HTTP_IMAGE . 'no_image.jpg' : $this->model_tool_image->resize($this->product['image'], $this->config->get('config_image_cart_width') * self::IMAGE_RATE, $this->config->get('config_image_cart_height') * self::IMAGE_RATE);
        $this->item['is_virtual'] = false;
        $this->item['allow_add_to_cart'] = !$this->hasAttributes($this->product['product_id']);
        $this->item['item_type'] = 'simple';
        $this->item['item_status'] = $this->product['quantity'] <= 0 ? 'outofstock' : 'instock';
        $this->item['post_free'] = isset($this->product['shipping']) && !$this->product['shipping'];
        if (!$this->config->has('config_review_status') || $this->config->get('config_review_status')) {
            $reviewService = ServiceFactory::factory('Review');
            $this->item['rating_count'] = $reviewService->getReviewsCount($this->product['product_id']);
            $this->item['rating_score'] = isset($this->product['rating']) ? $this->product['rating'] : $reviewService->getAvgRatingScore($this->product['product_id']);
        }

        return $this->item;
    }

    /**
     * whether the product has attributes
     * @param type $productId
     * @return boolean
     * @author hujs
     */
    public function hasAttributes($productId) {
        if (is_numeric($productId)) {
            $attrQuery = 'select count(*) count from ' . DB_PREFIX . 'product_option where product_id=' . (int) $productId;
            $query = $this->db->query($attrQuery);

            return $query->row['count'] > 0;
        }

        return false;
    }

    /**
     * get item prices
     * @return type
     * @author hujs
     */
    public function getItemPrices() {
        if($this->config->get('config_customer_price') && !$this->customer->isLogged()){
            return array();
        }

        $prices = array();
        $originalPrice = $this->tax->calculate($this->product['price'], $this->product['tax_class_id'], $this->config->get('config_tax'));
        $finalPrice = $this->tax->calculate($this->getFinalPrice($this->product['product_id'], $this->product['price']), $this->product['tax_class_id'], $this->config->get('config_tax'));

        $productPrice = $this->format($finalPrice);
        $prices['currency'] = $this->currency->getCode();
        $prices['base_price'] = array('price' => $productPrice); //not include attribute price
        $prices['tier_prices'] = $this->getTierPrices();    //different qty has diferent price
        $displayPrices = array();
        $displayPrices[] = array(//the final price include attribute price
            'title' => $this->language->get('text_price'),
            'price' => $productPrice,
            'style' => 'normal'
        );

        if ($originalPrice > $finalPrice) {
            $displayPrices[] = array(
                'title' => 'Original Price',
                'price' => $this->format($originalPrice),
                'style' => 'line-through'
            );
            $this->item['discount'] = round(100 - ($finalPrice * 100) / $originalPrice);
        }

        $prices['display_prices'] = $displayPrices;
        $this->item['prices'] = $prices;
        return $prices;
    }

    /**
     * 
     */
    public function getTierPrices() {
        $discounts = $this->model_catalog_product->getProductDiscounts($this->product['product_id']);

        $info = array();
        foreach ($discounts as $discount) {
            $info[] = array(
                'min_qty' => (int) $discount['quantity'],
                'price' => $this->format($this->tax->calculate($discount['price'], $this->product['tax_class_id']), $this->config->get('config_tax'))
            );
        }

        return $info;
    }

    /**
     * get one product final price
     * @param type $productId
     * @return int
     * @author hujs
     */
    public function getFinalPrice($productId, $originalPrice) {
        // Product Specials
        if ((float) $this->product['special']) {
            return $this->product['special'];
        } else {
            if ($this->customer->isLogged()) {
                $customer_group_id = $this->customer->getCustomerGroupId();
            } else {
                $customer_group_id = $this->config->get('config_customer_group_id');
            }
            $specialQuery = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $productId . "' AND customer_group_id = '" . (int) $customer_group_id . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

            if ($specialQuery->num_rows) {
                return $specialQuery->row['price'];
            }

            // Product discounts
            $discountQuery = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $productId . "' AND customer_group_id = '" . (int) $customer_group_id . "' AND quantity <= '" . (int) $this->product['quantity'] . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");

            if ($discountQuery->num_rows) {
                return $discountQuery->row['price'];
            }
        }

        return $originalPrice;
    }

    /**
     * get item option
     * @return type
     * @author hujs
     */
    public function getItemAttributes() {

        $productId = $this->product['product_id'];
        $rows = $this->model_catalog_product->getProductOptions($productId);

        foreach ($rows as $row) {
            $attributeCollection[] = $row;
        }
        $this->item['attributes'] = $this->extractAttributes($attributeCollection);
        return $this->item['attributes'];
    }

    /**
     * get product detail information
     */
    private function extractAttributes($attributeCollection) {
        $attributes = array();

        $map = array(
            'select' => self::OPTION_TYPE_SELECT,
            'radio' => self::OPTION_TYPE_SELECT,
            'checkbox' => self::OPTION_TYPE_CHECKBOX,
            'text' => self::OPTION_TYPE_TEXT,
            'file' => self::OPTION_TYPE_TEXT,
            'date' => self::OPTION_TYPE_DATE,
            'time' => self::OPTION_TYPE_TIME
        );

        foreach ($attributeCollection as $attr) {
            $options = array();
            foreach ($attr['option_value'] as $option) {
                if (!$option['subtract'] || ($option['quantity'] > 0)) {
                    $price = $option['prefix'] == '-' ? 0 - $option['price'] : $option['price'];
                    $options[] = array(
                        'attribute_id' => $attr['product_option_id'],
                        'option_id' => $option['product_option_value_id'],
                        'title' => $option['name'],
                        'price' => $this->format($this->tax->calculate($price, $this->product['tax_class_id'], $this->config->get('config_tax')))
                    );
                }
            }

            $attributes[] = array(
                'attribute_id' => $attr['product_option_id'],
                'title' => $attr['name'],
                'required' => $attr['required'] == 1,
                'input' => isset($attr['type']) ? (isset($map[$attr['type']]) ? $map[$attr['type']] : self::OPTION_TYPE_TEXT) : self::OPTION_TYPE_SELECT,
                'options' => $options
            );
        }
        return $attributes;
    }

    public function getRecommededItems() {
        $this->item['recommended_items'] = array();
    }

    public function getRelatedItems() {

        $relatedItems = array();

        $rows = $this->model_catalog_product->getProductRelated($this->product['product_id']);

        $proudctTranslator = ServiceFactory::factory('ProductTranslator', false);
        foreach ($rows as $row) {
            $proudctTranslator->clear();
            $proudctTranslator->setProduct($row);
            $proudctTranslator->getItemBaseInfo();
            $proudctTranslator->getItemPrices();
            $relatedItems[] = $proudctTranslator->getTranslatedItem();
        }

        $this->item['related_items'] = $relatedItems;
    }

    public function getProductFeature() {
        $features = array();

        if ($this->product['manufacturer']) {
            $features[] = array(
                'name' => rtrim($this->language->get('text_manufacturer'), ':：'),
                'value' => $this->product['manufacturer']
            );
        }

        $features[] = array(
            'name' => rtrim($this->language->get('text_model'), ':：'),
            'value' => $this->product['model']
        );

        $features[] = array(
            'name' => rtrim($this->language->get('text_reward'), ':：'),
            'value' => $this->product['reward']
        );

        if ($this->product['quantity'] <= 0) {
            $stock = $this->product['stock_status'];
        } elseif ($this->config->get('config_stock_display')) {
            $stock = $this->product['quantity'];
        } else {
            $stock = $this->language->get('text_instock');
        }

        $features[] = array(
            'name' => rtrim($this->language->get('text_stock'), ':：'),
            'value' => $stock
        );


        return $features;
    }

    /**
     * get item images
     * @return int
     * @author hujs
     */
    public function getItemImgs() {

        $this->item['short_description'] = $this->product['meta_description'];
        $this->item['detail_description'] = htmlspecialchars_decode($this->product['description'], ENT_COMPAT);
        $this->item['detail_description'] = preg_replace('/(\<img[^\<^\>]+src\s*=\s*[\"\'])([^(http)]+\/)/i', '$1' . HTTP_SERVER . '$2', $this->item['detail_description']);
        $this->item['specifications'] = $this->getProductFeature();

        $imgs = array();

        $productId = intval($this->product['product_id']);
        $rows = $this->model_catalog_product->getProductImages($productId);

        $pos = 0;
        foreach ($rows as $row) {
            $imgs[] = array(
                'id' => $row['product_image_id'],
                'img_url' => HTTP_IMAGE . $row['image'],
                'position' => $pos++
            );
        }

        if (!$imgs) {
            $imgs[] = array(
                'id' => '1',
                'img_url' => HTTP_IMAGE . $this->product['image'],
                'position' => $pos
            );
        }
        $this->item['item_imgs'] = $imgs;
        return $imgs;
    }

    public function clear() {
        $this->product = array();
        $this->item = array();
    }

    public function setProduct($product) {
        $this->product = $product;
    }

    public function getFullItemInfo() {
        $this->getItemBaseInfo();
        $this->getItemAttributes();
        $this->getItemPrices();
        $this->getItemImgs();
        $this->getRecommededItems();
        $this->getRelatedItems();
        return $this->getTranslatedItem();
    }

}

?>
