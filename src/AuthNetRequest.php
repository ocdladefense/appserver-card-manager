<?php


use net\authorize\api\contract\v1 as AuthNetAPI;
use net\authorize\api\controller as AuthNetController;
use net\authorize\api\constants\ANetEnvironment as AuthNetEnvironment;


class AuthNetRequest { 


    private $requestType; 


    private $body = array();

    

    public function __construct($requestTypeSchema) { 
        list($prefix,$requestType) = explode("authnet://",$requestTypeSchema);

        $this->requestType = $requestType;
    }


    public function addProperty($prop,$value) {
        $this->body[$prop] = $value;
    }

    public function removeProperty($prop) {
        unset($this->body[$prop]);
    }

    public function getBody() {
        return $this->body;
    }

    public function getRequestType() {
        return $this->requestType;
    }



 




}

