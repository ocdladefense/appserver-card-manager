<?php


use net\authorize\api\contract\v1 as AuthNetAPI;



class CustomerProfileService {


    private $profileId;

    private $environment;


    private function __construct($env) {

        $this->environment = $env;
    }


    public static function newFromEnvironment($env, $profileId) {
        $instance = new CustomerProfileService($env);
        $instance->setProfileId($profileId);
    }

    private function setProfileId($profileId) {
        $this->profileId = $profileId;
    }



    // Get customer profile
    public function getProfile() {

        $req = new AuthNetRequest("authnet://GetCustomerProfile");
        $req->addProperty("customerProfileId", $profileId);
        
        $client = new AuthNetClient(AuthNetEnvironment::SANDBOX);
        $resp = $client->send($req);

        return $resp->getProfile();
    }



    public function getPaymentProfiles() {

        $profile = $this->getProfile();

        return $profile->getPaymentProfiles();
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






/**
   // Test function from our meeting.
     
    public function updatePaymentProfile($profileId = "904941070") {

        $customerId = "905125806";

        // Set the transaction's refId
        $refId = 'ref' . time();

        $get = new AnetAPI\GetCustomerPaymentProfileRequest();
        $get->setMerchantAuthentication(MerchantAuthentication::get());
        $get->setRefId($refId);
        $get->setCustomerProfileId($customerId);
        $get->setCustomerPaymentProfileId($profileId);
        
        $client = new AnetController\GetCustomerPaymentProfileController($get);
        $resp = $client->executeWithApiResponse( \net\authorize\api\constants\AuthNetEnvironment::SANDBOX);


        $existing = $resp->getPaymentProfile();
        $payment = $existing->getPayment();
        $card = $payment->getCreditCard();
        $cardno = $card->getCardNumber();
        $cardexp = $card->getExpirationDate();

        // var_dump($card,$cardno,$cardexp);exit;
        $existingBillTo = $existing->getbillTo();


        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber($card->getCardNumber());//"4111111111111111" );
        $creditCard->setExpirationDate("2023-01");//"2038-12");
        
        $paymentCreditCard = new AnetAPI\PaymentType();
        $paymentCreditCard->setCreditCard($creditCard);

        $profile = new AnetAPI\CustomerPaymentProfileExType();
        // $profile->setBillTo($existingBillTo);
        $profile->setCustomerPaymentProfileId($profileId);
        $profile->setPayment($paymentCreditCard);
        


        // Assemble the complete transaction request
        $req = new AnetAPI\UpdateCustomerPaymentProfileRequest();
        $req->setMerchantAuthentication(MerchantAuthentication::get());

        // Add an existing profile id to the request
        $req->setCustomerProfileId($customerId);
        // $req->setPaymentProfile($profile);
        $req->setPaymentProfile($profile); // Will this work?
        // $req->setValidationMode("liveMode");



        // Create the controller and get the response
        $controller = new AnetController\UpdateCustomerPaymentProfileController($req);

        $resp = $controller->executeWithApiResponse( \net\authorize\api\constants\AuthNetEnvironment::SANDBOX);

        var_dump($resp);

        exit;
    
}
*/


    
    
}

