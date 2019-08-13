<?php
/*
 * Used following to add custom options (key=>value pair) to our configuration parameters like ApprovalRequired,Cron Expressions,Is Best Offer & time frame in admin panel
 */
class CLExchange_Clexchangefunding_CronExpressionList
{
    /* custom values for Cron expression(selectInputField) in admin panel */
    public function toOptionArray()
    {
        return array(
            array('value' => '*/5 * * * *', 'label'=>Mage::helper('adminhtml')->__('Every 5 minutes')),
            array('value' => '*/10 * * * *', 'label'=>Mage::helper('adminhtml')->__('Every 10 minutes')),
            array('value' => '30 * * * *', 'label'=>Mage::helper('adminhtml')->__('Every 30 minutes')),
        );
    }
}

