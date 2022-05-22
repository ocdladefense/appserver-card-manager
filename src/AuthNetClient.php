<?php


use net\authorize\api\contract\v1 as AuthNetAPI;
use net\authorize\api\controller as AuthNetController;
use net\authorize\api\constants\AuthNetEnvironment;


class AuthNetClient { 

    const RESPONSE_OK = "Ok";

    static $endpoint = AuthNetEnvironment::SANDBOX;

    static $endpoints = array(
        "GetCustomerProfileRequest" => "GetCustomerProfileController",
        "GetCustomerPaymentProfileRequest" => "GetCustomerPaymentProfileController",
        "UpdateCustomerPaymentProfileRequest" => "UpdateCustomerPaymentProfileController",
        "CreateCustomerPaymentProfileRequest" => "CreateCustomerPaymentProfileController",
        "DeleteCustomerPaymentProfileRequest" => "DeleteCustomerPaymentProfileController"
    );


    function send($endpoint = "CreateCustomerPaymentProfile") {

        $key = $endpoint . "Request";

        $nsreq = "ANetAPI";
        $nscon = "ANetController";

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
        foreach($this-body as $method => $value) {
            $methodn = "set" . ucasewords($method);
            $req->{$methodn}($value);
        }

        $client = new $clientClass($req);
        $resp = $client->executeWithApiResponse($this->endpoint);


        // throw new PaymentProfileManagerException($errorMessages[0]->getCode() . " " . $errorMessages[0]
    }




}

