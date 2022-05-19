<?php


use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use net\authorize\api\constants\ANetEnvironment;


class CustomerProfile {

    const RESPONSE_OK = "Ok";

    public $endpoint;
    public $profileId;
    public $response;
    public $success;
    

    public function __construct($profileId = null) {

        $this->endpoint = AUTHORIZE_DOT_NET_USE_PRODUCTION_ENDPOINT ? ANetEnvironment::PRODUCTION : ANetEnvironment::SANDBOX;
        $this->profileId = $profileId;
    }

    // Create a new customer profile
    public static function create($params) {

        $cp = new self();

        $customerProfile = new AnetAPI\CustomerProfileType();
        $customerProfile->setDescription($params["description"]);
        $customerProfile->setMerchantCustomerId($params["customerId"]);
        $customerProfile->setEmail($params["email"]);

        $request = new AnetAPI\CreateCustomerProfileRequest();
        $request->setMerchantAuthentication(MerchantAuthentication::get());
        $request->setProfile($customerProfile);

        $controller = new AnetController\CreateCustomerProfileController($request);
        $response = $controller->executeWithApiResponse($cp->endpoint);

        if($cp->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }

        return $response;
    }

    // Get customer profile
    public function getProfile() {

        $request = new AnetAPI\GetCustomerProfileRequest();
        $request->setMerchantAuthentication(MerchantAuthentication::get());
        $request->setCustomerProfileId($this->profileId);
        $controller = new AnetController\GetCustomerProfileController($request);
        $response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
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


    public function getPaymentProfile($profileId) {

        $request = new AnetAPI\GetCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication(MerchantAuthentication::get());
        $request->setRefId( $refId);
        $request->setCustomerProfileId($this->profileId);
        $request->setCustomerPaymentProfileId($profileId);
    
        $controller = new AnetController\GetCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }

        $paymentProfile = $response->getPaymentProfile();

        return PaymentProfile::fromMaskedArray($paymentProfile);
    }


    public function savePaymentProfile($profile){

        $isDefault = empty($profile->default) ? false : true;
        $isUpdate = empty($profile->id) ? false : true;

        // Set credit card information for payment profile
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($profile->cardNumber);
        $creditCard->setExpirationDate($profile->expYear . "-" . $profile->expMonth);
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
        $billto->setZip($profile->zip);
        $billto->setCountry("USA");
        $billto->setPhoneNumber($profile->phone);

        // Create a new Customer Payment Profile object
        $paymentprofile = $isUpdate ? new AnetAPI\CustomerPaymentProfileExType() : new AnetAPI\CustomerPaymentProfileType();

        if($isUpdate) $paymentprofile->setCustomerPaymentProfileId($profile->id);

        $paymentprofile->setCustomerType('individual');
        $paymentprofile->setBillTo($billto);
        $paymentprofile->setPayment($paymentCreditCard);
        $paymentprofile->setDefaultPaymentProfile($isDefault);

        // Assemble the complete transaction request
        $paymentprofilerequest = $isUpdate ? new AnetAPI\UpdateCustomerPaymentProfileRequest() : new AnetAPI\CreateCustomerPaymentProfileRequest();
        $paymentprofilerequest->setMerchantAuthentication(MerchantAuthentication::get());

        // Add an existing profile id to the request
        $paymentprofilerequest->setCustomerProfileId($this->profileId);
        $paymentprofilerequest->setPaymentProfile($paymentprofile);
        $paymentprofilerequest->setValidationMode("liveMode");

        // Create the controller and get the response
        $controller = $isUpdate ? new AnetController\UpdateCustomerPaymentProfileController($paymentprofilerequest)
                    : new AnetController\CreateCustomerPaymentProfileController($paymentprofilerequest);

        $this->response = $controller->executeWithApiResponse($this->endpoint);

        return $isUpdate ? $profile->id : $this->response->getCustomerPaymentProfileId();
    }

    public function deletePaymentProfile($pProfileId) {

        $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
        $request->setMerchantAuthentication(MerchantAuthentication::get());
        $request->setCustomerProfileId($this->profileId);
        $request->setCustomerPaymentProfileId($pProfileId);
        $controller = new AnetController\DeleteCustomerPaymentProfileController($request);
        $response = $controller->executeWithApiResponse($this->endpoint);

        if($this->hasErrors($response)) {

            $errorMessages = $response->getMessages()->getMessage();
            throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
        }
    }


    // Theses errors are probably due to programming errors, so Im just gonna throw the exception in the calling code.
    public function hasErrors($response) {

        return $response->getMessages()->getResultCode() != self::RESPONSE_OK;
    }


    public function getResponse() {

        return $this->response;
    }


    public function getCustomerId() {

        return $this->getProfile()->getMerchantCustomerId();
    }


    // Some errors should be handled in a user-friendly way...hence the next two methods.
    //(Feels like im on the verge of refactoring the way I work with the response)
    public function getErrorMessage() {

        return $this->response->getMessages()->getMessage()[0]->getText();
    }


    public function success() {

        return $this->response->getMessages()->getResultCode() == self::RESPONSE_OK;
    }
}

