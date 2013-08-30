<?php

/**
 * @author hujs
 */
class UserService extends BaseService {

    private $addressFieldsMap = array(
        'address_id' => 'address_book_id',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'address_1' => 'address1',
        'address_2' => 'address2',
        'city' => 'city',
        'zone_id' => 'zone_id',
        'country_id' => 'country_id',
        'postcode' => 'postcode',
        'company' => 'company'
    );

    /**
     * translate the osCommerce's address to kancart's address format
     * @param type $address
     * @return type
     * @author hujs
     */
    public function translateAddress($address) {
        $translatedAddress = array();
        foreach ($this->addressFieldsMap as $key => $value) {
            $translatedAddress[$value] = $address[$key];
        }
        $translatedAddress['telephone'] = $this->customer->getTelephone();
        return $translatedAddress;
    }

    /**
     * Get user's addresses
     * @global type $customer_id
     * @return type
     * @author hujs
     */
    public function getAddresses() {
        $this->load->model('account/address');
        $addressesInfo = $this->model_account_address->getAddresses();
        $addresses = array();
        foreach ($addressesInfo as $addresse) {
            $addresses[] = $this->translateAddress($addresse);
        }
        return $addresses;
    }

    /**
     * check whether the email has already existed in the database
     * @param type $email
     * @return boolean
     * @author hujs
     */
    public function checkEmailExists($email) {
        if ($email) {
            $this->load->model('account/customer');
            $count = intval($this->model_account_customer->getTotalCustomersByEmail($email));
            return $count > 0;
        }
        return true;
    }

    /**
     * User register
     * @global type $cart
     * @param type $registerInfo
     * @return boolean
     * @author hujs
     */
    public function register($registerInfo) {

        $this->load->model('account/customer');
        $this->url = new Url($this->config->get('config_url'), $this->config->get('config_ssl'));

        $this->model_account_customer->addCustomer($registerInfo);

        return true;
    }

