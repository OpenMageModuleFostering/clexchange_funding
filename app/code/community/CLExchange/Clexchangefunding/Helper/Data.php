<?php
/*
 * This is a custom helper class, In this we making API request for 1.Getting Loan Offers 2.Creating loan application 3.Getting Loan application Status 4.Getting response from Queue 5.Loan Offer Accept/Reject API request
 */
class CLExchange_Clexchangefunding_Helper_Data extends Mage_Core_Helper_Abstract {

    /* Following functions are make API request for realtime loan offers,creating loan application etc. 
     * Which accepts $url i.e API endpoint , 
     * $data is the customers loan application details & 
     * $authorizationField is a authorization key (This is to be set during extension configuration in admin panel)
     */ 
    
    function masterCurlRequestToClxAPIs($api,$data=FALSE,$loanOfferId=FALSE,$applicationId=FALSE){
        $authorizationField = Mage::getStoreConfig('payment/clxfunding/authorization_field');
        $accountId = Mage::getStoreConfig('payment/clxfunding/account_id');
        if(isset($api) && !empty($api)){
            if($api=='create_application'){
                $url = "https://portal.clexchange.io/api/loan-applications";// (Portal) Production environment
                $page = "/api/loan-applications";
                $data['accountId'] = $accountId;
            }else if($api=='get_loan_offer'){
                $url = "https://portal.clexchange.io/api/loan-offers";// (Portal) Production environment
                $page = "/api/loan-offers";
                $data['accountId'] = $accountId;
            }
            else if($api=='loan_offer_accept_or_reject'){
                $url = "https://portal.clexchange.io/api/loan-offers/".$loanOfferId."/loan-applications" ;// (Portal) Production environment
                $page = "/api/loan-offers/" . $loanOfferId . "/loan-applications";
                $data['accountId'] = $accountId;
                if(isset($data['isOfferAccepted'])){
                    $data['isOfferAccepted'] =  $data['isOfferAccepted']=="1" ? true: false;
                }
            }
            else if($api=='get_current_loan_app_status'){
                $url = "https://portal.clexchange.io/api/partner-customers/loan-applications/$applicationId";// (Portal) Production environment
                $page = "/api/partner-customers/loan-applications/$applicationId";
                $data['accountId'] = $accountId;
            }
            else{
                return array('valid' => FALSE, 'Error' => 'API Error');
            }
            
            if(!(isset($data['ssn']) && !empty($data['ssn']))){
                $data['ssn']='';
            }
            
            $headers = array(
                "POST " . $page . " HTTP/1.1",
                "Host:portal.clexchange.io",
                "Content-type: application/json",
                "Cache-Control: no-cache",
                "Authorization: " . $authorizationField,
                "Content-length: " . strlen(json_encode($data)),
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url); //setup curl url
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); //setup auth to basic
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //setup request header
            curl_setopt($ch, CURLOPT_POST, 1); //setup type post
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); //post parameters in json format
            
            $info = curl_exec($ch);
            
            if($api=='get_current_loan_app_status'){
                if (curl_errno($ch)) {
                    return FALSE;
                } else {
                    curl_close($ch);
                    $reponse_data = json_decode($info);
                    if (isset($reponse_data) && !empty($reponse_data)) {
                        return $reponse_data;
                    } else {
                        return FALSE;
                    }
                }
            }
            else{
                if (curl_errno($ch)) {
                    return array('valid' => FALSE, 'Error' => curl_error($ch));
                } else {
                    curl_close($ch);
                    return array('valid' => TRUE, 'result' => $info);
                }
            }    
        }
        else{
            return array('valid' => FALSE, 'Error' => 'API Error');
        }
        
    }

    function clxQueueingSystem() {
        $Queue_Authentication_Key = Mage::getStoreConfig('payment/clxfunding/queue_authentication_key');
        $Queue_Endpoint = Mage::getStoreConfig('payment/clxfunding/queue_endpoint');
        if (isset($Queue_Authentication_Key) && !empty($Queue_Authentication_Key) && isset($Queue_Endpoint) && !empty($Queue_Endpoint)) {
            $headers = array(
                "Content-type: application/json",
                "Authorization: OAuth " . $Queue_Authentication_Key
            );
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $Queue_Endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $info = curl_exec($ch);
            
            if (curl_errno($ch)) {
                return FALSE;
            } else {
                curl_close($ch);
                $reponse_data = json_decode($info);
                if (isset($reponse_data) && !empty($reponse_data)) {
                    return $reponse_data;
                } else {
                    return FALSE;
                }
            }
        } else {
            Mage::log('Clx Error : Missing queue authentication key or queue endpoint ');
        }
    }
    /* This function is for calculating age */
    function getAge($date1, $date2) {
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        $age = 0;
        while ($date2 > $date1 = strtotime('+1 year', $date1)) {
            ++$age;
        }
        return $age;
    }
}
