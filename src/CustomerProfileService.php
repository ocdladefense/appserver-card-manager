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
    public static function getProfile($env, $profileId) {

        $req = new AuthNetRequest("authnet://GetCustomerProfile");
        $req->addProperty("customerProfileId", $profileId);
        
        $client = new AuthNetClient($env);
        $resp = $client->send($req);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return $resp->getResponse()->getProfile();
    }




    public static function savePaymentProfile($env, $profileId, $data) {

        $isDefault = empty($data->default) ? false : true;
        $isUpdate = empty($data->id) ? false : true;

        $paymentType = self::getPaymentType($data);
        $billTo = self::getBillTo($data);

        $paymentProfile = $isUpdate ? new AuthNetAPI\CustomerPaymentProfileExType() : new AuthNetAPI\CustomerPaymentProfileType();

        if($isUpdate) $paymentprofile->setCustomerPaymentProfileId($profile->id);

        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($billTo);
        $paymentProfile->setPayment($paymentType);
        $paymentProfile->setDefaultPaymentProfile($isDefault);

        $requestType = $isUpdate ? "UpdateCustomerPaymentProfile" : "CreateCustomerPaymentProfile";

        $req = new AuthNetRequest("authnet://$requestType");
        $req->addProperty("customerProfileId", $profileId);
        $req->addProperty("paymentProfile", $paymentProfile);
        
        $client = new AuthNetClient($env);
        return $client->send($req);

        // might need to return the id of the response or the original id in the data!!!!
    }


    public static function getPaymentType($data) {

        $creditCard = new AuthNetAPI\CreditCardType();
        $creditCard->setCardNumber($data->cardNumber);
        $creditCard->setExpirationDate($data->expYear . "-" . $data->expMonth);
        $paymentType = new AuthNetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);

        return $paymentType;
    }
    

    public static function getBillTo($data) {

        $billto = new AuthNetAPI\CustomerAddressType();
        $billto->setFirstName($data->firstName);
        $billto->setLastName($data->lastName);
        // $billto->setCompany("Souveniropolis");
        $billto->setAddress($data->address);
        $billto->setCity($data->city);
        $billto->setState($data->state);
        $billto->setZip($data->zip);
        $billto->setCountry("USA");
        $billto->setPhoneNumber($data->phone);

        return $billto;
    }

    // Delete a payment profile
    public static function deletePaymentProfile($env, $profileId, $paymentProfileId) {

        $req = new AuthNetRequest("authnet://DeleteCustomerPaymentProfile");
        $req->addProperty("customerProfileId", $profileId);
        $req->addProperty("customerPaymentProfileId", $paymentProfileId);
        
        $client = new AuthNetClient($env);
        $resp = $client->send($req);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());
    }



    // // Create a new customer profile
    // public static function create($params) {

    //     $cp = new self();

    //     $customerProfile = new AnetAPI\CustomerProfileType();
    //     $customerProfile->setDescription($params["description"]);
    //     $customerProfile->setMerchantCustomerId($params["customerId"]);
    //     $customerProfile->setEmail($params["email"]);

    //     $request = new AnetAPI\CreateCustomerProfileRequest();
    //     $request->setMerchantAuthentication(MerchantAuthentication::get());
    //     $request->setProfile($customerProfile);

    //     $controller = new AnetController\CreateCustomerProfileController($request);
    //     $response = $controller->executeWithApiResponse($cp->endpoint);

    //     if($cp->hasErrors($response)) {

    //         $errorMessages = $response->getMessages()->getMessage();
    //         throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
    //     }

    //     return $response;
    // }





    // Get customer profile
    // public function getProfile() {

    //     $request = new AnetAPI\GetCustomerProfileRequest();
    //     $request->setMerchantAuthentication(MerchantAuthentication::get());
    //     $request->setCustomerProfileId($this->profileId);
    //     $controller = new AnetController\GetCustomerProfileController($request);
    //     $response = $controller->executeWithApiResponse($this->endpoint);

    //     if($this->hasErrors($response)) {

    //         $errorMessages = $response->getMessages()->getMessage();
    //         throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
    //     }

    //     return $response->getProfile();
    // }


    // Get all payment profiles associated with a customer's profile.
    // public function getPaymentProfiles() {

    //     $pProfiles = $this->getProfile()->getPaymentProfiles();
        
    //     $paymentProfiles = [];

    //     foreach($pProfiles as $paymentProfile) {

    //         $paymentProfiles[] = PaymentProfile::fromMaskedArray($paymentProfile);
    //     }
        
    //     return $paymentProfiles;
    // }


    // public function getPaymentProfile($profileId) {

    //     $request = new AnetAPI\GetCustomerPaymentProfileRequest();
    //     $request->setMerchantAuthentication(MerchantAuthentication::get());
    //     $request->setRefId( $refId);
    //     $request->setCustomerProfileId($this->profileId);
    //     $request->setCustomerPaymentProfileId($profileId);
    
    //     $controller = new AnetController\GetCustomerPaymentProfileController($request);
    //     $response = $controller->executeWithApiResponse($this->endpoint);

    //     if($this->hasErrors($response)) {

    //         $errorMessages = $response->getMessages()->getMessage();
    //         throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]->getText());
    //     }

    //     $paymentProfile = $response->getPaymentProfile();

    //     return PaymentProfile::fromMaskedArray($paymentProfile);
    // }












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

