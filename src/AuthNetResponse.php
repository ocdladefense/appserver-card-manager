<?php


use net\authorize\api\contract\v1 as AuthNetAPI;
use net\authorize\api\controller as AuthNetController;
use net\authorize\api\constants\AuthNetEnvironment;



class AuthNetResponse {


    const RESPONSE_OK = "Ok";

    const RESPONSE_ERROR = "Error";


    private $response;


    public function __construct($resp) {

        $this->response = $resp;
    }
    

    public function hasErrors($response) {

        return $response->getMessages()->getResultCode() == self::RESPONSE_ERROR;
    }


    public function getResponse() {

        return $this->response;
    }


    public function getProfile() {

        return $this->response->getProfile();
    }

    public function getProfileId() {

        return $this->response->getCustomerProfileId();
    }


    public function getPaymentProfile() {

        return $this->response->getPaymentProfile();
    }

    public function getCustomerPaymentProfileId() {

        return $this->response->getCustomerPaymentProfileId();
    }

    public function getPaymentProfiles() {

        $payments = $this->getProfile()->getPaymentProfiles();
        
        return PaymentProfile::fromMaskedArrays($payments);
    }


    public function getMessages() {

        return $this->response->getMessages();
    }


    public function getErrorMessage() {

        return $this->response->getMessages()->getMessage()[0]->getText();
    }


    public function success() {

        return $this->response->getMessages()->getResultCode() == self::RESPONSE_OK;
    }

}