<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class ProductService extends BaseService {

    private function setDefaultFilterIfNeed(&$filter) {
        if (!$filter['page_size']) {
            $filter['page_size'] = 10;
        }
        if (!$filter['page_no']) {
            $filter['page_no'] = 1;
        }
        if (!$filter['sort_by']) {
            $filter['sort_by'] = 'p.product_id';
        }
        if (!$filter['sort_order']) {
            $filter['sort_order'] = 'desc';
        }
    }

    /**
     * Get the products,filter is specified by the $filter parameter
     * 
     * @param array $filter array
     * @return array
     * @author hujs
     */
    public function getProducts($filter) {
        $this->setDefaultFilterIfNeed($filter);
        $products = array('total_results' => 0, 'items' => array());
        if (isset($filter['item_ids'])) {
            // get by item ids
            $products = $this->getSpecifiedProducts($filter);
        } else if ($filter['is_specials']) {
            // get specials products
            $products = $this->getSpecialProducts($filter);
        } else if ($filter['query']) {
            // get by query
            $products = $this->getProductsByQuery($filter);
        } else {
            // get by category
            $products = $this->getProductsByCategory($filter, $filter['cid']);
        }
        $returnResult = array();
        $returnResult['total_results'] = $products['total_results'];
        $returnResult['items'] = $products['items'];
        return $returnResult;
    }

    /**
     * get products by category,the category id is specified in the $filter array
     * @param type $filter
     * @author hujs
     */
    public function getProductsByCategory($filter) {
        $categoryId = $filter['cid'] == -1 ? 0 : $filter['cid'];
        $start = max($filter['page_no'] - 1, 0) * $filter['page_size'];
        $this->load->model('catalog/product');

        if ($categoryId) {
            $total = $this->model_catalog_product->getTotalProductsByCategoryId($categoryId);
            $results = $this->model_catalog_product->getProductsByCategoryId($categoryId, $filter['sort_by'], $filter['sort_order'], $start, $filter['page_size']);
        } else {
            $query = $this->db->query("SELECT COUNT(distinct p.product_id) AS total FROM " . DB_PREFIX . "product_to_category p2c LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
            $total = $query->row['total'];
            $results = $this->model_catalog_product->getProducts();
        }

        $proudctTranslator = ServiceFactory::factory('ProductTranslator');
        $products = array();
        $products['items'] = array();
        foreach ($results as $row) {
            $proudctTranslator->clear();
            $proudctTranslator->setProduct($row);
            $proudctTranslator->getItemBaseInfo();
            $proudctTranslator->getItemPrices();
            $products['items'][] = $proudctTranslator->getTranslatedItem();
        }
        $products['total_results'] = $total;

        return $products;
    }

    /**
     * get product by name
     * @param type $filter
     * @return int
     * @author hujs
     */
    public function getProductsByQuery($filter) {

        if (empty($filter['query']))
            return array();

        $count = 0;
        $totalProducts = $this->getSearchTotalProducts($filter, $count);
        $proudctTranslator = ServiceFactory::factory('ProductTranslator');

        foreach ($totalProducts as $row) {
            $proudctTranslator->clear();
            $proudctTranslator->setProduct($row);
            $proudctTranslator->getItemBaseInfo();
            $proudctTranslator->getItemPrices();
            $products['items'][] = $proudctTranslator->getTranslatedItem();
        }
        $products ['total_results'] = $count;

        return $products;
    }

    private function getSearchTotalProducts($filter, &$count) {
        $keyword = $this->request->clean($filter['query']);
        $start = max($filter['page_no'] - 1, 0) * $filter['page_size'];

        $cid = (isset($filter['cid']) && is_numeric($filter['cid'])) ? (int) $filter['cid'] : 0;
        $categoryId = $cid < 0 ? 0 : $cid;
        $description = isset($filter['description']);
        $model = isset($filter['model']);

        $this->load->model('catalog/product');

        $productTotal = $this->model_catalog_product->getTotalProductsByKeyword($keyword, $categoryId, $description, $model);
        $productTagToatal = $this->model_catalog_product->getTotalProductsByTag($keyword, $categoryId);

        $count = max($productTotal, $productTagToatal);

        if ($count) {
            $results = $this->model_catalog_product->getProductsByKeyword($keyword, $categoryId, $description, $model, $filter['sort_by'], $filter['sort_order'], $start, $filter['page_size']);
            $tagResults = $this->model_catalog_product->getProductsByTag($keyword, $categoryId, $filter['sort_by'], $filter['sort_order'], $start, $filter['page_size']);
            $totalProducts = array_merge($results, $tagResults);
        } else {
            $totalProducts = array();
        }

        return $totalProducts;
    }

    /**
     * get special products
     * @return type
     * @author hujs
     */
    public function getSpecialProducts($filter) {

        $pageNo = max($filter['page_no'] - 1, 0) * $filter['page_size'];
        $this->load->model('catalog/product');
        $rows = $this->model_catalog_product->getProductSpecials($filter['sort_by'], $filter['sort_order'], $pageNo, $filter['page_size']);

        $productTranslator = ServiceFactory::factory('ProductTranslator');
        foreach ($rows as $row) {
            $productTranslator->clear();
            $productTranslator->setProduct($row);
            $productTranslator->getItemBaseInfo();
            $productTranslator->getItemPrices();
            $items[] = $productTranslator->getTranslatedItem();
        }
        $returnResult['total_results'] = $this->model_catalog_product->getTotalProductSpecials();
        $returnResult['items'] = $items;

        return $returnResult;
    }

    /**
     * 
     * @param array $productIds
     * @return type
     * @author hujs
     */
    public function getSpecifiedProducts($filter) {
        $productIds = trim($filter['item_ids']);
        if (!is_array($productIds)) {
            $productIds = explode(',', $productIds);    
        }

        if(!sizeof($productIds)){
            return array('total_results' => 0, 'items' => array());
        }
        
        $start = max($filter['page_no'] - 1, 0) * $filter['page_size'];
        $itemIds = array_splice($productIds, $start, $filter['page_size']);
        $this->load->model('catalog/product');
        $productTranslator = ServiceFactory::factory('ProductTranslator');
        $items = array();
        foreach ($itemIds as $id) {
            $row = $this->model_catalog_product->getProduct($id);
            $productTranslator->clear();
            $productTranslator->setProduct($row);
            $productTranslator->getItemBaseInfo();
            $productTranslator->getItemPrices();
            $items[] = $productTranslator->getTranslatedItem();
        }
        $returnResult['total_results'] = count($productIds);
        $returnResult['items'] = $items;

        return $returnResult;
    }

    /**
     * Get one product info
     * @param integer $goods_id 商品id
     * @return array
     * @author hujs
     */
    public function getProduct($productId) {
        $this->load->model('catalog/product');
        $row = $this->model_catalog_product->getProduct($productId);
        if ($row) {
            $productTranslator = ServiceFactory::factory('ProductTranslator');
            $productTranslator->setProduct($row);
            return $productTranslator->getFullItemInfo();
        }
        return array();
    }

}

?>