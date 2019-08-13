<?php
/*
 * Used following to add custom options (key=>value pair) to our configuration parameters like ApprovalRequired,Cron Expressions,Is Best Offer & time frame in admin panel
 */
class CLExchange_Clexchangefunding_ApprovalRequired {
    /* custom values of Approval Required(selectInputField) in admin panel */
    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('adminhtml')->__('true')),
            array('value' => '0', 'label' => Mage::helper('adminhtml')->__('false'))
        );
    }

}
