<?php
/*
 *  This will return order selected for view by admin in admin panel.(i.e sales/orders & click of view link) 
 *  & We did this, as we are adding a block for showing current loan application status & block will visible only if customer placed this order using Online loan payment method
 *  Check here : app/design/adminhtml/default/default/template/clxfunding/custom.phtml will clarify the code
 */
class CLExchange_Clexchangefunding_Block_Adminhtml_Sales_Order_View_Info_Block extends Mage_Core_Block_Template {
    
    protected $order;
    
    public function getOrder() {
        if (is_null($this->order)) {
            if (Mage::registry('current_order')) {
                $order = Mage::registry('current_order');
            }
            elseif (Mage::registry('order')) {
                $order = Mage::registry('order');
            }
            else {
                $order = new Varien_Object();
            }
            $this->order = $order;
        }
        return $this->order;
    }
}