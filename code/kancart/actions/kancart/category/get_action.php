<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_category_get_action extends BaseAction {

    public function execute() {
        $categoryService = ServiceFactory::factory('Category');
        $categories = $categoryService->getAllCategories();
        if (isset($_REQUEST['all_cat'])) {
            $this->setSuccess(array('item_cats' => $categories));
            return;
        } else {
            if (!isset($_REQUEST['parent_cid'])) {
                $this->setError(KancartResult::ERROR_CATEGORY_INPUT_PARAMETER);
                return;
            }
            $parent_cid = -1;
            if ($_REQUEST['parent_cid'] != -1) {
                $parent_cid = $_REQUEST['parent_cid'];
            }
            $info = $categoryService->getSubCategories($parent_cid, $categories);
            $this->setSuccess(array('item_cats' => $info));
            return;
        }
    }

}

?>
