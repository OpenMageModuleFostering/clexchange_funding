<?php

class CLExchange_Clexchangefunding_Model_ClxEmail extends Mage_Core_Model_Email_Template {
    /* send mail function */

    public function sendEmail($templateId, $email, $name, $subject, $params = array()) {
        /* send mail sender details */
        // ident_sales - sales representative 
        // ident_general - Owner
        // $email - customer email
        // $name - customer name
        // $params - pass parameters to template
        $sender = array('name' => Mage::getStoreConfig('trans_email/ident_sales/name'), 'email' => Mage::getStoreConfig('trans_email/ident_sales/email'));

        $this->setDesignConfig(array('area' => 'frontend', 'store' => $this->getDesignConfig()->getStore()))
                ->setTemplateSubject($subject)
                ->sendTransactional(
                        $templateId, $sender, $email, $name, $params
        );
    }

}
