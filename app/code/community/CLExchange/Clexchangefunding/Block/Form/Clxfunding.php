<?php
/*
 * This block contain our js scripts & it will appended on customer checkout.
 */
class CLExchange_Clexchangefunding_Block_Form_Clxfunding extends Mage_Payment_Block_Form {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('clxfunding/form/clxfunding.phtml');
    }

}
