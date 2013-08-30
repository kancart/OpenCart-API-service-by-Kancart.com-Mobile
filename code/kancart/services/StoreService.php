<?php

if (!defined('IN_KANCART')) {
    header('HTTP/1.1 404 Not Found');
    die();
}

/**
 * @author hujs
 */
class StoreService extends BaseService {

    /**
     * get store info
     * @author hujs
     */
    public function getStoreInfo() {
        $storeInfo = array();
        $storeInfo['general'] = $this->getGeneralInfo();
        $storeInfo['currencies'] = $this->getCurrencies();
        $storeInfo['countries'] = $this->getCountries();
        $storeInfo['zones'] = $this->getZones();
        $storeInfo['languages'] = $this->getLanguages();
        $storeInfo['order_statuses'] = $this->getOrderStatauses();
        $storeInfo['register_fields'] = $this->getRegisterFields();
        $storeInfo['address_fields'] = $this->getAddressFields();
        $storeInfo['category_sort_options'] = $this->getCategorySortOptions();
        $storeInfo['search_sort_options'] = $this->getSearchSortOptions();
        return $storeInfo;
    }

    /**
     * get Languages
     * @global type $languages
     * @return string
     * @author hujs
     */
    public function getLanguages() {
        $info = array();
        $position = 0;
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        foreach ($languages as $language) {
            if (intval($language['status'])) {
                $info[] = array(
                    'language_id' => $language['code'],
                    'language_code' => strtolower($language['code']),
                    'language_name' => $language['name'],
                    'language_text' => $language['locale'],
                    'position' => $position++,
                );
            }
        }

        return $info;
    }

    /**
     * get countries
     * @return type
     * @author hujs
     */
    public function getCountries() {

        $shopCountries = array();
        $this->load->model('localisation/country');
        $rows = $this->model_localisation_country->getCountries();
        foreach ($rows as $row) {
            $shopCountries[] = array(
                'country_id' => $row['country_id'],
                'country_name' => $row['name'],
                'country_iso_code_2' => $row['iso_code_2'],
                'country_iso_code_3' => $row['iso_code_3']
            );
        }
        return $shopCountries;
    }

