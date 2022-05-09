<?php

use function Mysql\select;

class PaymentProfileManagerModule extends Module {

    public function __construct() {

        parent::__construct();
    }

    
    // I am just going to try to get all of the customer's payment profiles here.
    public function showAll() {

        $customerProfile = $this->getCustomerProfile();

        if(empty($customerProfile)) return $this->showMessage("No authorize.net customer profile associated with the current user.");

        $paymentProfiles = $customerProfile->getPaymentProfiles();

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

        $customerProfile = $this->getCustomerProfile();

        $profile = $customerProfile->getPaymentProfile($id);
        
        $tpl = new Template("edit");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["profile" => $profile]);
    }


    // Save a new payment profile
    public function save() {

        $profile = $this->getRequest()->getBody();

        $customerProfile = $this->getCustomerProfile();

        $result = $customerProfile->savePaymentProfile($profile);

        if($result !== true) return $this->showMessage($result);

        return redirect("/cards/show");
    }

    // Delete a payment profile
    public function delete($id) {

        $customerProfile = $this->getCustomerProfile();

        $customerProfile->deletePaymentProfile($id);

        return redirect("/cards/show");
    }


    public function getCustomerProfile() {

        $user = current_user();

        $api = $this->loadForceApi();

        $query = "SELECT Contact.AuthorizeDotNetCustomerProfileId__c from User where Id = '" . $user->getId() . "'";

        $result = $api->query($query)->getRecord();
        
        $profileId = $result["Contact"]["AuthorizeDotNetCustomerProfileId__c"];

        //$profileId = "1915351471";  //Profile id for Jose on authorize.net

        return empty($profileId) ? null : new CustomerProfile($profileId);
    }


    // Return a user friendly error message.
    public function showMessage($message) {

        $tpl = new Template("message");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["message" => $message]);
    }
}