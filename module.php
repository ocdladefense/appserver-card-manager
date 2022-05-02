<?php

require "vendor/autoload.php";

use CustomerProfile;

class PaymentProfileManagerModule extends Module {

    public function __construct() {

        parent::__construct();
    }

    
    // I am just going to try to get all of the customer's payment profiles here.
    public function showAllPaymentProfiles() {

        $profileId = "1915351471";  //Profile id for Jose on authorize.net

        $customerProfile = new CustomerProfile($profileId);

        $paymentProfiles = $customerProfile->getPaymentProfiles();

        $tpl = new Template("cards");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["paymentProfiles" => $paymentProfiles]);
    }

    // Show a form for adding a new payment profile
    public function create() {}


    // Shows one profile in an editable form.
    public function edit($id) {}


    // Save a new payment profile
    public function save() {

        $profileId = "1915351471";  //Profile id for Jose on authorize.net

        $customerProfile = new CustomerProfile($profileId);

        $paymentProfile = $this->getRequest()->getBody();

        if(empty($paymentProfile->id)) {
            
            $customerProfile->addNewPaymentProfile($paymentProfile);

        } else {

            $customerProfile->updatePaymentProfile($paymentProfile);

        }

        return $this->showAllPaymentProfiles();
    }

    // Delete a profile
    public function delete($id) {}
}