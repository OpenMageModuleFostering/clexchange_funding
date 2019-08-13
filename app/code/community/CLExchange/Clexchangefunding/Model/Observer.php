<?php

class CLExchange_Clexchangefunding_Model_Observer{
    /* This function to check extension configured with all required information, if not Hide CLXFunding else Show */

    public function paymentMethodIsActive(Varien_Event_Observer $observer) {

        /* extension core config data */
        $isExtensionActive = Mage::getStoreConfig('payment/clxfunding/active');
        if(isset($isExtensionActive) && !empty($isExtensionActive) && $isExtensionActive)
        {
            $clx_config_var = new Mage_Core_Model_Config();
            $accountId = Mage::getStoreConfig('payment/clxfunding/account_id'); //Account ID
            $authorizationField = Mage::getStoreConfig('payment/clxfunding/authorization_field'); //Authorization Key
            $queue_authentication_key = Mage::getStoreConfig('payment/clxfunding/queue_authentication_key'); //Queue authentication key
            $queue_endpoint = Mage::getStoreConfig('payment/clxfunding/queue_endpoint'); //queue endpoint
            
            // if all required configuration parameters set then only show CLX Funding Button 
            if (!(isset($accountId) && !empty($accountId) &&
                    isset($authorizationField) && !empty($authorizationField) &&
                    isset($queue_authentication_key) && !empty($queue_authentication_key) &&
                    isset($queue_endpoint) && !empty($queue_endpoint)
                    )) {

                $clx_config_var->saveConfig('payment/clxfunding/active', "0"); // to disable module (hide CLX Funding radio button)
            } 
        }
    }

    /* following function for getting Queue Response & processing order accordingly */

    public function checkCron() {
        Mage::getModel('clxfunding/CronOrderStatusUpdate')->updateClxOrderStatus();
    }

    /* Following function is used for two purposes*/
    /* 1.Order cancel if customer not responding to loan offer mail notification */
    /* 2.Get loan application status*/
    public function timeframeCancelOrder() {
        Mage::getModel('clxfunding/CronOrderStatusUpdate')->cancelOrderAfterTimeExpire(); // loan offer mail link expire
        Mage::getModel('clxfunding/CronOrderStatusUpdate')->aPILoanApplcationStatus(); // update loan application status & notify user & merchant accordingly
    }
    
    public function getSalesOrderViewInfo(Varien_Event_Observer $observer) {
        $block = $observer->getBlock();
        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('mymodule.order.info.custom.block'))) {
            $transport = $observer->getTransport();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }
    public function addCustomMessage(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if ($block->getNameInLayout() == 'sales.order.info.child0') {
            if (Mage::registry('current_order')) {
                $order = Mage::registry('current_order');
            }
            elseif (Mage::registry('order')) {
                $order = Mage::registry('order');
            }
            else {
                $order = new Varien_Object();
            }
            
            $clx_status = $this->fetchStatus($order);
            $transport = $observer->getTransport();
            if ($transport) {
                $html = $transport->getHtml();
                if(isset($clx_status) && !empty($clx_status) && $clx_status)
                {
                    $clx_status_html = '<br/><b>(Current loan application status for this order is '.$clx_status.')</b>';
                    $html.=$clx_status_html;
                }
                $transport->setHtml($html);
            }
        }
        
    }
    public function fetchStatus($order){
        $clx_order_id = $order->getRealOrderId();
        if(isset($clx_order_id) && !empty($clx_order_id) && $clx_order_id)
        {
            $table_prefix = Mage::getConfig()->getTablePrefix();
            $clx_table = $table_prefix . 'clx_loan_application_detail';
            $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
            $select = $connectionRead->select()
                    ->from($clx_table, array('*'))
                    ->where('order_id=?',$clx_order_id);
            $row = $connectionRead->fetchRow($select);   //return rows
            if (isset($row) && !empty($row) && is_array($row) && isset($row['clx_loan_application_detail_id']))
            {
                return $row['status'];
            }
            else
            {
                return FALSE;
            }
        }
        else
        {
            return FALSE;
        }
    }

}

?>
