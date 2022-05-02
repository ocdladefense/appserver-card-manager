<?php

use PaymentProfile;
use MerchantAuthentication;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use net\authorize\api\constants\ANetEnvironment;


class CustomerProfile {

    const RESPONSE_OK = "Ok";

    public $endpoint;
    public $profileId;
    

    public function __construct($profileId = null) {

        $this->endpoint = ANetEnvironment::SANDBOX;
        $this->profileId = $profileId;
    }


    // Get customer profile
    public function getProfile() {

        $request = new AnetAPI\GetCustomerProfileRequest();
        $request->setMerchantAuthentication(MerchantAuthentication::getMerchantAuthentication());
        $request->setCustomerProfileId($this->profileId);
        $controller = new AnetController\GetCustomerProfileController($request);
        $response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            Throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }

        return $response->getProfile();
    }


    // Get all payment profiles associated with a customer's profile.
    public function getPaymentProfiles() {

        $pProfiles = $this->getProfile()->getPaymentProfiles();
        
        $paymentProfiles = [];

        foreach($pProfiles as $paymentProfile) {

            $paymentProfiles[] = PaymentProfile::fromMaskedArray($paymentProfile);
        }
        
        return $paymentProfiles;
    }


    public function addPaymentProfile(){}

    public function updatePaymentProfile($id) {}

    public function deletePaymentProfile($id) {}


    public function hasErrors($response) {

        return $response->getMessages()->getResultCode() != self::RESPONSE_OK;
    }
}


class PaymentProfileManagerException extends Exception{}

