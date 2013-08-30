<?php

abstract class BaseService {

    protected $registry;

    public function __construct() {
        $this->registry = $GLOBALS['register'];
    }

    public function __get($key) {
        return $this->registry->get($key);
    }

    public function __set($key, $value) {
        $this->registry->set($key, $value);
    }

    protected function format($number, $currency = '', $value = '', $format = false) {
        return $this->currency->format($number, $currency, $value, $format);
    }

    protected function setZone($country_id, $zone_id) {
        if (method_exists($this->tax, 'setZone')) { //apply for 1.5.0.0+
            $this->tax->setZone($country_id, $zone_id);
        } elseif (method_exists($this->tax, 'setShippingAddress')) { //apply for 1.5.1.3+
            $this->tax->setShippingAddress($country_id, $zone_id);
        }
    }

    protected function getTax($value, $tax_class_id) {
        if(method_exists($this->tax, 'getTax')){ ////apply for 1.5.1.3+
            return $this->tax->getTax($value, $tax_class_id);
        }else{
            return $this->tax->getRate($tax_class_id);
        }
    }

}

?>
