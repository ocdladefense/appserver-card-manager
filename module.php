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

        $profile = $this->customerProfile->getPaymentProfile($id);
        
        $tpl = new Template("edit");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["profile" => $profile]);
    }


    // Save a new payment profile
    public function save() {

        $profile = $this->getRequest()->getBody();

        $isUpdate = empty($paymentProfile->id) ? false : true;

        $result = $this->customerProfile->savePaymentProfile($profile, $isUpdate);

        if($result !== true) {

            return "<a href='javascript:history.back()'>&#8592;&nbsp;Go back&nbsp;&nbsp;</a>$result";
        }

        return redirect("/cards/show");
    }

    // Delete a payment profile
    public function delete($id) {

        $this->customerProfile->deletePaymentProfile($id);

        return redirect("/cards/show");
    }
}