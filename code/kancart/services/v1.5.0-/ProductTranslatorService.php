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
    const MAX_SHORT_DES = 800;
    const IMAGE_RATE = 4;

    public function __construct() {
        parent::__construct();
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
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
        $this->item['post_free'] = isset($this->product['shipping']) && !$this->product['shipping'];
        $this->item['item_type'] = 'simple';
        $this->item['item_status'] = $this->product['quantity'] <= 0 ? 'outofstock' : 'instock';

        if ($this->config->get('config_review')) {
            $reviewService = ServiceFactory::factory('Review');
            $this->item['rating_count'] = $reviewService->getReviewsCount($this->product['product_id']);
            $this->item['rating_score'] = $reviewService->getAvgRatingScore($this->product['product_id']);
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
        if ($this->config->get('config_customer_price') && !$this->customer->isLogged()) {
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
            'title' => 'Price',
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
                'price' => $this->format($this->tax->calculate($discount['price'], $this->product['tax_class_id'], $this->config->get('config_tax')))
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
        //according to the full site,discount price preceds to special price
        $discountPrice = $this->model_catalog_product->getProductDiscount($productId);
        if (abs($discountPrice) > 0) {
            return $discountPrice;
        }
        $specialPrice = $this->model_catalog_product->getProductSpecial($productId);
        if (abs($specialPrice) > 0) {
            return $specialPrice;
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
                $price = $option['prefix'] == '-' ? 0 - $option['price'] : $option['price'];
                $options[] = array(
                    'attribute_id' => $attr['product_option_id'],
                    'option_id' => $option['product_option_value_id'],
                    'title' => $option['name'],
                    'price' => $this->format($this->tax->calculate($price, $this->product['tax_class_id'], $this->config->get('config_tax')))
                );
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
        $this->language->load('product/product');
        $features = array();

        if ($this->product['quantity'] <= 0) {
            $stock = $this->product['stock_status'];
        } elseif ($this->config->get('config_stock_display')) {
            $stock = $this->product['quantity'];
        } else {
            $stock = $this->language->get('text_instock');
        }

        $features[] = array(
            'name' => substr($this->language->get('text_availability'), 0, -1),
            'value' => $stock
        );

        $features[] = array(
            'name' => substr($this->language->get('text_model'), 0, -1),
            'value' => $this->product['model']
        );

        if ($this->product['manufacturer']) {
            $features[] = array(
                'name' => substr($this->language->get('text_manufacturer'), 0, -1),
                'value' => $this->product['manufacturer']
            );
        }

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
        $this->item['detail_description'] = preg_replace('/(\<img[^\<^\>]+src\s*=\s*[\"\'])([^:]+\/)/i', '$1' . HTTP_SERVER . '$2', $this->item['detail_description']);
        if (empty($this->item['short_description'])) {
            if (strlen($this->item['detail_description']) > self::MAX_SHORT_DES) {
                $this->item['short_description'] = substr($this->item['detail_description'], 0, self::MAX_SHORT_DES);
                $this->item['short_description'].=' ...';
            } else {
                $this->item['short_description'] = $this->item['detail_description'];
            }
        }

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
