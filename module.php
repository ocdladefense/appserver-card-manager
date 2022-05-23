<?php

use function Mysql\select;
use net\authorize\api\constants\ANetEnvironment as AuthNetEnvironment;



class PaymentProfileManagerModule extends Module {

    const SHOW_EXPIRATION_DATES = true;

    public $authNetEnvironment;
    

    public function __construct() {

        $this->authNetEnvironment = AUTHORIZE_DOT_NET_USE_PRODUCTION_ENDPOINT ? AuthNetEnvironment::PRODUCTION : AuthNetEnvironment::SANDBOX;  

        parent::__construct();
    }


    
    // Retrive the current customer's payment profiles here.
    public function index() {

        $user = current_user();

        if($user->isGuest()) return $this->showMessage("<a href='/login'>Login</a> to see your saved payment methods.");

        if(empty($user->getExternalCustomerProfileId())) {

            $message = "Your don't have an Authorize.net customer profile.  Click <a href='/customer/enroll'>here</a> to auto-enroll.";

            return AUTHORIZE_DOT_NET_AUTO_ENROLL ? $this->enroll() : $this->showMessage($message);
        }
        

        $profile = $this->getCustomerProfile();

        $payments = $profile->getPaymentProfiles();
        
        $payments = array_map("PaymentProfile::fromMaskedArray", $payments);


        // Make this block optional, for now.
        if(self::SHOW_EXPIRATION_DATES) {

            $api = $this->loadForceApi();
            $sfPaymentProfiles = PaymentProfile__c::all($api, $user->getContactId());


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


    // Save or update a customer payment profile
    public function save() {

        $pProfile = $this->getRequest()->getBody();

        var_dump($pProfile);exit;

        $cp = $this->getCustomerProfile();

        $pProfileId = $cp->savePaymentProfile($pProfile);

        if(!$cp->success()) return $this->showMessage($cp->getErrorMessage());

        $contactId = $cp->getCustomerId();
        $api = $this->loadForceApiFromFlow("usernamepassword");
        $paymentProfile__c = new PaymentProfile__c($api);
        $resp = $paymentProfile__c->save($contactId, $pProfileId, $pProfile);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return redirect("/cards");
    }


    // Delete a payment profile
    public function delete($id) {

        $user = current_user();

        $profileId = $user->getExternalCustomerProfileId();

        $req = new AuthNetRequest("authnet://DeleteCustomerPaymentProfile");
        $req->addProperty("customerProfileId", $profileId);
        $req->addProperty("customerPaymentProfileId", $id);
        
        $client = new AuthNetClient($this->authNetEnvironment);
        $resp = $client->send($req);

        return redirect("/cards");
    }


    public function getCustomerProfile() {

        $user = current_user();

        $profileId = $user->getExternalCustomerProfileId();

        $req = new AuthNetRequest("authnet://GetCustomerProfile");
        $req->addProperty("customerProfileId", $profileId);
        
        $client = new AuthNetClient($this->authNetEnvironment);
        $resp = $client->send($req);

        return $resp->getProfile();
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

        $response = CustomerProfile::create($params);
        $profileId = $response->getCustomerProfileId();

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