<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * @author hujs
 */
class CategoryService extends BaseService {

    /**
     * get categories
     * @return type
     * @author hujs
     */
    public function getAllCategories() {
        $sql = 'SELECT
                    c.*, cd.name
                FROM ' . DB_PREFIX . 'category AS c
                LEFT JOIN ' . DB_PREFIX . 'category_description AS cd ON c.category_id = cd.category_id
                WHERE cd.language_id = ' . (int) $this->config->get('config_language_id') . ' and c.status = 1  order by c.sort_order,cd.name';
        $result = $this->db->query($sql);
        $pos = 0;
        $categories = array();
        $parent = array();
        foreach ($result->rows as $row) {
            $cid = $row['category_id'];
            $row['parent_id'] != 0 && $parent[$row['parent_id']] = true;
            $categories[$cid] = array(
                'cid' => $cid,
                'parent_cid' => $row['parent_id'] == 0 ? '-1' : $row['parent_id'],
                'name' => $row['name'],
                'is_parent' => false,
                'count' => 0,
                'position' => $pos++
            );
        }

        $this->getProductQuantity($categories);
        $this->getProductTotal($categories, $parent);

        return array_values($categories);
    }

    /**
     * Calculation category include sub categroy product counts
     * @auth hujs
     * @staticvar array $children
     * @param type $cats
     * @return boolean
     */
    private function getProductTotal(&$cats, $pids) {
        if (!($count = sizeof($pids))) {//depth=1
            return;
        }

        $parents = array();
        $newPids = array();
        foreach ($cats as $key => &$cat) {
            if (isset($pids[$key])) {
                $cat['is_parent'] = true;
                $parents[$key] = &$cat;
                $newPids[$cat['parent_cid']] = true;
            } elseif ($cat['parent_cid'] != -1) {
                $cats[$cat['parent_cid']]['count'] += intval($cat['count']);
            }
        }
        $pcount = sizeof($newPids);

        while ($pcount > 1 && $count != $pcount) { //one parent or only children
            $count = $pcount;
            $pids = array();
            foreach ($parents as $key => &$parent) {
                if (!isset($newPids[$key])) {
                    if ($parent['parent_cid'] != -1) {
                        $parents[$parent['parent_cid']]['count'] += intval($parent['count']);
                    }
                    unset($parents[$key]);
                } else {
                    $pids[$parent['parent_cid']] = true;
                }
            }
            $pcount = sizeof($pids);
            $newPids = $pids;
        }
    }

    /**
     * get total of one category id
     * @param type $categoryId
     * @param type $subCategories
     * @return type
     * @author hujs
     */
    private function getProductQuantity(&$categories) {
        $dbPrefix = DB_PREFIX;
        $productCountSql = "
            SELECT
            p2c.category_id AS cid, count(distinct(p.product_id)) as count
            FROM
            {$dbPrefix}product p
            LEFT JOIN {$dbPrefix}product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN {$dbPrefix}product_to_store p2s ON (p.product_id = p2s.product_id)
            LEFT JOIN {$dbPrefix}stock_status ss ON (p.stock_status_id = ss.stock_status_id)
            LEFT JOIN {$dbPrefix}product_to_category p2c ON (
                p.product_id = p2c.product_id
            )
            WHERE
            p.status = 1
            AND p.date_available <= NOW() 
            AND pd.language_id = " . (int) $this->config->get('config_language_id') . "
            AND p2s.store_id = " . (int) $this->config->get('config_store_id') . "
            AND ss.language_id = " . (int) $this->config->get('config_language_id') . "
            GROUP BY p2c.category_id";
        $result = $this->db->query($productCountSql);

        foreach ($result->rows as $row) {
            if (isset($categories[$row['cid']])) {
                $categories[$row['cid']]['count'] = $row['count'];
            }
        }
    }

}

?>
