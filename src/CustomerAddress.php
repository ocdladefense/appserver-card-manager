<?php

use net\authorize\api\contract\v1 as AuthNetAPI;



class CustomerAddress extends AuthNetAPI\CustomerAddressType {



    public static function fromMaskedArray($masked) {

        $profile = new self();
        $profile->setDefault($masked->getDefaultPaymentProfile());
        $profile->id = $masked->getCustomerPaymentProfileId();
        $profile->cardType = $masked->getPayment()->getCreditCard()->getCardType();
        $profile->cardNumber = $masked->getPayment()->getCreditCard()->getCardNumber();
        $profile->expirationDate = $masked->getPayment()->getCreditCard()->getExpirationDate();
        $profile->setPaymentProfileBillingInfo($masked->getBillTo());

        return $profile;
    }


}