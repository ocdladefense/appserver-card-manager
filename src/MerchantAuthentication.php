<?php

use net\authorize\api\contract\v1 as AnetAPI;

class MerchantAuthentication {

    public $merchantId;
    public $transactionKey;

    public function __construct($profileId = null) {

        $this->merchantId = "6gSVxaYj397";
        $this->transactionKey = "6FR49pDH5Jjum58g";
    }

    public static function getMerchantAuthentication() {

        $merchantAuth = new Self();

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($merchantAuth->merchantId);
        $merchantAuthentication->setTransactionKey($merchantAuth->transactionKey);

        return $merchantAuthentication;
    }
}