    /**
     * User login
     * @global type $cart
     * @param type $loginInfo
     * @return type
     * @author hujs
     */
    public function login($loginInfo) {
        $this->language->load('account/login');
        $result = $this->customer->login($loginInfo['email'], $loginInfo['password']);
        if ($result) {
            $this->load->model('account/customer');
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);

            if ($customer_info && !$customer_info['approved']) {
                return $this->language->get('error_approved');
            }

            unset($this->session->data['guest']);

            // Default Shipping Address
            $this->load->model('account/address');

            $address_info = $this->model_account_address->getAddress($this->customer->getAddressId());

            if ($address_info) {
                if ($this->config->get('config_tax_customer') == 'shipping') {
                    $this->session->data['shipping_address_id'] = $this->customer->getAddressId(); //
                    $this->session->data['shipping_country_id'] = $address_info['country_id'];
                    $this->session->data['shipping_zone_id'] = $address_info['zone_id'];
                    $this->session->data['shipping_postcode'] = $address_info['postcode'];
                    $this->customer->country_id = $address_info['country_id'];
                    $this->customer->zone_id = $address_info['zone_id'];
                }

                if ($this->config->get('config_tax_customer') == 'payment') {
                    $this->session->data['payment_address_id'] = $this->customer->getAddressId(); //
                    $this->session->data['payment_country_id'] = $address_info['country_id'];
                    $this->session->data['payment_zone_id'] = $address_info['zone_id'];
                }
            } else {
                unset($this->session->data['shipping_address_id']); //
                unset($this->session->data['payment_address_id']); //
                unset($this->session->data['shipping_country_id']);
                unset($this->session->data['shipping_zone_id']);
                unset($this->session->data['shipping_postcode']);
                unset($this->session->data['payment_country_id']);
                unset($this->session->data['payment_zone_id']);
            }
            return true;
        }
        return $this->language->get('error_login');
    }

    /**
     * validateForm
     * @param type $addressInfo
     * @param type $language
     * @return type
     * @author hujs
     */
    private function validateForm($addressInfo, $language) {
        $error = array();

        if ((strlen(utf8_decode($addressInfo['firstname'])) < 1) || (strlen(utf8_decode($addressInfo['firstname'])) > 32)) {
            $error[] = $language->get('error_firstname');
        }

        if ((strlen(utf8_decode($addressInfo['lastname'])) < 1) || (strlen(utf8_decode($addressInfo['lastname'])) > 32)) {
            $error[] = $language->get('error_lastname');
        }

        if ((strlen(utf8_decode($addressInfo['address_1'])) < 3) || (strlen(utf8_decode($addressInfo['address_1'])) > 128)) {
            $error[] = $language->get('error_address_1');
        }

        if ((strlen(utf8_decode($addressInfo['city'])) < 3) || (strlen(utf8_decode($addressInfo['city'])) > 128)) {
            $error[] = $language->get('error_city');
        }

        $this->load->model('localisation/country');
        $countryInfo = $this->model_localisation_country->getCountry($addressInfo['country_id']);

        if ($countryInfo && $countryInfo['postcode_required']) {
            if ((strlen(utf8_decode($addressInfo['postcode'])) < 2) || (strlen(utf8_decode($addressInfo['postcode'])) > 10)) {
                $error[] = $language->get('error_postcode');
            }
        }

        if ($addressInfo['country_id'] == FALSE) {
            $error[] = $language->get('error_country');
        }

        if (!isset($addressInfo['zone_id']) || $addressInfo['zone_id'] == '') {
            $error[] = $this->language->get('error_zone');
        }

        return $error;
    }

    /**
     * get address by id
     * @param type $addressBookId
     * @return type
     * @author hujs
     */
    public function getAddress($addressBookId) {

        $this->load->model('account/address');
        $address = $this->model_account_address->getAddress($addressBookId);

        if ($address === false) {
            return array();
        } else {
            $address['address_id'] = $addressBookId;
            return $this->translateAddress($address);
        }
    }

    /**
     * add an address entry to address book
     * @param type $addressInfo
     * @author hujs
     */
    public function addAddress($addressInfo) {

        $this->language->load('account/address');

        $errorMessages = $this->validateForm($addressInfo, $this->language);
        if (empty($errorMessages)) {
            $this->load->model('account/address');
            $addressId = $this->model_account_address->addAddress($addressInfo);

            $firstName = $this->customer->getFirstName();
            $telephone = $this->customer->getTelephone();
            if ((empty($firstName) && $addressInfo['firstname']) || (empty($telephone) && $addressInfo['telephone'])) {
                $data = array(
                    'firstname' => $addressInfo['firstname'],
                    'lastname' => $addressInfo['lastname'],
                    'email' => $this->customer->getEmail(),
                    'telephone' => $addressInfo['telephone'],
                    'fax' => '');
                $this->load->model('account/customer');
                $this->model_account_customer->editCustomer($data);
            }

            return $addressId;
        }

        return $errorMessages;
    }

    /**
     * delete  an entry from address book
     * @param type $addressId
     * @return type
     * @author hujs
     */
    public function deleteAddress($addressId) {

        $this->language->load('account/address');
        $this->load->model('account/address');

        $errorMessages = array();

        if ($this->model_account_address->getTotalAddresses() == 1) {
            $errorMessages[] = $this->language->get('error_delete');
        }

        if ($this->customer->getAddressId() == $addressId) {
            $errorMessages[] = $this->language->get('error_default');
        }

        if (count($errorMessages) <= 0) {
            $this->model_account_address->deleteAddress($addressId);
            // Default Shipping Address
            if (isset($this->session->data['shipping_address_id']) && ($addressId == $this->session->data['shipping_address_id'])) {
                unset($this->session->data['shipping_address_id']);
                unset($this->session->data['shipping_country_id']);
                unset($this->session->data['shipping_zone_id']);
                unset($this->session->data['shipping_postcode']);
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
            }

            // Default Payment Address
            if (isset($this->session->data['payment_address_id']) && ($addressId == $this->session->data['payment_address_id'])) {
                unset($this->session->data['payment_address_id']);
                unset($this->session->data['payment_country_id']);
                unset($this->session->data['payment_zone_id']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
            }

            $this->session->data['success'] = $this->language->get('text_delete');
            return true;
        } else {
            return $errorMessages;
        }
    }

    /**
     * update user's address
     * @global type $customer_id
     * @param type $addressInfo
     * @return boolean|string
     * @author hujs
     */
    public function updateAddress($addressInfo) {

        $this->language->load('account/address');
        $this->load->model('account/address');

        $errorMessages = $this->validateForm($addressInfo, $this->language);
        if (empty($errorMessages)) {
            $this->model_account_address->editAddress($addressInfo['address_id'], $addressInfo);
            // Default Shipping Address
            if (isset($this->session->data['shipping_address_id']) && ($addressInfo['address_id'] == $this->session->data['shipping_address_id'])) {
                $this->session->data['shipping_country_id'] = $addressInfo['country_id'];
                $this->session->data['shipping_zone_id'] = $addressInfo['zone_id'];
                $this->session->data['shipping_postcode'] = $addressInfo['postcode'];

                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
            }

            // Default Payment Address
            if (isset($this->session->data['payment_address_id']) && ($addressInfo['address_id'] == $this->session->data['payment_address_id'])) {
                $this->session->data['payment_country_id'] = $addressInfo['country_id'];
                $this->session->data['payment_zone_id'] = $addressInfo['zone_id'];

                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
            }

            $this->session->data['success'] = $this->language->get('text_update');
            return true;
        } else {
            return $errorMessages;
        }
    }

    /**
     * User logout
     */
    public function logout() {
        if ($this->customer->isLogged()) {
            $this->customer->logout();
            $this->cart->clear();

            unset($this->session->data['wishlist']);
            unset($this->session->data['shipping_address_id']);
            unset($this->session->data['shipping_country_id']);
            unset($this->session->data['shipping_zone_id']);
            unset($this->session->data['shipping_postcode']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_address_id']);
            unset($this->session->data['payment_country_id']);
            unset($this->session->data['payment_zone_id']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
        }
    }

}

?>
