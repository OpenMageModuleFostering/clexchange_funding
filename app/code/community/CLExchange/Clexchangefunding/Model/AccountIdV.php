<?php
/* This is to restrict merchant to change title of payment method */
class CLExchange_Clexchangefunding_Model_AccountIdV extends Mage_Core_Model_Config_Data {
    /* save config in admin panel */
    public function save() {
        $accountId = $this->getValue(); //get the value from our config
        if (!isset($accountId) || empty($accountId)) {
            Mage::throwException("CLExchange Payment Module - AccountId is required");
        }
        return parent::save();  //call original save method so whatever happened
    }
}
