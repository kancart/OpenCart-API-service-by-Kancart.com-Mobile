<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * @author hujs
 */
class ReviewService extends BaseService {

    public function __construct() {
        parent::__construct();

        $this->load->model('catalog/review');
    }

    /**
     * get product avg rating score
     * @param type $productId
     * @return int
     * @author hujs
     */
    public function getAvgRatingScore($productId) {

        if (method_exists($this->model_catalog_review, 'getAverageRating')) {
            $average = $this->model_catalog_review->getAverageRating($productId);
        } else { //apply for version>=1.5.5
            $query = $this->db->query("SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review WHERE status = '1' AND product_id = '" . (int) $productId . "' GROUP BY product_id");

            if (isset($query->row['total'])) {
                return round($query->row['total']);
            } else {
                return 0;
            }
        }


        return $average;
    }

    /**
     * get one product reviews count
     * @param type $productId
     * @return int
     * @author hujs
     */
    public function getReviewsCount($productId) {

        $count = $this->model_catalog_review->getTotalReviewsByProductId($productId);

        return $count;
    }

    /**
     * add a review
     * @param type $itemId
     * @param type $content
     * @param type $rating
     * @author hujs
     */
    public function addReview($itemId, $content, $rating) {
        if (!$this->config->has('config_review_status') || $this->config->get('config_review_status')) {
            $userName = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();

            $date = array(
                'name' => $userName,
                'text' => $content,
                'rating' => $rating
            );

            $this->model_catalog_review->addReview($itemId, $date);
            return true;
        }

        return false;
    }

    /**
     * get reviews by product id
     * @param type $productId
     * @param type $pageNo
     * @param type $pageSize
     * @return type
     * @author hujs
     */
    public function getReviews($productId, $pageNo = 0, $pageSize = 10) {
        $start = max($pageNo, 0) * $pageSize;
        $rows = $this->model_catalog_review->getReviewsByProductId($productId, $start, $pageSize);

        foreach ($rows as $row) {
            $reviews[] = array(
                'uname' => $row['author'],
                'item_id' => $productId,
                'rate_score' => $row['rating'],
                'rate_content' => $row['text'],
                'rate_date' => $row['date_added']
            );
        }
        return $reviews;
    }

}

?>
