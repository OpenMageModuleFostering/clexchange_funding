<?php
/* This is to restrict merchant to change title of payment method */
class CLExchange_Clexchangefunding_Model_AuthorizationKeyV extends Mage_Core_Model_Config_Data {
    /* save config in admin panel */
    public function save() {
        $authkey_v = $this->getValue(); //get the value from our config
        if (!isset($authkey_v) || empty($authkey_v)) {
            Mage::throwException("CLExchange Payment Module - Authentication Key is required");
        }
        return parent::save();  //call original save method so whatever happened
    }
}
