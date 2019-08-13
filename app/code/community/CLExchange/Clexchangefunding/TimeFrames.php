<?php
/*
 * Used following to add custom options (key=>value pair) to our configuration parameters like ApprovalRequired,Cron Expressions,Is Best Offer & time frame in admin panel
 */
class CLExchange_Clexchangefunding_TimeFrames {
    /* custom values for TimeFrames(selectInputField) in admin panel */
    public function toOptionArray() {
        return array(
            array('value' => '1', 'label' => Mage::helper('adminhtml')->__('24 Hours (1 day)')),
            array('value' => '2', 'label' => Mage::helper('adminhtml')->__('48 Hours (2 days)')),
            array('value' => '3', 'label' => Mage::helper('adminhtml')->__('72 Hours (3 days)')),
            array('value' => '4', 'label' => Mage::helper('adminhtml')->__('96 Hours (4 days)')),
            array('value' => '5', 'label' => Mage::helper('adminhtml')->__('120 Hours (5 days)')),
            array('value' => '6', 'label' => Mage::helper('adminhtml')->__('144 Hours (6 days)'))
        );
    }

}
