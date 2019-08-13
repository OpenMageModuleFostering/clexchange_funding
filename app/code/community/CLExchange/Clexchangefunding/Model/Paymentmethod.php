<?php

class CLExchange_Clexchangefunding_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'clxfunding';
    protected $_formBlockType = 'clxfunding/form_clxfunding';

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('clxfunding/ClxAPI/saveStaus', array('_secure' => false));
    }

}
