<?php
/* This is to restrict merchant to change title of payment method */
class CLExchange_Clexchangefunding_Model_PaymentMethodTitle extends Mage_Core_Model_Config_Data {
    /* save config in admin panel */
    public function save() {
        /*$title = $this->getValue(); 
        if (isset($title) && !empty($title) && $title != 'Online Loan') {
            Mage::throwException("CLX Message - Please contact CLExchange to change title");
        }*/
        return parent::save();  
    }
}
