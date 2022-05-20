<?php


use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use net\authorize\api\constants\ANetEnvironment;


class CustomerProfile { // Could rename this to CustomerProfileRequest?

    const RESPONSE_OK = "Ok";

    static $endpoint = ANetEnvironment::SANDBOX;

    static $endpoints = array(
        "GetCustomerProfileRequest" => "GetCustomerProfileController",
        "GetCustomerPaymentProfileRequest" => "GetCustomerPaymentProfileController",
        "UpdateCustomerPaymentProfileRequest" => "UpdateCustomerPaymentProfileController",
        "CreateCustomerPaymentProfileRequest" => "CreateCustomerPaymentProfileController",
        "DeleteCustomerPaymentProfileRequest" => "DeleteCustomerPaymentProfileController"
    );

    private $body;
    
    private $profileId;


    
    

    public function __construct($profileId = null) {

        $this->profileId = $profileId;
    }



    // Get customer profile
    public function getProfile() {

        $this->body["customerProfileId"] = $this->profileId;

        return $this->send("GetCustomerProfile");
        // return $response->getProfile();
    }




    // Create a new customer profile
    public function create($params) {

        $profile = new AnetAPI\CustomerProfileType();
        $profile->setDescription($params["description"]);
        $profile->setMerchantCustomerId($params["customerId"]);
        $profile->setEmail($params["email"]);

        // $request->setProfile($customerProfile);
        $this->body["profile"] = $profile;
        
        return $this->send("CreateCustomerProfile");
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

    
        $this->body = array(
            "customerProfileId" => $this->profileId,
            "customerPaymentProfileId" => $profileId
        );

        return $this->send("GetCustomerPaymentProfileRequest");

        // $paymentProfile = $response->getPaymentProfile();

        // return PaymentProfile::fromMaskedArray($paymentProfile);
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
        $endpoint = $isUpdate ? "UpdateCustomerPaymentProfile" : "CreateCustomerPaymentProfile";

        $resp = $this->send($endpoint);

        return $isUpdate ? $profile->id : $this->response->getCustomerPaymentProfileId();
    }


    private function updatePaymentProfile($profileId) {

    }

    private function createPaymentProfile() {

    }


    function send($endpoint = "CreateCustomerPaymentProfile") {

        $key = $endpoint . "Request";

        $ns = "AnetController";

        $reqClass = $ns . "\\" . $key;
        $clientClass = $ns . "\\" . self::$endpoints[$key];

        $req = new $reqClass;
        

        $req->setMerchantAuthentication(MerchantAuthentication::get());
        $req->setRefId($refId);
        // $req->setValidationMode("liveMode");

        // See foreach loop, below, for algo on how to do this dynamically.
        // $req->setCustomerProfileId($this->profileId);
        // $req->setPaymentProfile($paymentprofile);
        // $req->setCustomerPaymentProfileId($pProfileId);
        // $req->setProfile($customerProfile);
        

        // Inspect the body of our request.
        // Use keys and values to invoke the appropriate Authnet method names,
        // passing in $value as their parameters.
        foreach($this-body as $method => $value) {
            $methodn = "set" . ucasewords($method);
            $req->{$methodn}($value);
        }

        $client = new $clientClass($req);
        $resp = $client->executeWithApiResponse($this->endpoint);


        // throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]
    }







    public function getCustomerId() {

        return $this->getProfile()->getMerchantCustomerId();
    }




    // Theses errors are probably due to programming errors, so Im just gonna throw the exception in the calling code.
    public function hasErrors($response) {

        return $response->getMessages()->getResultCode() != self::RESPONSE_OK;
    }


    public function getResponse() {

        return $this->response;
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