    /**
     * get zones
     * @return type
     * @author hujs
     */
    public function getZones() {
        $shopZones = array();
        $query = $this->db->query("select * from " . DB_PREFIX . "zone");
        foreach ($query->rows as $row) {
            $shopZones[] = array(
                'zone_id' => $row['zone_id'],
                'country_id' => $row['country_id'],
                'zone_name' => $row['name'],
                'zone_code' => $row['code']
            );
        }
        $query = $this->db->query("select max(zone_id) max_zone_id from " . DB_PREFIX . "zone");
        $maxZoneId = $query->row['max_zone_id'];
        $nullZones = $this->db->query(
                "SELECT
                c.country_id
                FROM " . DB_PREFIX . "country c
                LEFT JOIN " . DB_PREFIX . "zone z ON c.country_id = z.country_id
                WHERE z.country_id IS NULL");
        if ($nullZones->num_rows) {
            foreach ($nullZones->rows as $nullZone) {
                $shopZones[] = array(
                    'zone_id' => $maxZoneId + 1,
                    'country_id' => $nullZone['country_id'],
                    'zone_name' => '_NONE_',
                    'zone_code' => ''
                );
            }
        }
        return $shopZones;
    }

    /**
     * get currencies
     * @return type
     * @author hujs
     */
    public function getCurrencies() {
        $this->load->model('localisation/currency');
        $currencies = $this->model_localisation_currency->getCurrencies();
        $shopCurencies = array();
        if ($currencies) {
            foreach ($currencies as $currencyEntry) {
                if (intval($currencyEntry['status'])) {
                    $shopCurencies[] = array(
                        'currency_code' => $currencyEntry['code'],
                        'default' => $this->config->get('config_currency') == $currencyEntry['code'],
                        'currency_symbol' => $currencyEntry['symbol_left'] ? $currencyEntry['symbol_left'] : $currencyEntry['symbol_right'],
                        'currency_symbol_right' => $currencyEntry['symbol_right'] ? true : false,
                        'decimal_symbol' => isset($currencyEntry['decimal_point']) ? $currencyEntry['decimal_point'] : '.',
                        'group_symbol' => isset($currencyEntry['thousands_point']) ? $currencyEntry['thousands_point'] : ',',
                        'decimal_places' => isset($currencyEntry['decimal_place']) ? $currencyEntry['decimal_place'] : '0',
                        'description' => $currencyEntry['title']
                    );
                }
            }
        }
        return $shopCurencies;
    }

    public function getOrderStatauses() {
        $status = array();
        $position = 0;
        $query = $this->db->query("select * from " . DB_PREFIX . "order_status");
        foreach ($query->rows as $row) {
            $status[] = array(
                'status_id' => $row['order_status_id'],
                'status_name' => $row['name'],
                'display_text' => $row['name'],
                'language_id' => $row['language_id'],
                'date_added' => '',
                'comments' => '',
                'position' => $position++
            );
        }

        return $status;
    }

    public function getGeneralInfo() {
        $version = get_project_version(TRUE);
        return array(
            'cart_type' => 'opencart',
            'cart_version' => $version ? $version : 'unknown',
            'plugin_version' => KANCART_PLUGIN_VERSION,
            'support_kancart_payment' => true,
            'login_by_mail' => true
        );
    }

    /**
     * get register fields
     * @return type
     * @author hujs
     */
    public function getRegisterFields() {
        $registerFields = array(
            array('type' => 'email', 'required' => true),
            array('type' => 'pwd', 'required' => true),
            array('type' => 'telephone', 'required' => true)
        );
        return $registerFields;
    }

    /**
     * get address fileds
     * @return array
     * @author hujs
     */
    public function getAddressFields() {
        $addressFields = array(
            array('type' => 'firstname', 'required' => true),
            array('type' => 'lastname', 'required' => true),
            array('type' => 'country', 'required' => true),
            array('type' => 'zone', 'required' => true),
            array('type' => 'city', 'required' => true),
            array('type' => 'address1', 'required' => true),
            array('type' => 'address2', 'required' => false),
            array('type' => 'postcode', 'required' => false),
        );
        return $addressFields;
    }

    /**
     * Verify the address is complete
     * @param type $address
     * @return boolean
     */
    public function checkAddressIntegrity($address) {
        if (empty($address) || !is_array($address)) {
            return false;
        }

        $addressFields = $this->getAddressFields();
        foreach ($addressFields as $field) {
            if ($field['required'] === true) {
                $name = $field['type'];
                if ($name == 'country') {
                    if (!isset($address['country_id']) || empty($address['country_id'])) {
                        return false;
                    }
                } elseif ($name == 'zone') {
                    if (isset($address['zone_id']) && intval($address['zone_id'])) {
                        continue;
                    } elseif (isset($address['state']) && $address['state']) {
                        continue;
                    } elseif (isset($address['zone_name']) && $address['zone_name']) {
                        continue;
                    } else {
                        return false;
                    }
                } elseif ($name == 'city') {
                    if (isset($address['city_id']) && intval($address['city_id'])) {
                        continue;
                    } elseif (isset($address['city']) && $address['city']) {
                        continue;
                    } else {
                        return false;
                    }
                } elseif (!isset($address[$name]) || empty($address[$name])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * get category sort options
     * @global type $language
     * @return string
     * @author hujs
     */
    public function getCategorySortOptions() {

        $this->language->load('product/category');
        $categorySortOptions = array();

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_default'),
                'code' => 'p.sort_order:ASC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_name_asc'),
                'code' => 'pd.name:ASC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_name_desc'),
                'code' => 'pd.name:DESC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_price_asc'),
                'code' => 'p.price:ASC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_price_desc'),
                'code' => 'p.price:DESC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_rating_desc'),
                'code' => 'rating:DESC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_rating_asc'),
                'code' => 'rating:ASC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_model_asc'),
                'code' => 'p.model:ASC',
                'arrow_type' => ''
                ));

        $categorySortOptions[] = array(array(
                'title' => $this->language->get('text_model_desc'),
                'code' => 'p.model:DESC',
                'arrow_type' => ''
                ));

        return $categorySortOptions;
    }

    public function getSearchSortOptions() {
        return $this->getCategorySortOptions();
    }

}

?>
