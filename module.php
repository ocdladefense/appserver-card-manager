<?php

use function Mysql\select;
use net\authorize\api\constants\ANetEnvironment as AuthNetEnvironment;



class PaymentProfileManagerModule extends Module {

    const SHOW_EXPIRATION_DATES = true;

    private $customerProfileService;
    

    public function __construct() {

        $this->user = current_user();

        $profileId = $this->user->getExternalCustomerProfileId();

        $env = AUTHORIZE_DOT_NET_USE_PRODUCTION_ENDPOINT ? AuthNetEnvironment::PRODUCTION : AuthNetEnvironment::SANDBOX; 
        
        $this->customerProfileService = CustomerProfileService::newFromEnvironment($env, $profileId);

        parent::__construct();
    }


    
    // Retrive the current customer's payment profiles here.
    public function index() {

        if($this->user->isGuest()) return $this->showMessage("<a href='/login'>Login</a> to see your saved payment methods.");

        if(empty($this->user->getExternalCustomerProfileId())) {

            $message = "Your don't have an Authorize.net customer profile.  Click <a href='/customer/enroll'>here</a> to auto-enroll.";

            return AUTHORIZE_DOT_NET_AUTO_ENROLL ? $this->enroll() : $this->showMessage($message);
        }
        

        $profile = $this->customerProfileService->getProfile();

        $payments = $profile->getPaymentProfiles();
        
        $payments = array_map("PaymentProfile::fromMaskedArray", $payments);


        // Make this block optional, for now.
        if(self::SHOW_EXPIRATION_DATES) {

            $api = $this->loadForceApi();
            $sfPaymentProfiles = PaymentProfile__c::all($api, $this->user->getContactId());


            foreach($payments as $pp) {
                foreach($sfPaymentProfiles as $sfpp) {
                    if($pp->Id() == $sfpp["ExternalId__c"]) {
                        $pp->setExpirationDate($sfpp["ExpirationDate__c"]);
                        break;
                    }
                }
            }
        } 


        $tpl = new Template("cards");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["paymentProfiles" => $payments]);
    }


    // Save or update a customer payment profile
    public function save() {

        $data = $this->getRequest()->getBody();

        $resp = $this->customerProfileService->savePaymentProfile($data);

        if(!$resp->success()) return $this->showMessage($resp->getErrorMessage());

        $paymentProfileId = empty($data->id) ? $resp->getCustomerPaymentProfileId() : $data->id;

        $this->savePaymentProfile__c($paymentProfileId, $data);

        return redirect("/cards");
    }


    public function savePaymentProfile__c($paymentProfileId, $data) {

        $contactId = $this->user->getContactId();
        $api = $this->loadForceApi();
        $paymentProfile__c = new PaymentProfile__c($api);
        $resp = $paymentProfile__c->save($contactId, $paymentProfileId, $data);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());
    }
    


    // Delete a payment profile
    public function deletePaymentProfile($id) {

        $this->customerProfileService->deletePaymentProfile($id);

        $api = $this->loadForceApi();
        $resp = PaymentProfile__c::delete($api, $id);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return redirect("/cards");
    }


    // Show a form for adding a new payment profile
    public function create() {

        $tpl = new Template("create");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render();
    }



    // Shows one profile in an editable form.
    public function edit($id) {

        $profile = $this->customerProfileService->getPaymentProfile($id);

        $profile = PaymentProfile::fromMaskedArray($profile);

        $api = $this->loadForceApi();
        $sfpp = PaymentProfile__c::get($api, $profile->id);
        $profile->setExpirationDate($sfpp["ExpirationDate__c"]);

        $tpl = new Template("edit");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["profile" => $profile]);
    }




    public function enroll() {

        $query = "SELECT ContactId FROM User WHERE Id = '" . current_user()->getId() . "'";

        $result = $this->loadForceApi()->query($query)->getRecord();
        
        $contactId = $result["ContactId"];

        return redirect("/customer/$contactId/save");
    }


    public function saveCustomer($contactId) {

        $isAccountAuthorized = false; //Should the contact be allowed to make purchases with the accounts cards on file? (Future Use)

        $query = "SELECT Id, AccountId, Account.Name, FirstName, LastName, Email, AuthorizeDotNetCustomerProfileId__c FROM Contact WHERE Id = '$contactId'";

        $api = $this->loadForceApi();

        $contact = $api->query($query)->getRecord();

        $firstName = $contact["FirstName"];
        $lastName = $contact["LastName"];
        $accountName = "$firstName $lastName";
        $contactId = $contact["Id"];
        $email = $contact["Email"];

        $params = [
            "description" => $accountName,
            "customerId"  => $contactId,
            "email"       => $email
        ];

        $response = $this->customerProfileService->create($params);

        $profileId = $response->getProfileId();

        $contact = new stdClass();
        $contact->Id = $contactId;
        $contact->AuthorizeDotNetCustomerProfileId__c = $profileId;

        $resp = $api->upsert("Contact", $contact);

        return redirect("/cards");
    }


    // Return a user friendly error message.
    public function showMessage($message) {

        $tpl = new Template("message");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["message" => $message]);
    }
}