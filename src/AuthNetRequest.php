<?php


use net\authorize\api\contract\v1 as AuthNetAPI;
use net\authorize\api\controller as AuthNetController;
use net\authorize\api\constants\ANetEnvironment as AuthNetEnvironment;


class AuthNetRequest { 

    const RESPONSE_OK = "Ok";

    static $endpoint = AuthNetEnvironment::SANDBOX;

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

        $nsreq = "net\\authorize\\api\\contract\\v1";
        $nscon = "net\\authorize\\api\\controller";

        $reqClass = $nsreq . "\\" . $key;
        $clientClass = $nscon . "\\" . self::$endpoints[$key];

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
        foreach($this->body as $method => $value) {
            $methodn = "set" . ucwords($method);
            $req->{$methodn}($value);
        }

        $client = new $clientClass($req);
        return $client->executeWithApiResponse(self::$endpoint);


        // throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]
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



    public function getCustomerId() {

        return $this->getProfile()->getMerchantCustomerId();
    }





}

