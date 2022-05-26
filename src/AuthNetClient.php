<?php


use net\authorize\api\contract\v1 as AuthNetAPI;
use net\authorize\api\controller as AuthNetController;
use net\authorize\api\constants\ANetEnvironment as AuthNetEnvironment;


class AuthNetClient { 

    const RESPONSE_OK = "Ok";

    static $defaultEndpoint = AuthNetEnvironment::SANDBOX;

    private $endpoint; 



    public function __construct($endpoint) {
        $this->endpoint = $endpoint;
    }


    static $endpoints = array(
        "GetCustomerProfileRequest" => "GetCustomerProfileController",
        "GetCustomerPaymentProfileRequest" => "GetCustomerPaymentProfileController",
        "UpdateCustomerPaymentProfileRequest" => "UpdateCustomerPaymentProfileController",
        "CreateCustomerPaymentProfileRequest" => "CreateCustomerPaymentProfileController",
        "DeleteCustomerPaymentProfileRequest" => "DeleteCustomerPaymentProfileController"
    );


    function send(AuthNetRequest $helper) {
        $type = $helper->getRequestType();

        $key = $type . "Request";

        $nsreq = "net\\authorize\\api\\contract\\v1";
        $nscon = "net\\authorize\\api\\controller";

        $reqClass = $nsreq . "\\" . $key;
        $clientClass = $nscon . "\\" . self::$endpoints[$key];

        $req = new $reqClass;
        // See foreach loop, below, for algo on how to do this dynamically.
        // $req->setCustomerProfileId($this->profileId);
        // $req->setPaymentProfile($paymentprofile);
        // $req->setCustomerPaymentProfileId($pProfileId);
        // $req->setProfile($customerProfile);
        

        // Inspect the body of our request.
        // Use keys and values to invoke the appropriate Authnet method names,
        // passing in $value as their parameters.
        foreach($helper->getBody() as $method => $value) {
            $methodn = "set" . ucwords($method);
            $req->{$methodn}($value);
        }

        $req->setMerchantAuthentication(MerchantAuthentication::get());
        // $req->setValidationMode("liveMode");
        $req->setRefId($refId);

        $client = new $clientClass($req);
        return $client->executeWithApiResponse($this->endpoint);


        // throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]
    }




}

