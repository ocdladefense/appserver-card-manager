<?php

use function Mysql\select;

class PaymentProfileManagerModule extends Module {

    public function __construct() {

        parent::__construct();
    }

    
    // I am just going to try to get all of the customer's payment profiles here.
    public function showAll() {

        $customerProfile = $this->getCustomerProfile();

        if(empty($customerProfile)) {

            $message = "No authorize.net customer profile associated with the current user.";

            return $this->showMessage($message);
        }

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

        $query = "SELECT ContactId, Contact.AuthorizeDotNetCustomerProfileId__c FROM User WHERE Id = '" . $user->getId() . "'";

        $result = $api->query($query)->getRecord();
        
        $profileId = $result["Contact"]["AuthorizeDotNetCustomerProfileId__c"];
        $contactId = $result["ContactId"];

        $autoEnroll = true;

        if(empty($profileId) && $autoEnroll) {

            $profileId = $this->saveCustomer($contactId);
        }

        return empty($profileId) ? null : new CustomerProfile($profileId);
    }


    public function saveCustomer($contactId) {

        $isAccountAuthorized = false; // Set to true if contact should be allowed to make purchases with the accounts cards on file. (Future Use)

        $query = "SELECT Id, AccountId, Account.Name, FirstName, LastName, Email, AuthorizeDotNetCustomerProfileId__c FROM Contact WHERE Id = '$contactId'";

        $api = $this->loadForceApi();

        // Testing Area
        $contact = new stdClass();
        $contact->Id = $contactId;
        $contact->Ocdla_Member_Status__c = "H";
        $contact->AuthorizeDotNetCustomerProfileId__c = "0000000000000";

        $resp = $api->upsert("Contact", $contact);

        var_dump($resp);exit;

        // End Testing Area

        $contact = $api->query($query)->getRecord();

        $firstName = $contact["FirstName"];
        $lastName = $contact["LastName"];
        $accountName = "$firstName $lastName";
        $contactId = $contact["Id"];
        $email = $contact["Email"];

        $params = [
            "description" => $accountName,
            "customerId"  => $contactId,
            "email"       => $email . "1"
        ];

        $response = CustomerProfile::create($params);
        $profileId = $response->getCustomerProfileId();

        $contact = new stdClass();
        $contact->Id = $contactId;
        $contact->AuthorizeDotNetCustomerProfileId__c = $profileId;

        $resp = $api->upsert("Contact", $contact);

        var_dump($resp);exit;
    }


    // Return a user friendly error message.
    public function showMessage($message) {

        $tpl = new Template("message");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["message" => $message]);
    }
}