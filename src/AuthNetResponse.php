<?php


use net\authorize\api\contract\v1 as AuthNetAPI;
use net\authorize\api\controller as AuthNetController;
use net\authorize\api\constants\AuthNetEnvironment;



class AuthNetResponse {


    const RESPONSE_OK = "Ok";


    private $response;


    public function __construct($resp) {

        $this->response = $resp;
    }
    

    public function hasErrors($response) {

        return $response->getMessages()->getResultCode() != self::RESPONSE_OK;
    }


    public function getResponse() {

        return $this->response;
    }


    public function getErrorMessage() {

        return $this->response->getMessages()->getMessage()[0]->getText();
    }


    public function success() {

        return $this->response->getMessages()->getResultCode() == self::RESPONSE_OK;
    }

}