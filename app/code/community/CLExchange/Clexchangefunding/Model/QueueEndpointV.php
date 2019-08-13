<?php
/* This is to restrict merchant to change title of payment method */
class CLExchange_Clexchangefunding_Model_QueueEndpointV extends Mage_Core_Model_Config_Data {
    /* save config in admin panel */
    public function save() {
        $qev = $this->getValue(); //get the value from our config
        if (!isset($qev) || empty($qev)) {
            Mage::throwException("CLExchange Payment Module - Queue Endpoint is required");
        }
        return parent::save();  //call original save method so whatever happened
    }
}
