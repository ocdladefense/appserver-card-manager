<?php

use net\authorize\api\contract\v1 as AnetAPI;

class MerchantAuthentication {


    public static function get() {

        self::validate();

        $merchantAuth = new Self();

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(AUTHORIZE_DOT_NET_MERCHANT_ID);
        $merchantAuthentication->setTransactionKey(AUTHORIZE_DOT_NET_TRANSACTION_KEY);

        return $merchantAuthentication;
    }
    

    public static function validate() {

        if(!defined("AUTHORIZE_DOT_NET_MERCHANT_ID")) {

            throw new PaymentProfileManagerException("The authorize.net constant 'AUTHORIZE_DOT_NET_MERCHANT_ID' is not defined in your config.php.");
        }

        if(empty(AUTHORIZE_DOT_NET_MERCHANT_ID)) {

            throw new PaymentProfileManagerException("The authorize.net constant 'AUTHORIZE_DOT_NET_MERCHANT_ID' is defined, but does not have a value. See your config.php.");
        }

        if(!defined("AUTHORIZE_DOT_NET_TRANSACTION_KEY")) {

            throw new PaymentProfileManagerException("The authorize.net constant 'AUTHORIZE_DOT_NET_TRANSACTION_KEY' is not defined in your config.php.");
        }

        if(empty(AUTHORIZE_DOT_NET_TRANSACTION_KEY)) {

            throw new PaymentProfileManagerException("The authorize.net constant 'AUTHORIZE_DOT_NET_TRANSACTION_KEY' is defined, but does not have a value. See your config.php.");
        }
    }
}