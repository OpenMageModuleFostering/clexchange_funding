<?php

class CLExchange_Clexchangefunding_Model_CronOrderStatusUpdate {

    public function updateClxOrderStatus() {
        $QueueResponse = Mage::helper('clxfunding')->clxQueueingSystem(); //this will call queueing system to get loan application status

        if(isset($QueueResponse) && $QueueResponse && isset($QueueResponse->messages) && is_array($QueueResponse->messages))
        {
            foreach($QueueResponse->messages as $res)
            {
                $body = json_decode('{'.$res->body.'}');
                if (isset($body->application_status) && isset($body->source_application_Id) && !is_null($body->source_application_id)) {
                    $this->commonFunctionToUpdateStatus($body);
                }
            }
        }
    }

    public function commonFunctionToUpdateStatus($QueueResponse) {
        $table_prefix = Mage::getConfig()->getTablePrefix();
        $clx_table = $table_prefix . 'clx_loan_application_detail';
        $applicationId = $QueueResponse->source_application_id;
        $clxOrderStatus = $QueueResponse->application_status;
        if (isset($applicationId) && !empty($applicationId) && isset($clxOrderStatus) && !empty($clxOrderStatus)) {
            $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
            $select = $connectionRead->select()
                    ->from($clx_table, array('*'))
                    ->where('application_id=? and status!="FUNDED" and status!="LENDER_REJECTED" and status!="REJECTED" and status!="CUSTOMER_REJECTED" and status!="MERCHANT_REJECTED"', $applicationId);
            $row = $connectionRead->fetchRow($select);   //return rows
            if (isset($row) && !empty($row) && is_array($row) && isset($row['clx_loan_application_detail_id'])) {
                if (isset($QueueResponse->loan_offers[0]) && !empty($QueueResponse->loan_offers[0]) && count($QueueResponse->loan_offers[0]) && $row['approvalRequired_flag'] && $row['mail_sent'] != 1 && $row['status'] != 'CUSTOMER_ACCEPTED') {
                    if (isset($row['order_id']) && $row['order_id']) {
                        $order = Mage::getModel('sales/order')->loadByIncrementId($row['order_id']);//This will fetch order details & $row['order_id'] is order number 
                        if (isset($order) && !empty($order)) {
                            if (isset($order['customer_email']) && !empty($order['customer_email']) && isset($order['customer_firstname']) && !empty($order['customer_firstname']) && isset($order['customer_lastname']) && !empty($order['customer_lastname'])) {
                                /* save loan offer details */
                                $loan_offer_data = array();
                                if (isset($QueueResponse->loan_offers[0]->id) && !empty($QueueResponse->loan_offers[0]->id)) {
                                    $loan_offer_data['offerId'] = $QueueResponse->loan_offers[0]->id;
                                }
                                if (isset($QueueResponse->loan_offers[0]->loanRate) && !empty($QueueResponse->loan_offers[0]->loanRate)) {
                                    $loan_offer_data['loanRate'] = $QueueResponse->loan_offers[0]->loanRate;
                                }
                                if (isset($QueueResponse->loan_offers[0]->loanTerm) && !empty($QueueResponse->loan_offers[0]->loanTerm)) {
                                    $loan_offer_data['loanTerm'] = $QueueResponse->loan_offers[0]->loanTerm;
                                }
                                if (isset($QueueResponse->loan_offers[0]->loanAPR) && !empty($QueueResponse->loan_offers[0]->loanAPR)) {
                                    $loan_offer_data['loanAPR'] = $QueueResponse->loan_offers[0]->loanAPR;
                                }
                                if (isset($QueueResponse->loan_offers[0]->paymentFrequency) && !empty($QueueResponse->loan_offers[0]->paymentFrequency)) {
                                    $loan_offer_data['paymentFrequency'] = $QueueResponse->loan_offers[0]->paymentFrequency;
                                }
                                if (isset($QueueResponse->loan_offers[0]->paymentAmount) && !empty($QueueResponse->loan_offers[0]->paymentAmount)) {
                                    $loan_offer_data['paymentAmount'] = $QueueResponse->loan_offers[0]->paymentAmount;
                                }
                                if (isset($QueueResponse->loan_offers[0]->downPayment) && !empty($QueueResponse->loan_offers[0]->downPayment)) {
                                    $loan_offer_data['downPayment'] = $QueueResponse->loan_offers[0]->downPayment;
                                }
                                if (isset($QueueResponse->loan_offers[0]->showSelectedOfferUrl) && !empty($QueueResponse->loan_offers[0]->showSelectedOfferUrl)) {
                                    $loan_offer_data['showSelectedOfferUrl'] = $QueueResponse->loan_offers[0]->showSelectedOfferUrl;
                                }
                                if (isset($QueueResponse->loan_offers[0]->lenderName) && !empty($QueueResponse->loan_offers[0]->lenderName)) {
                                    $loan_offer_data['lenderName'] = $QueueResponse->loan_offers[0]->lenderName;
                                }
                                $loan_offer_data['loan_application_id'] = $row['clx_loan_application_detail_id'];
                                if (isset($loan_offer_data) && !empty($loan_offer_data)) {
                                    $new_model = Mage::getModel('clxfunding/clxloanofferdetails')->setData($loan_offer_data);
                                    try {
                                        $new_model->save();
                                    } catch (Exception $e) {
                                        Mage::logException($e);
                                    }
                                }
                                /* save loan offer details */

                                $e_applicationId = base64_encode(Mage::helper('core')->encrypt($applicationId));
                                $e_offerId = base64_encode(Mage::helper('core')->encrypt($QueueResponse->loan_offers[0]->id));
                                $templateData = array(
                                    'firstName' => uc_words(strtolower($order['customer_firstname'])),
                                    'lastName' => uc_words(strtolower($order['customer_lastname'])),
                                    'MonthlyPayment' => $QueueResponse->loan_offers[0]->paymentAmount,
                                    'APR' => $QueueResponse->loan_offers[0]->loanAPR,
                                    'Terms' => $QueueResponse->loan_offers[0]->loanTerm,
                                    'orderNumber' => $row['order_id'],
                                    'offerAcceptRedirectUrl' => Mage::getBaseUrl() . 'clxfunding/ClxAPI/loanOfferAccept/applicationId/' . $e_applicationId . '/loanOfferId/' . $e_offerId,
                                    'offerRejectRedirectUrl' => Mage::getBaseUrl() . 'clxfunding/ClxAPI/loanOfferReject/applicationId/' . $e_applicationId . '/loanOfferId/' . $e_offerId
                                );
                                if (isset($QueueResponse->loan_offers[0]->lenderName) && $QueueResponse->loan_offers[0]->lenderName) {
                                    $templateData['lenderName'] = $QueueResponse->loan_offers[0]->lenderName;
                                } else {
                                    $templateData['lenderName'] = 'Lender';
                                }
                                if (isset($QueueResponse->loan_offers[0]->ShowSelectedOfferUrl) && $QueueResponse->loan_offers[0]->ShowSelectedOfferUrl) {
                                    $templateData['MoreDetails'] = '<a href=' . $QueueResponse->loan_offers[0]->ShowSelectedOfferUrl . ' target="_blank">More details</a>';
                                } else {
                                    $templateData['MoreDetails'] = '-';
                                }
                                if (isset($QueueResponse->loan_offers[0]->loanRate) && !empty($QueueResponse->loan_offers[0]->loanRate)) {
                                    $templateData['loanRate'] = $QueueResponse->loan_offers[0]->loanRate.'%';
                                } else {
                                    $templateData['loanRate'] = '-';
                                }
                                

                                /* send loan offer mail notification to customer */
                                try {
                                    Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                            'clx_loan_offerdetail_email_template', $order['customer_email'], uc_words(strtolower($order['customer_firstname'])) . ' ' . uc_words(strtolower($order['customer_lastname'])), 'Loan Offer Details', $templateData
                                    );

                                    $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                                    $connectionWrite->beginTransaction();
                                    $data = array();
                                    $data['status'] = $clxOrderStatus;
                                    $data['update_time'] = date('Y-m-d H:i:s');
                                    $data['offer_mail_sent_time'] = date('Y-m-d H:i:s');
                                    $data['prev_offer_mail_sent_time'] = date('Y-m-d H:i:s');
                                    $data['mail_sent'] = 1;
                                    $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row['clx_loan_application_detail_id']);
                                    $connectionWrite->update($clx_table, $data, $where);
                                    $connectionWrite->commit();
                                } catch (Exception $ex) {
                                    Mage::logException($ex);
                                }
                                /* send loan offer mail notification to customer */
                            }
                        }
                    }
                } 
                else  
                {   
                    /*
                    // response without loan offer
                    if (isset($row['order_id']) && $row['order_id']) {
                        $order = Mage::getModel('sales/order')->loadByIncrementId($row['order_id']);
                        if (isset($order) && !empty($order)) {
                            if ($clxOrderStatus == "FUNDED") {
                                $parameters1 = array(
                                    'applicationID' => $applicationId,
                                    'orderNumber' => $row['order_id'], //$row['order_id'] is order number
                                );
                                Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                        'clx_loan_funded_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Loan Offer Funded', $parameters1
                                );
                            }
                            if (($clxOrderStatus == "REJECTED") || ($clxOrderStatus == "LENDER_REJECTED")) {
                                // Mail notification to Merchant about loan offer rejection 
                                $parameters = array('applicationId' => $applicationId, 'orderNumber' => $row['order_id'], 'customerName' => ucfirst(strtolower($row['firstName'])) . ' ' . ucfirst(strtolower($row['lastName'])));
                                Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                        'clx_loan_offerreject_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Loan Application Rejected', $parameters
                                );
                                // Order Cancel 
                                if (isset($row['order_id']) && !empty($row['order_id'])) {
                                    $orderModel = Mage::getModel('sales/order');
                                    $orderModel->loadByIncrementId($row['order_id']);
                                    if ($orderModel->canCancel()) {
                                        $orderModel->cancel();
                                        $orderModel->setStatus('Reason for cancellation : Lender rejected the loan offer');
                                        $orderModel->save();
                                    }
                                }
                                // Order Cancel 
                            }
                            // update custom table with current application status
                            $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                            $connectionWrite->beginTransaction();
                            $data = array();
                            $data['status'] = $clxOrderStatus;
                            $data['update_time'] = date('Y-m-d H:i:s');
                            $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row['clx_loan_application_detail_id']);
                            $connectionWrite->update($clx_table, $data, $where);
                            $connectionWrite->commit();
                        }
                    }
                    */
                }
            }
        } else {
            Mage::log("CLX Message : Invalid reponse");
        }
    }

    /* this function is store loan application details on order save after create loan application or loan offer accept */

    public function saveLoanApplicationDetails($data) {
        $data['created_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        $model = Mage::getModel('clxfunding/clxloanappdtls')->setData($data);
        try {
            $model->save();
            if (isset($data['offer_acceptance_flag']) && !empty($data['offer_acceptance_flag']) && $data['offer_acceptance_flag']) {
                $last_insert_id = $model->getId();
                if (isset($last_insert_id) && !empty($last_insert_id)) {
                    $loan_offer_details = array();
                    $loan_offer_details['loanTerm'] = $data['loanTerms'];
                    $loan_offer_details['loanAPR'] = $data['loanAPR'];
                    $loan_offer_details['loanRate'] = $data['loanRate'];
                    $loan_offer_details['paymentFrequency'] = $data['paymentFrequency'];
                    $loan_offer_details['paymentAmount'] = $data['paymentAmount'];
                    $loan_offer_details['downPayment'] = $data['downPayment'];
                    $loan_offer_details['offerId'] = $data['loanOfferId'];
                    $loan_offer_details['showSelectedOfferUrl'] = $data['showSelectedOfferUrl'];
                    $loan_offer_details['lenderName'] = $data['lenderName'];
                    $loan_offer_details['loan_application_id'] = $last_insert_id;

                    $loan_offer_model = Mage::getModel('clxfunding/clxloanofferdetails')->setData($loan_offer_details);
                    try {
                        $loan_offer_model->save();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                } else {
                    Mage::log('Clx Error : unable to save loan application details');
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function cancelOrderAfterTimeExpire() {
        $table_prefix = Mage::getConfig()->getTablePrefix();
        $clx_table = $table_prefix . 'clx_loan_application_detail';
        $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $connectionRead->select()
                ->from($clx_table, array('*'))
                ->where('approvalRequired_flag=1 and status!="FUNDED" and status!="LENDER_REJECTED" and status!="CUSTOMER_REJECTED" and status!="CUSTOMER_ACCEPTED" and status!="MERCHANT_REJECTED" and status!="REJECTED" and mail_sent=1');
        $row = $connectionRead->fetchAll($select);   //return rows

        if (isset($row) && !empty($row) && is_array($row)) {
            for ($i = 0; $i < count($row); $i++) {
                if (isset($row[$i]['offer_mail_sent_time']) && !is_null($row[$i]['offer_mail_sent_time']) && isset($row[$i]['prev_offer_mail_sent_time']) && !is_null($row[$i]['prev_offer_mail_sent_time']) && isset($row[$i]['approvalRequired_flag']) && isset($row[$i]['status']) && $row[$i]['approvalRequired_flag'] == 1 && $row[$i]['status'] != 'CUSTOMER_REJECTED' && isset($row[$i]['order_id']) && !empty($row[$i]['order_id'])) {
                    $timeframe = Mage::getStoreConfig('payment/clxfunding/loan_offer_time_frame');
                    $daily_mails_per_day = 1;

                    ($daily_mails_per_day > 0) ? $daily_mails_per_day = round($daily_mails_per_day) : $daily_mails_per_day = 1; //Value should not be less than 1

                    $time_frame_in_hours = $timeframe * 24;
                    $mail_sent_datetime = $row[$i]['offer_mail_sent_time'];
                    $prev_mail_sent_datetime = $row[$i]['prev_offer_mail_sent_time'];

                    $current_datetime = date('Y-m-d H:i:s');
                    $m_hours = round((strtotime($current_datetime) - strtotime($mail_sent_datetime)) / 3600, 1);
                    $daily_hours = round((strtotime($current_datetime) - strtotime($prev_mail_sent_datetime)) / 3600, 1);


                    if (isset($m_hours) && isset($time_frame_in_hours) && isset($daily_hours) && $m_hours >= $time_frame_in_hours) {
                        $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $connectionWrite->beginTransaction();
                        $data = array();
                        $data['status'] = "MERCHANT_REJECTED";
                        $data['update_time'] = date('Y-m-d H:i:s');

                        $orderModel = Mage::getModel('sales/order');
                        $orderModel->loadByIncrementId($row[$i]['order_id']);
                        if ($orderModel->canCancel()) {
                            $orderModel->cancel();
                            $orderModel->setStatus('canceled_pendings');
                            $orderModel->save();
                        }
                        $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row[$i]['clx_loan_application_detail_id']);
                        $connectionWrite->update($clx_table, $data, $where);
                        $connectionWrite->commit();


                        $parameters = array('applicationId' => $row[$i]['application_id'], 'orderNumber' => $row[$i]['order_id']);
                        Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                'clx_auto_order_cancel_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Clx Auto Order Canceled', $parameters
                        );
                    } else if (isset($m_hours) && isset($time_frame_in_hours) && isset($daily_hours) && $m_hours < $time_frame_in_hours && $daily_hours >= (24 / $daily_mails_per_day)) {
                        $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
                        $select = $connectionRead->select()
                                ->from($table_prefix . 'clx_loan_offer_detail', array('*'))
                                ->where('loan_application_id=?', $row[$i]['clx_loan_application_detail_id']);

                        $customer_loan_offer_row = $connectionRead->fetchRow($select);   //return rows

                        if (isset($customer_loan_offer_row) && !empty($customer_loan_offer_row)) {
                            if (isset($customer_loan_offer_row['clx_loan_offer_detail_id'])) {
                                $order_h = Mage::getModel('sales/order')->loadByIncrementId($row[$i]['order_id']);
                                if (isset($order_h) && !empty($order_h)) {
                                    $e_applicationId = base64_encode(Mage::helper('core')->encrypt($row[$i]['application_id']));
                                    $e_offerId = base64_encode(Mage::helper('core')->encrypt($customer_loan_offer_row['offerId']));
                                    $templateData = array(
                                        'firstName' => uc_words(strtolower($order_h['customer_firstname'])),
                                        'lastName' => uc_words(strtolower($order_h['customer_lastname'])),
                                        'MonthlyPayment' => $customer_loan_offer_row['paymentAmount'],
                                        'loanRate' => $customer_loan_offer_row['loanRate'],
                                        'APR' => $customer_loan_offer_row['loanAPR'],
                                        'Terms' => $customer_loan_offer_row['loanTerm'],
                                        'orderNumber' => $row[$i]['order_id'],
                                        'offerAcceptRedirectUrl' => Mage::getBaseUrl() . 'clxfunding/ClxAPI/loanOfferAccept/applicationId/' . $e_applicationId . '/loanOfferId/' . $e_offerId,
                                        'offerRejectRedirectUrl' => Mage::getBaseUrl() . 'clxfunding/ClxAPI/loanOfferReject/applicationId/' . $e_applicationId . '/loanOfferId/' . $e_offerId
                                    );
                                    if (isset($customer_loan_offer_row['lenderName']) && !empty($customer_loan_offer_row['lenderName'])) {
                                        $templateData['lenderName'] = $customer_loan_offer_row['lenderName'];
                                    } else {
                                        $templateData['lenderName'] = 'Lender';
                                    }
                                    if (isset($customer_loan_offer_row["ShowSelectedOfferUrl"]) && !empty($customer_loan_offer_row["ShowSelectedOfferUrl"])) {
                                        $templateData['MoreDetails'] = '<a href=' . $customer_loan_offer_row["ShowSelectedOfferUrl"] . ' target="_blank">More details</a>';
                                    } else {
                                        $templateData['MoreDetails'] = '-';
                                    }

                                    try {

                                        Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                                'clx_loan_offerdetail_email_template', $order_h['customer_email'], uc_words(strtolower($order_h['customer_firstname'])) . ' ' . uc_words(strtolower($order_h['customer_lastname'])), 'Loan Offer Details', $templateData
                                        );

                                        $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                                        $connectionWrite->beginTransaction();
                                        $data = array();
                                        $data['update_time'] = date('Y-m-d H:i:s');
                                        $data['prev_offer_mail_sent_time'] = date('Y-m-d H:i:s');
                                        $data['mail_sent'] = 1;
                                        $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row[$i]['clx_loan_application_detail_id']);
                                        $connectionWrite->update($clx_table, $data, $where);
                                        $connectionWrite->commit();
                                    } catch (Exception $ex) {
                                        Mage::logException($ex);
                                    }
                                }
                            }
                        }
                    } else {
                        
                    }
                }
            }
        }
    }
    
    public function aPILoanApplcationStatus()
    {
        $table_prefix = Mage::getConfig()->getTablePrefix();
        $clx_table = $table_prefix . 'clx_loan_application_detail';
        $accountId = Mage::getStoreConfig('payment/clxfunding/account_id');
        $authorizationField = Mage::getStoreConfig('payment/clxfunding/authorization_field');
        $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
        $select = $connectionRead->select()
                ->from($clx_table, array('*'))
                ->where('status!="FUNDED" and status!="LENDER_REJECTED" and status!="REJECTED" and status!="CUSTOMER_REJECTED" and status!="MERCHANT_REJECTED"');
        $allLoanOrders = $connectionRead->fetchAll($select);   //return rows
        foreach ($allLoanOrders as $row) {
            if (isset($row) && !empty($row) && isset($row['clx_loan_application_detail_id'])) {
                $applicationId = $row['application_id'];
                if(isset($applicationId) && !empty($applicationId))
                {
                    $API_Response_Arr = Mage::helper('clxfunding')->mastercurlRequestToClxLoanStatusApi($applicationId,$authorizationField);
                    if(isset($API_Response_Arr) && !empty($API_Response_Arr) && isset($API_Response_Arr->application_status) && !empty($API_Response_Arr->application_status) && isset($API_Response_Arr->source_application_id) && !empty($API_Response_Arr->source_application_id))
                    {
                        $clxOrderStatus = $API_Response_Arr->application_status;
                        $sourceApplicationId = $API_Response_Arr->source_application_id;
                        /*$tmpvar = explode('-',$source_application_id);
                        $sourceApplicationId = $tmpvar[0].'-'.ltrim($tmpvar[1], '0');*/
                        if($applicationId == $sourceApplicationId)
                        {
                            if (isset($row['order_id']) && !empty($row['order_id'])) {
                                $order = Mage::getModel('sales/order')->loadByIncrementId($row['order_id']);
                                if (isset($order) && !empty($order)) {
                                    if ($clxOrderStatus == "FUNDED") {
                                        $parameters1 = array(
                                            'applicationID' => $applicationId,
                                            'orderNumber' => $row['order_id'], //$row['order_id'] is order number
                                        );
                                        /* send mail to merchant */
                                        Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                                'clx_loan_funded_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Loan Offer Funded', $parameters1
                                        );
                                        /* send mail to user */
                                        Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                                'clx_loan_funded_user_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), $row['emailId'], 'Loan Offer Funded', $parameters1
                                        );
                                    }
                                    if (($clxOrderStatus == "REJECTED") || ($clxOrderStatus == "LENDER_REJECTED")) {
                                        /* Mail notification to Merchant about loan offer rejection */
                                        $parameters = array('applicationId' => $applicationId, 'orderNumber' => $row['order_id'], 'customerName' => ucfirst(strtolower($row['firstName'])) . ' ' . ucfirst(strtolower($row['lastName'])));
                                        Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                                'clx_loan_offerreject_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Loan Application Rejected', $parameters
                                        );

                                        /* Order Cancel */
                                        if (isset($row['order_id']) && !empty($row['order_id'])) {
                                            $orderModel = Mage::getModel('sales/order');
                                            $orderModel->loadByIncrementId($row['order_id']);
                                            if ($orderModel->canCancel()) {
                                                $orderModel->cancel();
                                                $orderModel->setStatus('Reason for cancellation : Lender rejected the loan offer');
                                                $orderModel->save();
                                            }
                                        }
                                        /* Order Cancel */
                                    }
                                    /* update custom table with current application status */
                                    $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                                    $connectionWrite->beginTransaction();
                                    $data = array();
                                    $data['status'] = $clxOrderStatus;
                                    $data['update_time'] = date('Y-m-d H:i:s');
                                    $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row['clx_loan_application_detail_id']);
                                    $connectionWrite->update($clx_table, $data, $where);
                                    $connectionWrite->commit();
                                }
                            }
                        }
                    }
                }
            }
        }
        
    }
}
