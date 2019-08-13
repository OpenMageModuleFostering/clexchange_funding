<?php
/* This is to restrict merchant to change title of payment method */
class CLExchange_Clexchangefunding_Model_QueueAuthKeyV extends Mage_Core_Model_Config_Data {
    /* save config in admin panel */
    public function save() {
        $qak_v = $this->getValue(); //get the value from our config
        if (!isset($qak_v) || empty($qak_v)) {
            Mage::throwException("CLExchange Payment Module - Queue Authentication Key is required");
        }
        return parent::save();  //call original save method so whatever happened
    }
}
