<?php


use net\authorize\api\contract\v1 as AuthNetAPI;
use net\authorize\api\controller as AuthNetController;
use net\authorize\api\constants\AuthNetEnvironment;



class AuthNetResponse {


    const RESPONSE_OK = "Ok";


    private $resp;

    


    public function __construct($resp) {
        $this->resp = $resp;
    }
    
    // Theses errors are probably due to programming errors,
    // so I'm just gonna throw the exception in the calling code.
    public function hasErrors($response) {

        return $response->getMessages()->getResultCode() != self::RESPONSE_OK;
    }


    public function getResponse() {

        return $this->response;
    }


    // Some errors should be handled in a user-friendly way...hence the next two methods.
    // (Feels like I'm on the verge of refactoring the way I work with the response)
    public function getErrorMessage() {

        return $this->response->getMessages()->getMessage()[0]->getText();
    }


    public function success() {

        return $this->response->getMessages()->getResultCode() == self::RESPONSE_OK;
    }

}