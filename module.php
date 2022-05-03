<?php

require "vendor/autoload.php";

use CustomerProfile;

class PaymentProfileManagerModule extends Module {

    public $customerProfile;

    public function __construct() {

        parent::__construct();

        $profileId = "1915351471";  //Profile id for Jose on authorize.net
        $this->customerProfile = new CustomerProfile($profileId);
    }

    
    // I am just going to try to get all of the customer's payment profiles here.
    public function showAll() {

        $paymentProfiles = $this->customerProfile->getPaymentProfiles();

        $tpl = new Template("cards");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["paymentProfiles" => $paymentProfiles]);
    }

    // Show a form for adding a new payment profile
    public function create() {

        $tpl = new Template("create");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render();
    }


    // Shows one profile in an editable form.
    public function edit($id) {
        
        var_dump("Hello from edit");exit;
    }


    // Save a new payment profile
    public function save() {

        $paymentProfile = $this->getRequest()->getBody();

        if(empty($paymentProfile->id)) {
            
            $this->customerProfile->addPaymentProfile($paymentProfile);

        } else {

            $this->customerProfile->updatePaymentProfile($paymentProfile);

        }

        return $this->showAll();
    }

    // Delete a payment profile
    public function delete($id) {

        $this->customerProfile->deletePaymentProfile($id);

        return redirect("/cards/show");
    }
}