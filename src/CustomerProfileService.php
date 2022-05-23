<?php


use net\authorize\api\contract\v1 as AuthNetAPI;



class CustomerProfileService {


    private $profileId;

    private $env;


    private function __construct($env) {

        $this->env = $env;
    }


    public static function newFromEnvironment($env, $profileId) {

        $instance = new CustomerProfileService($env);
        $instance->setProfileId($profileId);

        return $instance;
    }

    private function setProfileId($profileId) {

        $this->profileId = $profileId;
    }



    // Get customer profile
    public function getProfile() {

        $req = new AuthNetRequest("authnet://GetCustomerProfile");
        $req->addProperty("customerProfileId", $this->profileId);
        
        $client = new AuthNetClient($this->env);
        $resp = $client->send($req);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return $resp->getProfile();
    }


    public function getPaymentProfile($paymentProfileId) {

        $req = new AuthNetRequest("authnet://GetCustomerPaymentProfile");
        $req->addProperty("customerProfileId", $this->profileId);
        $req->addProperty("customerPaymentProfileId", $paymentProfileId);
        
        $client = new AuthNetClient($this->env);
        $resp = $client->send($req);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return $resp->getPaymentProfile();
    }




    public function savePaymentProfile($data) {

        $isDefault = empty($data->default) ? false : true;
        $isUpdate = empty($data->id) ? false : true;

        $paymentType = $this->getPaymentType($data);
        $billTo = $this->getBillTo($data);

        $paymentProfile = $isUpdate ? new AuthNetAPI\CustomerPaymentProfileExType() : new AuthNetAPI\CustomerPaymentProfileType();

        if($isUpdate) $paymentProfile->setCustomerPaymentProfileId($data->id);

        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($billTo);
        $paymentProfile->setPayment($paymentType);
        $paymentProfile->setDefaultPaymentProfile($isDefault);

        $requestType = $isUpdate ? "UpdateCustomerPaymentProfile" : "CreateCustomerPaymentProfile";

        $req = new AuthNetRequest("authnet://$requestType");
        $req->addProperty("customerProfileId", $this->profileId);
        $req->addProperty("paymentProfile", $paymentProfile);
        
        $client = new AuthNetClient($this->env);
        
        return $client->send($req);
    }


    public function getPaymentType($data) {

        $creditCard = new AuthNetAPI\CreditCardType();
        $creditCard->setCardNumber($data->cardNumber);
        $creditCard->setExpirationDate($data->expYear . "-" . $data->expMonth);
        $paymentType = new AuthNetAPI\PaymentType();
        $paymentType->setCreditCard($creditCard);

        return $paymentType;
    }
    

    public function getBillTo($data) {

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
    public function deletePaymentProfile($paymentProfileId) {

        $req = new AuthNetRequest("authnet://DeleteCustomerPaymentProfile");
        $req->addProperty("customerProfileId", $this->profileId);
        $req->addProperty("customerPaymentProfileId", $paymentProfileId);
        
        $client = new AuthNetClient($this->env);
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


    // Create a new customer profile
    public function create($params) {

        $profile = new AnetAPI\CustomerProfileType();
        $profile->setDescription($params["description"]);
        $profile->setMerchantCustomerId($params["customerId"]);
        $profile->setEmail($params["email"]);

        $req = new AuthNetRequest("authnet://CreateCustomerProfile");
        $req->addProperty("customerProfile", $profile);
        
        $client = new AuthNetClient($this->env);
        $resp = $client->send($req);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return $resp;
    }    
}

