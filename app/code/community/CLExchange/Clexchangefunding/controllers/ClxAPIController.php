<?php

class CLExchange_Clexchangefunding_ClxAPIController extends Mage_Core_Controller_Front_Action {
    /* function will receive loan application form data on click of next,previous,check eligibility,submit button */
    public function loanAppProcessingAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $accountId = Mage::getStoreConfig('payment/clxfunding/account_id'); //get configured accountID from core_config_data
            $authorizationField = Mage::getStoreConfig('payment/clxfunding/authorization_field'); //get configured authorization from core_config_data
            $quote = Mage::getModel('checkout/session')->getQuote();
            $quoteData = $quote->getData();

            $grandTotal = $quoteData['grand_total'];

            if (isset($accountId) && isset($authorizationField)) {
                $formData = array(
                    "firstName" => trim($this->getRequest()->getParam('firstName')),
                    "lastName" => trim($this->getRequest()->getParam('lastName')),
                    "emailId" => trim($this->getRequest()->getParam('emailId')),
                    "birthDate" => trim($this->getRequest()->getParam('birthDate')),
                    "street" => trim($this->getRequest()->getParam('street')),
                    "city" => trim($this->getRequest()->getParam('city')),
                    "country" => trim($this->getRequest()->getParam('country')),
                    "state" => trim($this->getRequest()->getParam('state')),
                    "zipcode" => trim($this->getRequest()->getParam('zipcode')),
                    "loanAmount" => floatval($grandTotal),
                    "loanPurpose" => trim($this->getRequest()->getParam('loanPurpose')),
                    "loanTerms" => trim($this->getRequest()->getParam('loanTerms')),
                    "yearlyIncome" => trim($this->getRequest()->getParam('yearlyIncome')),
                    "employmentStatus" => trim($this->getRequest()->getParam('employmentStatus')),
                    "selfReportedCreditScore" => trim($this->getRequest()->getParam('selfReportedCreditScore')),
                    "mobilePhoneAreaCode" => trim($this->getRequest()->getParam('mobilePhoneAreaCode')),
                    "mobileNumber" => trim($this->getRequest()->getParam('mobileNumber')),
                    "bankName" => trim($this->getRequest()->getParam('bankName')),
                    "firstAccountHolderName" => trim($this->getRequest()->getParam('firstAccountHolderName')),
                    "bankAccountType" => trim($this->getRequest()->getParam('bankAccountType')),
                    "bankAccountNumber" => trim($this->getRequest()->getParam('bankAccountNumber')),
                    "routingNumber" => trim($this->getRequest()->getParam('routingNumber')),
                    "accountId" => $accountId,
                    "currency" => Mage::app()->getStore()->getCurrentCurrencyCode()
                );
                $ssn = $this->getRequest()->getParam('ssn');
                if(isset($ssn)){
                    $formData['ssn']  = trim($ssn);
                }
                
                if(isset($formData['employmentStatus']) && $formData['employmentStatus']!='Not Employed'){
                    $employerName = $this->getRequest()->getParam('employerName');
                    if(isset($employerName))
                    {
                        $formData['employerName'] = trim($employerName);
                    }
                    $employmentStartDate = $this->getRequest()->getParam('employmentStartDate');
                    if(isset($employmentStartDate)){
                        $formData['employmentStartDate'] = trim($employmentStartDate);
                    }
                    $occupation = $this->getRequest()->getParam('occupation');
                    if(isset($occupation)){
                        $formData['occupation']  = trim($occupation);
                    }
                }
                
                
                $clx_button_flag = trim($this->getRequest()->getParam('clx_btn_flag'));
                $validation_error = array();
                /* validators */

                $is_digit = new Zend_Validate_Digits();
                $date_validator = new Zend_Validate_Date(); // date yyyy-mm-dd
                $is_decimal = new Zend_Validate_Float(); //
                $string_length_min_max_3 = new Zend_Validate_StringLength(array('max' => 3)); //mobilePhoneAreaCode
                $string_length_min_max_10 = new Zend_Validate_StringLength(array('max' => 10)); //mobileNumber
                $string_length_max_8 = new Zend_Validate_StringLength(array('max' => 8)); //zipcode
                $string_length_max_100 = new Zend_Validate_StringLength(array('max' => 100)); //employerName(100max)
                $string_length_max_255 = new Zend_Validate_StringLength(array('max' => 255)); //bankName(255max)
                $string_length_max_30 = new Zend_Validate_StringLength(array('max' => 30)); //bankAccountNumber(30max)
                $string_length_min_max_9 = new Zend_Validate_StringLength(array('min' => 9, 'max' => 9));
                $tab1_fields_key_arr = array("firstName", "lastName", "emailId", "birthDate", "mobilePhoneAreaCode", "mobileNumber", "street", "state", "city", "country", "zipcode", "yearlyIncome", "employmentStatus", "employmentStatus", "employerName", "employmentStartDate", "occupation");
                $tab2_fields_key_arr = array("selfReportedCreditScore", "bankName", "firstAccountHolderName", "bankAccountType", "bankAccountNumber", "routingNumber", "ssn");
                $tab3_fields_key_arr = array("loanAmount", "loanPurpose", "loanTerms");

                /* Personal Information */
                /* firstName */
                if (!Zend_Validate::is($formData['firstName'], 'NotEmpty')) {
                    $validation_error['firstName'] = "This field is required";
                }

                /* lastName */
                if (!Zend_Validate::is($formData['lastName'], 'NotEmpty')) {
                    $validation_error['lastName'] = "This field is required";
                }

                /* emailId */
                if (!Zend_Validate::is($formData['emailId'], 'NotEmpty')) {
                    $validation_error['emailId'] = "This field is required";
                } else {
                    if (!Zend_Validate::is($formData['emailId'], 'EmailAddress')) {
                        $validation_error['emailId'] = "Invalid Email Address";
                    }
                }

                /* birthDate */
                if (!Zend_Validate::is($formData['birthDate'], 'NotEmpty')) {
                    $validation_error['birthDate'] = "This field is required";
                } else {
                    if (!$date_validator->isValid($formData['birthDate'])) {
                        $validation_error['birthDate'] = "Invalid date format";
                    }
                    $currentDate = date('Y-m-d');
                    if (strtotime($currentDate) > strtotime($formData['birthDate'])) {
                        $cl_age = Mage::helper('clxfunding')->getAge($formData['birthDate'], $currentDate);
                        if ($cl_age < 18) {
                            $validation_error['birthDate'] = "Age should be above 18";
                        }
                    } else {
                        $validation_error['birthDate'] = "Invalid birthDate";
                    }
                }

                /* mobilePhoneAreaCode */
                if (!Zend_Validate::is($formData['mobilePhoneAreaCode'], 'NotEmpty')) {
                    $validation_error['mobilePhoneAreaCode'] = "This field is required";
                } else {
                    if (!$string_length_min_max_3->isValid($formData['mobilePhoneAreaCode'])) {// 3digits only
                        $validation_error['mobilePhoneAreaCode'] = "This field should contain 3 digits only";
                    }
                }
                /* mobileNumber */
                if (!Zend_Validate::is($formData['mobileNumber'], 'NotEmpty')) {
                    $validation_error['mobileNumber'] = "This field is required";
                } else {
                    if ($is_digit->isValid($formData['mobileNumber'])) { //digits
                        if (!$string_length_min_max_10->isValid($formData['mobileNumber'])) {// 10 digits only
                            $validation_error['mobileNumber'] = "This field should contain 10 digits only";
                        }
                    } else {
                        $validation_error['mobileNumber'] = "This field should contain 10 digits only";
                    }
                }

                /* street */
                if (!Zend_Validate::is($formData['street'], 'NotEmpty')) {
                    $validation_error['street'] = "This field is required";
                }

                /* city */
                if (!Zend_Validate::is($formData['city'], 'NotEmpty')) {
                    $validation_error['city'] = "This field is required";
                }

                /* state */
                if (!Zend_Validate::is($formData['state'], 'NotEmpty')) {
                    $validation_error['state'] = "This field is required";
                } else {
                    /*if (!in_array($formData['state'], $states_a)) {
                        $validation_error['state'] = "Not a valid state";
                    }*/
                }

                /* zipcode */
                if (!Zend_Validate::is($formData['zipcode'], 'NotEmpty')) {
                    $validation_error['zipcode'] = "This field is required";
                } else {
                    if (!$string_length_max_8->isValid($formData['zipcode'])) {// 10 digits only
                        $validation_error['zipcode'] = "This field should contain maximum 8 digits";
                    }
                }
                /* country */
                if (isset($formData['country'])) {
                    if (!Zend_Validate::is($formData['country'], 'NotEmpty')) {
                        $validation_error['country'] = "This field is required";
                    }
                }

                /* yearlyIncome */
                if (!Zend_Validate::is($formData['yearlyIncome'], 'NotEmpty')) {
                    $validation_error['yearlyIncome'] = "This field is required";
                } else {
                    if (!$is_decimal->isValid($formData['yearlyIncome'])) {
                        $validation_error['yearlyIncome'] = "This field contain decimal value only";
                    }
                }

                /* employmentStatus */
                if (!Zend_Validate::is($formData['employmentStatus'], 'NotEmpty')) {
                    $validation_error['employmentStatus'] = "This field is required";
                } 
                /* employerName */
                if(isset($formData['employerName'])){
                    if (!Zend_Validate::is($formData['employerName'], 'NotEmpty')) {
                        $validation_error['employerName'] = "This field is required";
                    } else {
                        if (!$string_length_max_100->isValid($formData['employerName'])) {// string length <=100 
                            $validation_error['employerName'] = "String length should be less than 100";
                        }
                    }
                }

                /* employmentStartDate */
                if(isset($formData['employmentStartDate'])){
                    if (!Zend_Validate::is($formData['employmentStartDate'], 'NotEmpty')) {
                        $validation_error['employmentStartDate'] = "This field is required";
                    } else {
                        if (!$date_validator->isValid($formData['employmentStartDate'])) {
                            $validation_error['employmentStartDate'] = "Invalid date format";
                        }
                    }
                }

                /* occupation */
                if(isset($formData['occupation'])){
                    if (!Zend_Validate::is($formData['occupation'], 'NotEmpty')) {
                        $validation_error['occupation'] = "This field is required";
                    }
                }

                /* End-  Personal Information */

                /* Bank Details & SSN tab fields */

                /* selfReportedCreditScore */
                if (!Zend_Validate::is($formData['selfReportedCreditScore'], 'NotEmpty')) {
                    $validation_error['selfReportedCreditScore'] = "This field is required";
                }
                /* bankName */
                if (!Zend_Validate::is($formData['bankName'], 'NotEmpty')) {
                    $validation_error['bankName'] = "This field is required";
                } else {
                    if (!$string_length_max_255->isValid($formData['bankName'])) {// string length <=255
                        $validation_error['bankName'] = "String length should be less than 255";
                    }
                }
                /* firstAccountHolderName */
                if (!Zend_Validate::is($formData['firstAccountHolderName'], 'NotEmpty')) {
                    $validation_error['firstAccountHolderName'] = "This field is required";
                } else {
                    if (!$string_length_max_255->isValid($formData['firstAccountHolderName'])) {// string length <=255
                        $validation_error['firstAccountHolderName'] = "String length should be less than 255";
                    }
                }
                /* bankAccountType */
                if (!Zend_Validate::is($formData['bankAccountType'], 'NotEmpty')) {
                    $validation_error['bankAccountType'] = "This field is required";
                }

                /* bankAccountNumber */
                if (!Zend_Validate::is($formData['bankAccountNumber'], 'NotEmpty')) {
                    $validation_error['bankAccountNumber'] = "This field is required";
                } else {
                    if (!$string_length_max_30->isValid($formData['bankAccountNumber'])) {// string length <=255
                        $validation_error['bankAccountNumber'] = "Invalid bank account number";
                    }
                }
                /* routingNumber */
                if (!Zend_Validate::is($formData['routingNumber'], 'NotEmpty')) {
                    $validation_error['routingNumber'] = "This field is required";
                } else {
                    if (!$string_length_max_255->isValid($formData['routingNumber'])) {// string length <=255
                        $validation_error['routingNumber'] = "Invalid routing number";
                    }
                }


                /* ssn */
                if(isset($formData['ssn'])){
                    if (!Zend_Validate::is($formData['ssn'], 'NotEmpty')) {
                        $validation_error['ssn'] = "This field is required";
                    } else {
                        if(!(preg_match("/^\d{9}$/",$formData['ssn'])))
                        {
                            $validation_error['ssn'] = "Please enter a valid 9 digit SSN (for example xxxxxxxxx)";
                        }
                    }
                }

                /* End- Bank Details & SSN tab fields */

                /* Confirmation & submit tab fields */

                /* loanAmount */
                if (!Zend_Validate::is($formData['loanAmount'], 'NotEmpty')) {
                    $validation_error['loanAmount'] = "This field is required";
                } else {
                    if (!$is_decimal->isValid($formData['loanAmount'])) {
                        $validation_error['loanAmount'] = "Invalid loan amount";
                    }
                }

                /* loanPurpose */
                if (!Zend_Validate::is($formData['loanPurpose'], 'NotEmpty')) {
                    $validation_error['loanPurpose'] = "This field is required";
                } 

                /* loanTerms */
                if (!Zend_Validate::is($formData['loanTerms'], 'NotEmpty')) {
                    $validation_error['loanTerms'] = "This field is required";
                } else {
                    if (!$is_decimal->isValid($formData['loanTerms'])) {
                        $validation_error['loanTerms'] = "This field contain decimal value only";
                    }
                }

                /* End - Confirmation & submit tab fields */

                /* If form contain validation errors then ajax will respond with error array */
                if (isset($validation_error) && !empty($validation_error) && count($validation_error)) {
                    $tab_flag = array();
                    for ($i = 1; $i <= 3; $i++) {
                        for ($j = 0; $j < count(${"tab" . $i . "_fields_key_arr"}); $j++) {
                            if (isset($validation_error[${"tab" . $i . "_fields_key_arr"}[$j]]) && !empty($validation_error[${"tab" . $i . "_fields_key_arr"}[$j]])) {
                                array_push($tab_flag, $i);
                                break;
                            }
                        }
                    }
                    echo json_encode(array('valid' => FALSE, 'error_messages' => $validation_error, 'flag' => 'C', 'tab_flag' => $tab_flag));
                }// else loan application form with no validation error
                else {
                    if (isset($clx_button_flag) && !empty($clx_button_flag)) {
                        if ($clx_button_flag == 'check_eligibility') {
                            echo json_encode(array('valid' => TRUE, 'flag' => '2', 'redirect_url' => Mage::getBaseUrl() . 'clxfunding/ClxAPI/clxApiRequest'));
                        } else if ($clx_button_flag == 'loan_apply') {
                            echo json_encode(array('valid' => TRUE, 'flag' => '3', 'redirect_url' => Mage::getBaseUrl() . 'clxfunding/ClxAPI/clxApiRequest'));
                        } else {
                            echo json_encode(array('valid' => TRUE, 'flag' => '1', 'redirect_url' => 'undefined'));
                        }
                    } else {
                        echo json_encode(array('valid' => FALSE));
                    }
                }
            } else {
                Mage::log('CLX Error : Merchant not configured with clx funding');
                echo json_encode(array('valid' => FALSE,'flag'=>FALSE));
            }
        }
    }

    public function clxApiRequestAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $accountId = Mage::getStoreConfig('payment/clxfunding/account_id');
            $authorizationField = Mage::getStoreConfig('payment/clxfunding/authorization_field');
            $isBestOffer = Mage::getStoreConfig('payment/clxfunding/is_Best_Offer');
            $ApprovalRequired = Mage::getStoreConfig('payment/clxfunding/approve_Required');

            $quote = Mage::getModel('checkout/session')->getQuote();
            $quoteData = $quote->getData();
            $grandTotal = $quoteData['grand_total'];

            $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
            
            $clx_button_flag = trim($this->getRequest()->getParam('clx_btn_flag'));
            
            if (isset($accountId) && isset($authorizationField)) {
                /* loan application form data */
                $data = array(
                    "firstName" => trim($this->getRequest()->getParam('firstName')),
                    "lastName" => trim($this->getRequest()->getParam('lastName')),
                    "emailId" => trim($this->getRequest()->getParam('emailId')),
                    "birthDate" => trim($this->getRequest()->getParam('birthDate')),
                    "street" => trim($this->getRequest()->getParam('street')),
                    "city" => trim($this->getRequest()->getParam('city')),
                    "country" => trim($this->getRequest()->getParam('country')),
                    "state" => trim($this->getRequest()->getParam('state')),
                    "zipcode" => trim($this->getRequest()->getParam('zipcode')),
                    "loanAmount" => floatval($grandTotal),
                    "loanPurpose" => trim($this->getRequest()->getParam('loanPurpose')),
                    "loanTerms" => floatval(trim($this->getRequest()->getParam('loanTerms'))),
                    "yearlyIncome" => floatval(trim($this->getRequest()->getParam('yearlyIncome'))),
                    "employmentStatus" => trim($this->getRequest()->getParam('employmentStatus')),
                    "selfReportedCreditScore" => intval(trim($this->getRequest()->getParam('selfReportedCreditScore'))),
                    "mobilePhoneAreaCode" => trim($this->getRequest()->getParam('mobilePhoneAreaCode')),
                    "mobileNumber" => trim($this->getRequest()->getParam('mobileNumber')),
                    "bankName" => trim($this->getRequest()->getParam('bankName')),
                    "firstAccountHolderName" => trim($this->getRequest()->getParam('firstAccountHolderName')),
                    "bankAccountType" => trim($this->getRequest()->getParam('bankAccountType')),
                    "bankAccountNumber" => trim($this->getRequest()->getParam('bankAccountNumber')),
                    "routingNumber" => trim($this->getRequest()->getParam('routingNumber')),
                    "currency" => Mage::app()->getStore()->getCurrentCurrencyCode()
                );
                $ssn = $this->getRequest()->getParam('ssn');
                if(isset($ssn)){
                    $data['ssn']  = trim($ssn);
                }
                
                if(isset($data['employmentStatus']) && $data['employmentStatus']!='Not Employed'){
                    $employerName = $this->getRequest()->getParam('employerName');
                    if(isset($employerName))
                    {
                        $data['employerName'] = trim($employerName);
                    }
                    $employmentStartDate = $this->getRequest()->getParam('employmentStartDate');
                    if(isset($employmentStartDate)){
                        $data['employmentStartDate'] = trim($employmentStartDate);
                    }
                    $occupation = $this->getRequest()->getParam('occupation');
                    if(isset($occupation)){
                        $data['occupation']  = trim($occupation);
                    }
                }
                
                
                $applicationId = "APP-".$data['emailId']."-".strtotime(date('Y-m-d H:i:s'));
                $data['applicationId'] = $applicationId;
                /* to save loan application form data to custom clx table */
                $clx_loan_application_data = $data;
                $clx_loan_application_data['application_id'] = $applicationId;
                $clx_loan_application_data['applicationId'] = $applicationId;
                $clx_loan_application_data['quote_id'] = $quoteId;
                Mage::getSingleton('checkout/session')->setLoanApplicationDataToSave($clx_loan_application_data);
                Mage::getSingleton('checkout/session')->setLoanApplicationId($applicationId);
                /* to save loan application form data to custom clx table */
                
                if (isset($clx_button_flag) && !empty($clx_button_flag)) {
                    if ($clx_button_flag == 'check_eligibility') {
                        $data['isBestOffer'] = (isset($isBestOffer) && $isBestOffer=="1")?TRUE:FALSE;
                        $result = Mage::helper('clxfunding')->masterCurlRequestToClxAPIs("get_loan_offer",$data,FALSE,FALSE);
                        
                        if (isset($result['valid']) && $result['valid']) {
                            if (isset($result['result'])) {
                                $api_response = json_decode($result['result']);
                                if (isset($api_response->application_status) && $api_response->application_status == "REJECTED") {
                                    if (isset($api_response->errors[0]) && !empty($api_response->errors[0])) {
                                        if (isset($api_response->errors[0]->errorCode) && !empty($api_response->errors[0]->errorCode) && $api_response->errors[0]->errorCode == 'UNAUTHORIZED') {
                                            $errorMessage = "Either authorization key is invalid or no account exist for passed accountId";
                                            if (isset($api_response->errors[0]->errorMessage) && !empty($api_response->errors[0]->errorMessage)) {
                                                $errorMessage = $api_response->errors[0]->errorMessage;
                                            }
                                            try {
                                                Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                                        'clx_error_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Clx Error', array('clxError' => $errorMessage)
                                                );
                                            } catch (Exception $ex) {
                                                Mage::logException($ex);
                                            }
                                        }
                                    }
                                }
                                
                                $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/loan_offer_response.phtml'));
                                $block->setLoanOffers(json_decode($result['result']));
                                $myHtml = $block->toHtml();
                                echo json_encode(array('valid' => TRUE, 'offer_view' => $myHtml));
                            }
                        } else {
                            Mage::log('CLX Error : Loan offer api has request error ');
                            Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
                        }
                    } else {
                        $result = Mage::helper('clxfunding')->masterCurlRequestToClxAPIs("create_application",$data,FALSE,FALSE);
                        if (isset($result['valid']) && $result['valid']) {
                            
                            if (isset($result['result'])) {
                                $api_response = json_decode($result['result']);
                                if (isset($api_response->application_status) && $api_response->application_status == "REJECTED") {
                                    if (isset($api_response->errors[0]) && !empty($api_response->errors[0])) {
                                        if (isset($api_response->errors[0]->errorCode) && !empty($api_response->errors[0]->errorCode) && $api_response->errors[0]->errorCode == 'UNAUTHORIZED') {
                                            $errorMessage = "Either authorization key is invalid or no account exist for passed accountId";
                                            if (isset($api_response->errors[0]->errorMessage) && !empty($api_response->errors[0]->errorMessage)) {
                                                $errorMessage = $api_response->errors[0]->errorMessage;
                                            }
                                            try {
                                                Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                                        'clx_error_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Clx Error', array('clxError' => $errorMessage)
                                                );
                                            } catch (Exception $ex) {
                                                Mage::logException($ex);
                                            }
                                        }
                                    }
                                }
                                $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/loanapplicationstatus.phtml'));
                                $block->setLoanApplication(json_decode($result['result']));
                                $block->setApprovalRequired($ApprovalRequired);// Approval Required
                                $myHtml = $block->toHtml();
                                echo json_encode(array('valid' => TRUE, 'offer_view' => $myHtml));
                            }
                        } else {
                            Mage::log('CLX Error : Loan application api has request error ');
                            Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
                        }
                    }
                }
            }
            else
            {
                echo json_encode(array('valid' => FALSE));
            }
        } else {
            Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
        }
    }

    public function storeSelectedLoanOfferAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $data = serialize($this->getRequest()->getParams());
            $clx_btn_flag = $this->getRequest()->getParam('clx_btn_flag');
            $loanOfferId = $this->getRequest()->getParam('loanOfferId');
            if (isset($clx_btn_flag) && !empty($clx_btn_flag) && $clx_btn_flag == "check_eligibility" && isset($loanOfferId) && !empty($loanOfferId) && isset($data) && !empty($data)) {
                Mage::getSingleton('checkout/session')->setLoanApplicationData($data);
                Mage::getSingleton('checkout/session')->setLoanOfferFlag(true);
                $check_set = Mage::getSingleton('checkout/session')->getLoanOfferFlag();
                if (isset($check_set)) {
                    echo json_encode(array('valid' => TRUE));
                } else {
                    echo json_encode(array('valid' => FALSE));
                }
            } else {
                echo json_encode(array('valid' => FALSE));
            }
        } else {
            Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
        }
    }

    public function setLoanApplicationSessAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {

            Mage::getSingleton('checkout/session')->setLoanApplicationFlag(true);
            $check_session_set = Mage::getSingleton('checkout/session')->getLoanApplicationFlag();
            if (isset($check_session_set) && $check_session_set) {
                echo json_encode(array('valid' => TRUE));
            } else {
                echo json_encode(array('valid' => FALSE));
            }
        } else {
            Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
        }
    }

    function saveStausAction() {
        $LoanOfferFlag = Mage::getSingleton('checkout/session')->getLoanOfferFlag(); // session set to track flow of loan offer 
        $loanApplicationFlag = Mage::getSingleton('checkout/session')->getLoanApplicationFlag(); // session set to track flow of loan application
        Mage::getSingleton('checkout/session')->unsLoanOfferFlag();
        Mage::getSingleton('checkout/session')->unsLoanApplicationFlag();

        if (isset($LoanOfferFlag) && $LoanOfferFlag){
            $formData = unserialize(Mage::getSingleton('checkout/session')->getLoanApplicationData());
            if (isset($formData) && !empty($formData)){
                
                $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
                $quote = Mage::getModel('checkout/session')->getQuote();                
                $quoteData = $quote->getData();
                if (isset($quoteData['grand_total'])) {
                    $grandTotal = $quoteData['grand_total'];
                }
                $data = array(
                    "firstName" => (string)trim($formData['firstName']),
                    "lastName" => (string)trim($formData['lastName']),
                    "emailId" => (string)trim($formData['emailId']),
                    "birthDate" => (string)trim($formData['birthDate']),
                    "street" => (string)trim($formData['street']),
                    "city" => (string)trim($formData['city']),
                    "country" => (string)trim($formData['country']),
                    "state" => (string)trim($formData['state']),
                    "zipcode" => (string)trim($formData['zipcode']),
                    "loanAmount" => floatval($formData['loanAmount']),
                    "loanPurpose" => (string)trim($formData['loanPurpose']),
                    "loanTerms" => floatval(trim($formData['loanTerms'])),
                    "yearlyIncome" => floatval(trim($formData['yearlyIncome'])),
                    "employmentStatus" => (string)trim($formData['employmentStatus']),
                    "selfReportedCreditScore" => intval(trim($formData['selfReportedCreditScore'])),
                    "mobilePhoneAreaCode" => (string)trim($formData['mobilePhoneAreaCode']),
                    "mobileNumber" => (string)trim($formData['mobileNumber']),
                    "bankName" => (string)trim($formData['bankName']),
                    "firstAccountHolderName" => (string)trim($formData['firstAccountHolderName']),
                    "bankAccountType" => (string)trim($formData['bankAccountType']),
                    "bankAccountNumber" => (string)trim($formData['bankAccountNumber']),
                    "routingNumber" => (string)trim($formData['routingNumber']),
                    "loanOfferId" => (string)trim($formData['loanOfferId']),
                    "currency" => (string)Mage::app()->getStore()->getCurrentCurrencyCode()
                );
                if(isset($formData['ssn'])){
                    $data['ssn'] = $formData['ssn'];
                }
                if(isset($formData['employmentStatus']) && $formData['employmentStatus']!='Not Employed'){
                    $data["employmentStartDate"] = trim($formData['employmentStartDate']);
                    $data["occupation"] = trim($formData['occupation']);
                    $data["employerName"] = trim($formData['employerName']);
                }

                $url = "http://clexchange-dev.herokuapp.com/api/loan-offers/" . $data['loanOfferId'] . "/loan-applications";
                $page = "/api/loan-offers/" . $data['loanOfferId'] . "/loan-applications";
                $data['isOfferAccepted'] = TRUE;

                $applicationId = (string)Mage::getSingleton('checkout/session')->getLoanApplicationId();
                
                $formData['application_id'] = $applicationId;
                $data["applicationId"]= $applicationId;
                $formData['quote_id'] = $quoteId;
                Mage::getSingleton('checkout/session')->setLoanApplicationDataToSave($formData);
                $result = Mage::helper('clxfunding')->masterCurlRequestToClxAPIs("loan_offer_accept_or_reject",$data,$data['loanOfferId'],FALSE);
                
                if ($result['valid']) {
                    if (isset($result['result'])) {
                        $this->loadLayout();
                        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/user_loan_offer_accept.phtml'));
                        $block->setLoanOfferStatus(json_decode($result['result']));
                        $this->getLayout()->getBlock('content')->append($block);
                        $this->renderLayout();
                    }
                }
            } else {
                Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
            }
        } else if (isset($loanApplicationFlag) && $loanApplicationFlag) {
            
            $approve_required = Mage::getStoreConfig('payment/clxfunding/approve_Required'); // user approve required
            $clx_status_track_model = Mage::getModel('clxfunding/CronOrderStatusUpdate'); // load clx status update model

            $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
            
            $clx_loan_application_data = Mage::getSingleton('checkout/session')->getLoanApplicationDataToSave();
            if (isset($quoteId) && isset($clx_loan_application_data) && !empty($clx_loan_application_data) && isset($clx_loan_application_data['applicationId'])) {
                $order = new Mage_Sales_Model_Order();
                $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
                $order->loadByIncrementId($orderId);
                $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Loan Application Approved.');

                $loanAppFlag = 0;
                if (isset($approve_required) && $approve_required) {
                    $loanAppFlag = 1;
                }

                try {
                    $order->sendNewOrderEmail();
                } catch (Exception $ex) {
                    Mage::logException($ex);
                }
                $order->save();
                $clx_loan_application_data['quote_id'] = $quoteId;
                $clx_loan_application_data['order_id'] = $orderId;
                $clx_loan_application_data['status'] = 'PENDING';
                $clx_loan_application_data['approvalRequired_flag'] = $loanAppFlag;
                $clx_status_track_model->saveLoanApplicationDetails($clx_loan_application_data);
                Mage::getSingleton('checkout/session')->unsLoanApplicationDataToSave();
                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => false));
            } else {
                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => false));
            }
        } else {
            Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
        }
    }

    function saveOrderCustomAction() {
        $clx_status_track_model = Mage::getModel('clxfunding/CronOrderStatusUpdate'); // load clx status update model
        $quoteId = Mage::getSingleton('checkout/session')->getQuoteId();
        $order = new Mage_Sales_Model_Order();
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order->loadByIncrementId($orderId);
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, true, 'Loan Application Approved.');
        try {
            $order->sendNewOrderEmail();
        } catch (Exception $ex) {
            Mage::logException($ex);
        }
        $order->save();

        $clx_loan_application_data = Mage::getSingleton('checkout/session')->getLoanApplicationDataToSave();
        $clx_loan_application_data['order_id'] = $orderId;
        $clx_loan_application_data['quote_id'] = $quoteId;
        $clx_loan_application_data['status'] = 'PENDING';
        $clx_loan_application_data['approvalRequired_flag'] = 0;
        $clx_loan_application_data['offer_acceptance_flag'] = true;
        $clx_status_track_model->saveLoanApplicationDetails($clx_loan_application_data);
        Mage::getSingleton('checkout/session')->unsLoanApplicationDataToSave();
        if ($order) {
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => false));
        }else {
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => false));
        }
    }
    function getLoanApplicationFormAction() {
        $quote = Mage::getModel('checkout/session')->getQuote();
        $quoteData = $quote->getData();
        $billAddress = $quote->getBillingAddress();
        $customer_dtls = $billAddress->getData();
        $street = $billAddress->getStreet(1).' '.$billAddress->getStreet(2);
        
        $grandTotal = $quoteData['grand_total'];
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/modal_clxfunding.phtml'));
        $block->setLoanAmmount($grandTotal);
        $block->setCustomerDetails($customer_dtls);
        $block->setStreetAddress($street);
        $myHtml = $block->toHtml();
        echo json_encode(array('valid' => TRUE, 'loan_app_view' => $myHtml));
    }
    
    function getStateListAction(){
        if ($this->getRequest()->isXmlHttpRequest()) {
            $country_code = trim($this->getRequest()->getParam('country_code'));
            if(isset($country_code) && !empty($country_code)){
                $states = Mage::getModel('directory/country')->load($country_code)->getRegions();
                $state_option = '<input type="text" autocapitalize="off" autocorrect="off" spellcheck="false" class="input-text required-entry validate-alpha-spac validate-no-html-tags" id="state" name="state" value="">';
                if (count($states) > 0){
                    $state_option = '';
                    $state_option = '<select class="select_input_f validate-select validate-no-html-tags" id="state" name="state"><option value="">-- Please Select --</option>';
                    foreach($states as $state):
                        $state_option.='<option value="'.$state->getName().'">'.$state->getName().'</option>';
                    endforeach;
                    $state_option .='</select>';
                }
                echo $state_option;
            }
            else{
                echo 'error';
            }
        }
        else{
            echo 'error';
        }
    }

    function loanOfferRejectAction() {
        $applicationId = $this->getRequest()->getParam('applicationId');
        $loanOfferId = $this->getRequest()->getParam('loanOfferId');
        if (isset($applicationId) && !empty($applicationId) && isset($loanOfferId) && !empty($loanOfferId)) {
            $applicationId = Mage::helper('core')->decrypt(base64_decode($applicationId));
            $loanOfferId = Mage::helper('core')->decrypt(base64_decode($loanOfferId));

            $table_prefix = Mage::getConfig()->getTablePrefix();
            $clx_table = $table_prefix . 'clx_loan_application_detail';

            $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
            $select = $connectionRead->select()
                    ->from($clx_table, array('*'))
                    ->where('application_id=? and status!="FUNDED" and status!="LENDER_REJECTED" and status!="CUSTOMER_ACCEPTED" and status!="MERCHANT_REJECTED" and status!="CUSTOMER_REJECTED" and status!="REJECTED"', $applicationId);
            $row = $connectionRead->fetchRow($select);   //return rows

            if (isset($row) && !empty($row) && is_array($row) && isset($row['clx_loan_application_detail_id']) && !empty($row['clx_loan_application_detail_id']) && isset($row['approvalRequired_flag']) && !empty($row['approvalRequired_flag']) && $row['approvalRequired_flag'] == 1) {
                $new_data = $row; //copied to row to remove some key ->value pair from array .
                unset($new_data['clx_loan_application_detail_id'], $new_data['order_id'], $new_data['application_id'], $new_data['quote_id'], $new_data['approvalRequired_flag'], $new_data['status'], $new_data['created_time'], $new_data['update_time']);
                $accountId = Mage::getStoreConfig('payment/clxfunding/account_id');
                $authorizationField = Mage::getStoreConfig('payment/clxfunding/authorization_field');
                
                if(!(isset($new_data['ssn']) && !empty($new_data['ssn']))){
                    unset($new_data['ssn']);
                }
                if(isset($new_data['employmentStatus']) && $new_data['employmentStatus']=='Not Employed'){
                    unset($new_data['employerName'],$new_data['employmentStartDate'],$new_data['occupation']);
                }
                $new_data['accountId'] = $accountId;
                $new_data['loanOfferId'] = $loanOfferId;
                $new_data['applicationId'] = $applicationId;
                $new_data['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                $new_data['isOfferAccepted'] = FALSE;
                $result = Mage::helper('clxfunding')->masterCurlRequestToClxAPIs("loan_offer_accept_or_reject",$new_data,$loanOfferId,FALSE);
                
                if (isset($result['result']) && $result['result']) {
                    /* Mail notification to Merchant about loan offer rejected by customer */
                    $parameters = array('applicationId' => $applicationId, 'orderNumber' => $row['order_id'], 'customerName' => ucfirst(strtolower($row['firstName'])) . ' ' . ucfirst(strtolower($row['lastName'])));
                    try {
                        Mage::getModel('clxfunding/ClxEmail')->sendEmail(
                                'clx_loan_offerreject_email_template', Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('trans_email/ident_sales/name'), 'Loan Offer Rejected', $parameters
                        );
                    } catch (Exception $ex) {
                        Mage::logException($ex);
                    }
                    /* Mail notification to Merchant about loan offer rejected by customer */

                    $table_prefix = Mage::getConfig()->getTablePrefix();
                    $clx_table = $table_prefix . 'clx_loan_application_detail';

                    $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $connectionWrite->beginTransaction();
                    $data = array();
                    $data['status'] = "CUSTOMER_REJECTED";
                    $data['update_time'] = date('Y-m-d H:i:s');
                    $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row['clx_loan_application_detail_id']);
                    $connectionWrite->update($clx_table, $data, $where);
                    $connectionWrite->commit();

                    /* Order Cancel */
                    if (isset($row['order_id']) && !empty($row['order_id'])) {
                        $orderModel = Mage::getModel('sales/order');
                        $orderModel->loadByIncrementId($row['order_id']);
                        if ($orderModel->canCancel()) {
                            $orderModel->cancel();
                            $orderModel->setStatus('Reason for cancellation : Customer rejected the loan offer');
                            $orderModel->save();
                        }
                    }
                    /* Order Cancel */

                    /* Notification on FrontEnd */
                    $this->loadLayout();
                    $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/user_loan_offer_reject.phtml'));
                    $this->getLayout()->getBlock('content')->append($block);
                    $this->renderLayout();
                    /* Notification on FrontEnd */
                } else {
                    Mage::log('CLX Error : Curl error [loan offer reject]');
                }
            } else {
                $this->loadLayout();
                $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/link_Expire.phtml'));
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
        } else {
            $this->getResponse()->setHeader('HTTP/1.1', '404 Not Found');
            $this->getResponse()->setHeader('Status', '404 File not found');
            $this->_forward('defaultNoRoute');
        }
    }

    function loanOfferAcceptAction() {
        $applicationId = $this->getRequest()->getParam('applicationId');
        $loanOfferId = $this->getRequest()->getParam('loanOfferId');
        if (isset($applicationId) && !empty($applicationId) && isset($loanOfferId) && !empty($loanOfferId)) {

            $applicationId = Mage::helper('core')->decrypt(base64_decode($applicationId));
            $loanOfferId = Mage::helper('core')->decrypt(base64_decode($loanOfferId));

            $table_prefix = Mage::getConfig()->getTablePrefix();
            $clx_table = $table_prefix . 'clx_loan_application_detail';

            $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
            $select = $connectionRead->select()
                    ->from($clx_table, array('*'))
                    ->where('application_id=? and status!="FUNDED" and status!="LENDER_REJECTED" and status!="CUSTOMER_ACCEPTED" and status!="REJECTED" and status!="MERCHANT_REJECTED" and status!="CUSTOMER_REJECTED"', $applicationId);
            $row = $connectionRead->fetchRow($select);   //return rows
            if (isset($row) && !empty($row) && is_array($row) && isset($row['clx_loan_application_detail_id']) && !empty($row['clx_loan_application_detail_id']) && isset($row['approvalRequired_flag']) && !empty($row['approvalRequired_flag']) && $row['approvalRequired_flag'] == 1) {
                $new_data = $row; //copy row to remove some key ->value pair from array .
                unset($new_data['clx_loan_application_detail_id'], $new_data['order_id'], $new_data['application_id'], $new_data['quote_id'], $new_data['approvalRequired_flag'], $new_data['status'], $new_data['created_time'], $new_data['update_time']);
                $accountId = Mage::getStoreConfig('payment/clxfunding/account_id');
                $authorizationField = Mage::getStoreConfig('payment/clxfunding/authorization_field');
                $new_data['accountId'] = $accountId;
                $new_data['loanOfferId'] = $loanOfferId;
                $new_data['applicationId'] = $applicationId;
                $new_data['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                
                $new_data['isOfferAccepted'] = TRUE;
                $result = Mage::helper('clxfunding')->masterCurlRequestToClxAPIs("loan_offer_accept_or_reject",$new_data,$loanOfferId,FALSE);
                if (isset($result['result']) && $result['result']) {
                    $clxOfferAcceptRes = json_decode($result['result']);
                    if ($clxOfferAcceptRes->application_status == "ACCEPTED") {
                        $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $connectionWrite->beginTransaction();
                        $data = array();
                        $data['status'] = "CUSTOMER_ACCEPTED";
                        $data['update_time'] = date('Y-m-d H:i:s');
                        $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row['clx_loan_application_detail_id']);
                        $connectionWrite->update($clx_table, $data, $where);
                        $connectionWrite->commit();
                    } else if ($clxOfferAcceptRes->application_status == "REJECTED") {
                        $connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $connectionWrite->beginTransaction();

                        $data = array();
                        $data['status'] = "REJECTED";
                        $data['update_time'] = date('Y-m-d H:i:s');

                        $orderModel = Mage::getModel('sales/order');
                        $orderModel->loadByIncrementId($row['order_id']);
                        if ($orderModel->canCancel()) {
                            $orderModel->cancel();
                            $orderModel->setStatus('Reason for cancellation : Lender rejected the loan offer');
                            $orderModel->save();
                        }
                        $where = $connectionWrite->quoteInto('clx_loan_application_detail_id =?', $row['clx_loan_application_detail_id']);
                        $connectionWrite->update($clx_table, $data, $where);
                        $connectionWrite->commit();

                    } else {
                        
                    }
                    $this->loadLayout();
                    $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/user_loan_offer_status.phtml'));
                    $block->setLoanOfferStatus(json_decode($result['result']));
                    $this->getLayout()->getBlock('content')->append($block);
                    $this->renderLayout();
                } else {
                    Mage::log('CLX Error : Curl error [loan offer accept]');
                    Mage_Core_Controller_Varien_Action::_redirectUrl(Mage::getBaseUrl());
                }
            } else {
                $this->loadLayout();
                $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/link_Expire.phtml'));
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
        } else {
            $this->getResponse()->setHeader('HTTP/1.1', '404 Not Found');
            $this->getResponse()->setHeader('Status', '404 File not found');
            $this->_forward('defaultNoRoute');
        }
    }

    public function aboutCLXFundingAction() {
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'clxfunding', array('template' => 'clxfunding/form/aboutCLXFunding.phtml'));
        echo $block->toHtml();
    }
}
