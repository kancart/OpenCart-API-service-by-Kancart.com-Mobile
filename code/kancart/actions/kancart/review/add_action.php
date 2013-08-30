<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

class kancart_review_add_action extends UserAuthorizedAction {

    public function validate() {
        if (parent::validate()) {
            $content = $this->getParam('content');
            if (!isset($content) || $content == '') {
                $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER);
                return false;
            }
            $item_id = $this->getParam('item_id');
            if (!isset($item_id) || $item_id == '') {
                $this->setError(KancartResult::ERROR_USER_INPUT_PARAMETER);
                return false;
            }
            if ($this->config->has('config_review_status') && !$this->config->get('config_review_status')) {
                $this->setError('', 'Add review is not allowed.');
                return false;
            }
        }
        return true;
    }

    public function execute() {

        $itemId = $this->getParam('item_id');
        $rating = is_null($this->getParam('rating')) ? 5 : intval($this->getParam('rating'));
        if ($rating > 5) {
            $rating = 5;
        } elseif ($rating < 0) {
            $rating = 0;
        }

        $content = is_null($this->getParam('content')) ? '' : htmlspecialchars(substr(trim($this->getParam('content')), 0, 1000));
        $reviewService = ServiceFactory::factory('Review');
        if ($reviewService->addReview($itemId, $content, $rating)) {
            $this->setSuccess();
        } else {
            $this->setError('', 'add review to this product failed.');
        }
    }

}

?>
