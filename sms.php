<?php
require_once 'vendor/autoload.php';

use AfricasTalking\SDK\AfricasTalking;

class SMS {
    private $sms;

    public function __construct() {
        $username = "sandbox"; 
        $apiKey   = "atsk_1b94c75ee889e25ebef7e456f030ea05b9b6e8ac3bc7f386438d65eeb5d80b779e7d7d80";
        
        $AT = new AfricasTalking($username, $apiKey);
        $this->sms = $AT->sms();
    }
    public function sendWelcomeSMS($phoneNumber, $name) {
        try {
            $message = "WELCOME Dear $name, you have successfully registered with us.";
            
            $result = $this->sms->send([
                'to'      => $phoneNumber,
                'message' => $message,
            ]);
            
            return $result;
        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return false;
        }
    }
    

    public function sendCartConfirmationSMS($phoneNumber, $productName) {
    try {
        $message = "The Event Add successful ($productName) has been added successfull. Thank you for to be with us!";
        
        $result = $this->sms->send([
            'to'      => $phoneNumber,
            'message' => $message,
        ]);
        
        return $result;
    } catch (Exception $e) {
        error_log("SMS Error: " . $e->getMessage());
        return false;
    }
}
public function sendCartSummarySMS($phoneNumber, $cartContent) {
    try {
        $message = "Smart event - Booking  SUMMARY\n\n";
        $message .= $cartContent . "\n\n";
        $message .= "Thank you for be with us!";
        
        $result = $this->sms->send([
            'to'      => $phoneNumber,
            'message' => $message,
        ]); 
        
        return $result;
    } catch (Exception $e) {
        error_log("Event SMS Error: " . $e->getMessage());
        return false;
    }
}
}
?>