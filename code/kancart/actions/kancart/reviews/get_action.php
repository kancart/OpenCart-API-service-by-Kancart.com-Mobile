<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_reviews_get_action extends BaseAction {

    public function validate() {
        if (parent::validate()) {
            $itemId = $this->getParam('item_id');
            if (!isset($itemId) || $itemId == '') {
                $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER);
                return false;
            }
        }
        return true;
    }

    public function execute() {
        $pageNo = intval($_REQUEST['page_no']) - 1;
        $pageSize = intval($_REQUEST['page_size']);
        $itemId = intval($_REQUEST['item_id']);
        if ($pageSize <= 0) {
            $pageSize = 10;
        }
        $reviewService = ServiceFactory::factory('Review');
        $reviews = $reviewService->getReviews($itemId, $pageNo, $pageSize);
        $reviewCounts = $reviewService->getReviewsCount($itemId);
        $this->setSuccess(array(
            'trade_rates' => $reviews,
            'total_results' => $reviewCounts
        ));
    }

}

?>
