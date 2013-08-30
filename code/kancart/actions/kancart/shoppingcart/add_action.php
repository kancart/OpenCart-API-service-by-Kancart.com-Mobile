<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_shoppingcart_add_action extends BaseAction {

    public function validate() {
        if (!parent::validate()) {
            return false;
        }
        $itemId = $this->getParam('item_id');

        if (!isset($itemId)) {
            $errMesg = 'Item id is not specified .';
        }
        $qty = $this->getParam('qty');

        if (!is_numeric($qty) || intval($qty) <= 0) {
            $errMesg = 'Incorrect number of product.';
        }

        if ($errMesg) {
            $this->setError(KancartResult::ERROR_CART_INPUT_PARAMETER, $errMesg);
            return false;
        }
        return true;
    }

    public function execute() {
        $itemId = $this->getParam('item_id');
        $qty = $this->getParam('qty');
        $attributes = $_REQUEST['attributes'];
        $option = array();
        if ($attributes) {
            $attributes = str_replace('\\', '/', htmlspecialchars_decode($attributes, ENT_COMPAT));
            $attributes = json_decode(stripslashes(urldecode($attributes)));
            foreach ($attributes as $attribute) {
                $optionId = $attribute->attribute_id;
                if ($attribute->input == 'multiselect') {    //support checkbox array()
                    $option[$optionId] = explode(',', $attribute->value);
                } else {
                    $option[$optionId] = $attribute->value;
                }
            }
        }
        $cartService = ServiceFactory::factory('ShoppingCart');
        if (method_exists($cartService, 'checkMinimunOrder')) {
            $error = $this->beforeAction($itemId, $option, $qty, $cartService);
        } else {
            $error = true;
        }
        if ($error === true) {
            $cartService->add($itemId, $qty, $option);
            $result = $cartService->get();
            $result['messages'] = array(); //do not show  not in stock message 
        } else {
            $result = $cartService->get();
            $result['messages'] = $error;
        }

        $this->setSuccess($result);
    }

    /**
     * check product options when version >= 1.5
     * @param type $itemId
     * @param type $option
     * @param type $qty
     * @param type $cartService
     * @return boolean
     * @author hujs
     */
    public function beforeAction($itemId, $option, $qty, $cartService) {
        $error = $cartService->checkMinimunOrder($itemId, (int) $qty);
        if (sizeof($error)) {
            return $error;
        }

        $this->language->load('checkout/cart');
        $this->load->model('catalog/product');
        $productOptions = $this->model_catalog_product->getProductOptions($itemId);

        foreach ($productOptions as $productOption) {
            $optionId = $productOption['product_option_id'];
            if ($productOption['required'] && (!isset($option[$optionId]) || !$option[$optionId])) {
                $error[] = sprintf($this->language->get('error_required'), $productOption['name']);
            } else {
                switch ($productOption['type']) {
                    case 'date':
                    case 'time':
                    case 'datetime':
                        $date = str_replace('//', '/', $option[$optionId]);
                        $result = date_parse($date);
                        $result['error_count'] > 0 && $error[] = $productOption['name'] . ' Invalid.';
                        break;
                    case 'file':
                        $this->uploadFile($option[$optionId], $error);
                        break;
                    default:
                        break;
                }
            }
        }

        return sizeof($error) ? $error : true;
    }

    private function uploadFile($filePath, &$error) {
        $fileName = basename($filePath);
        $len = strlen($fileName);
        $this->language->load('product/product');
        if ($len < 3 || $len > 128) {
            $error[] = $this->language->get('error_filename');
        } else {
            $allowed = array();
            $filetypes = explode(',', $this->config->get('config_upload_allowed'));
            foreach ($filetypes as $filetype) {
                $allowed[] = trim($filetype);
            }

            if (!in_array(substr(strrchr($fileName, '.'), 1), $allowed)) {
                $error[] = $this->language->get('error_filetype');
            }
        }
        if (!sizeof($error) && is_uploaded_file($filePath) && file_exists($filePath)) {
            move_uploaded_file($filePath, DIR_DOWNLOAD . $fileName . '.' . md5(rand()));
        }
        $this->language->load('checkout/cart');
    }

}

?>
