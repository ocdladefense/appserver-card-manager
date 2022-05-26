<?php

class PaymentProfile {


    public $isDefault;
    public $id;
    public $cardType;
    public $cardNumber;
    public $lastFour;
    public $expirationDate;
    public $date;
    public $unmaskedExpirationDate;
    public $firstName;
    public $lastName;
    public $phone;
    public $fax;
    public $email;
    public $address;
    public $city;
    public $state;
    public $zip;
    public $country;
    public $dateIsMasked;


    public function __construct(){}

    public static function fromMaskedArray($masked) {

        $profile = new self();
        $profile->setDefault($masked->getDefaultPaymentProfile());
        $profile->id = $masked->getCustomerPaymentProfileId();
        $profile->cardType = $masked->getPayment()->getCreditCard()->getCardType();
        $profile->cardNumber = $masked->getPayment()->getCreditCard()->getCardNumber();
        $profile->expirationDate = $masked->getPayment()->getCreditCard()->getExpirationDate();

        $profile->dateIsMasked = self::isMaskedDate($profile->expirationDate);
        if(!$profile->dateIsMasked) $profile->date = new DateTime($profile->expirationDate);

        $profile->setPaymentProfileBillingInfo($masked->getBillTo());

        return $profile;
    }

    public static function fromMaskedArrays($masked) {

        $values = array_map("PaymentProfile::fromMaskedArray", $masked);

        $keys = array_map(function($p){

            return $p->Id();

        }, $values);

        return array_combine($keys, $values);
    }

    private static function isMaskedDate($date){

        return strpos($date, "X") === 0;
    }


    public function setDefault($status) {

        $this->isDefault = $status == true;
    }

    public function firstName() {

        return $this->firstName;
    }

    public function id() {

        return $this->id;
    }

    public function lastName() {

        return $this->lastName;
    }

    public function type() {

        return $this->cardType;
    }

    public function lastFour() {

        return substr($this->cardNumber, -4);
    }

    public function maskedCardNumber() {

        return $this->cardNumber;
    }

    public function expiresOn() {

        return $this->expirationDate;
    }

    public function expYear() {

        return $this->dateIsMasked ? "XX" : $this->date->format("Y");
    }

    public function expMonth() {

        return $this->dateIsMasked ? "XX" : $this->date->format("m");
    }

    public function phone() {

        return $this->phone;
    }

    public function email() {

        return $this->email;
    }

    public function address() {

        return $this->address;
    }

    public function city() {

        return $this->city;
    }

    public function state() {

        return $this->state;
    }

    public function zip() {

        return $this->zip;
    }

    public function country() {

        return $this->country;
    }

    public function isDefault() {

        return $this->isDefault;
    }


	public function setPaymentProfileBillingInfo($billTo) {

        if(empty($billTo)) return null;

        $this->phone = $billTo->getPhoneNumber();
        $this->fax = $billTo->getFaxNumber();
        $this->email = $billTo->getEmail();
        $this->firstName = $billTo->getFirstName();
        $this->lastName = $billTo->getLastName();
        $this->address = $billTo->getAddress();
        $this->city = $billTo->getCity();
        $this->state = $billTo->getState();
        $this->zip = $billTo->getZip();
        $this->country = $billTo->getCountry();
	}

    public function setExpirationDate($date) {

        $this->expirationDate = $date;
    }
}