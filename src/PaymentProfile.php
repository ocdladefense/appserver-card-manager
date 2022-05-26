<?php

class PaymentProfile {


    public $isDefault;
    public $id;
    public $cardType;
    public $cardNumber;
    public $lastFour;
    public $expirationDate;
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


    public function __construct(){}

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

    public function expiresOn() {

        return substr($this->expirationDate, 0, -3);
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