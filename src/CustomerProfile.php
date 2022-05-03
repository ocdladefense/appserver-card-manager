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
    

    public function __construct($profileId) {

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


    public function getPaymentProfile($profileId, $getAsObject = true) {

        $request = new AnetAPI\GetCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication(MerchantAuthentication::getMerchantAuthentication());
        $request->setRefId( $refId);
        $request->setCustomerProfileId($this->profileId);
        $request->setCustomerPaymentProfileId($profileId);
    
        $controller = new AnetController\GetCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            Throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }

        $paymentProfile = $response->getPaymentProfile();

        return $getAsObject ? PaymentProfile::fromMaskedArray($paymentProfile) : $paymentProfile;
    }


    public function addPaymentProfile($profile){

        $isDefault = empty($profile->default) ? false : true;

        // Set credit card information for payment profile
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($profile->cardNumber);
        $creditCard->setExpirationDate($profile->expYear . "-" . $profile->expMonth);
        $creditCard->setCardCode($profile->cvv);
        $paymentCreditCard = new AnetAPI\PaymentType();
        $paymentCreditCard->setCreditCard($creditCard);


        // Create the Bill To info for new payment type
        $billto = new AnetAPI\CustomerAddressType();
        $billto->setFirstName($profile->firstName);
        $billto->setLastName($profile->lastName);
        // $billto->setCompany("Souveniropolis");
        $billto->setAddress($profile->address);
        $billto->setCity($profile->city);
        $billto->setState($profile->state);
        $billto->setZip($profile->city);
        $billto->setCountry("USA");
        $billto->setPhoneNumber($profile->phone);
        // $billto->setfaxNumber("999-999-9999");

        // Create a new Customer Payment Profile object
        $paymentprofile = new AnetAPI\CustomerPaymentProfileType();
        $paymentprofile->setCustomerType('individual');
        $paymentprofile->setBillTo($billto);
        $paymentprofile->setPayment($paymentCreditCard);
        $paymentprofile->setDefaultPaymentProfile($isDefault);

        // Assemble the complete transaction request
        $paymentprofilerequest = new AnetAPI\CreateCustomerPaymentProfileRequest();
        $paymentprofilerequest->setMerchantAuthentication(MerchantAuthentication::getMerchantAuthentication());

        // Add an existing profile id to the request
        $paymentprofilerequest->setCustomerProfileId($this->profileId);
        $paymentprofilerequest->setPaymentProfile($paymentprofile);
        $paymentprofilerequest->setValidationMode("liveMode");

        // Create the controller and get the response
        $controller = new AnetController\CreateCustomerPaymentProfileController($paymentprofilerequest);
        $response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            Throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }
    }

    public function updatePaymentProfile($profile) {

        $isDefault = empty($profile->default) ? false : true;

        $billto = new AnetAPI\CustomerAddressType();
        $billto->setFirstName($profile->firstName);
        $billto->setLastName($profile->lastName);
        // $billto->setCompany("Souveniropolis");
        $billto->setAddress($profile->address);
        $billto->setCity($profile->city);
        $billto->setState($profile->state);
        $billto->setZip($profile->city);
        $billto->setCountry("USA");
        $billto->setPhoneNumber($profile->phone);
        // $billto->setfaxNumber("999-999-9999");

        $creditCard = new AnetAPI\CreditCardType();
		$creditCard->setCardNumber($profile->cardNumber);
		$creditCard->setExpirationDate($profile->expYear . "-" . $profile->expMonth);

        $paymentCreditCard = new AnetAPI\PaymentType();
		$paymentCreditCard->setCreditCard($creditCard);
		$paymentprofile = new AnetAPI\CustomerPaymentProfileExType();
		$paymentprofile->setBillTo($billto);
		$paymentprofile->setCustomerPaymentProfileId($profile->id);
		$paymentprofile->setPayment($paymentCreditCard);

        // Submit a UpdatePaymentProfileRequest
		$request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
		$request->setMerchantAuthentication(MerchantAuthentication::getMerchantAuthentication());
		$request->setCustomerProfileId($this->profileId);
		$request->setPaymentProfile($paymentprofile);

		$controller = new AnetController\UpdateCustomerPaymentProfileController($request);
		$response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            Throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }
    }

    public function deletePaymentProfile($pProfileId) {

        $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication(MerchantAuthentication::getMerchantAuthentication());
        $request->setCustomerProfileId($this->profileId);
        $request->setCustomerPaymentProfileId($pProfileId);
        $controller = new AnetController\DeleteCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            Throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }
    }


    public function hasErrors($response) {

        return $response->getMessages()->getResultCode() != self::RESPONSE_OK;
    }
}


class PaymentProfileManagerException extends Exception{}

