<?php

use function Mysql\select;
use net\authorize\api\constants\ANetEnvironment as AuthNetEnvironment;



class PaymentProfileManagerModule extends Module {

    const SHOW_EXPIRATION_DATES = true;

    private $env;

    private $customerProfileService;

    private $profileId;

    private $hasAuthorizeDotNet;
    

    public function __construct() {

        $this->user = current_user();

        $this->profileId = $this->user->getExternalCustomerProfileId();

        $this->env = AUTHORIZE_DOT_NET_USE_PRODUCTION_ENDPOINT ? AuthNetEnvironment::PRODUCTION : AuthNetEnvironment::SANDBOX; 
        
        $this->customerProfileService = CustomerProfileService::newFromEnvironment($this->env, $this->profileId);

        $this->hasAuthorizeDotNet = !empty($this->user->getExternalCustomerProfileId());

        parent::__construct();
    }


    
    // Retrive the current customer's payment profiles here.
    public function index() {

        $contactId = $this->user->getContactId();
        $redirect = "/customer/$contactId/save";

        if(!$this->hasAuthorizeDotNet && !AUTHORIZE_DOT_NET_AUTO_ENROLL) {

            $message = "Your don't have an Authorize.net customer profile.  Click <a href='$redirect'>here</a> to auto-enroll.";

            throw new Exception($message);

        } else if(!$this->hasAuthorizeDotNet && AUTHORIZE_DOT_NET_AUTO_ENROLL) {

            return redirect($redirect);
        }
        

        $req = new AuthNetRequest("authnet://GetCustomerProfile");
        $req->addProperty("customerProfileId", $this->profileId);
        
        $client = new AuthNetClient($this->env);

        $resp = $client->send($req);

        $payments = $resp->getPaymentProfiles();


        // Make this block optional, for now.
        if(false && self::SHOW_EXPIRATION_DATES) {

            $query = "SELECT Id, ExpirationDate__c, ExternalId__c FROM PaymentProfile__c WHERE Contact__c = '$contactId'";

            $api = $this->loadForceApi();

            $resp = $api->query($query);

            foreach($resp->getRecords() as $sObject) {

                $pp = $payments[$sObject["ExternalId__c"]];
                $pp->setExpirationDate($sObject["ExpirationDate__c"]);
            }


           //var_dump($payments);exit;
        } 


        $tpl = new Template("cards");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["paymentProfiles" => $payments]);
    }



    // Save or update a customer payment profile
    public function save() {

        $data = $this->getRequest()->getBody();

        $resp = $this->customerProfileService->savePaymentProfile($data);

        if(!$resp->success()) throw new Exception($resp->getErrorMessage());

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
    public function delete($id) {

        $this->customerProfileService->deletePaymentProfile($id);

        $api = $this->loadForceApi();
        $resp = PaymentProfile__c::delete($api, $id);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return redirect("/cards");
    }




    // Shows one profile in an editable form.
    public function edit($id = null) {

        $profile = null;

        if(!empty($id)) {

            $req = new AuthNetRequest("authnet://GetCustomerPaymentProfile");
            $req->addProperty("customerProfileId", $this->profileId);
            $req->addProperty("customerPaymentProfileId", $id);
            
            $client = new AuthNetClient($this->env);
            $resp = $client->send($req);
    
            $profile = $resp->getPaymentProfile();

            if(false) {
                $api = $this->loadForceApi();
                $sfpp = PaymentProfile__c::get($api, $profile->id);
                $profile->setExpirationDate($sfpp["ExpirationDate__c"]);
            }
        }

        $tpl = empty($id) ? new Template("create") : new Template("edit");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["profile" => $profile]);
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

        // Need to reload the user in the session!!!!!

        return redirect("/cards");
    }
}