<?php

use function Mysql\select;
use net\authorize\api\constants\ANetEnvironment as AuthNetEnvironment;
use net\authorize\api\contract\v1 as AuthNetAPI;



class PaymentProfileManagerModule extends Module {

    const SHOW_EXPIRATION_DATES = true;
    const SAVE_NEW_CUSTOMER_PROFILES_TO_SALESFORCE = true;
    const UPDATE_PAYMENT_PROFILE__C_S_OBJECTS = true;

    private $env;

    private $profileId;

    private $hasAuthorizeDotNet;
    

    public function __construct() {

        $this->user = current_user();

        $this->profileId = $this->user->getExternalCustomerProfileId();

        $this->env = AUTHORIZE_DOT_NET_USE_PRODUCTION_ENDPOINT ? AuthNetEnvironment::PRODUCTION : AuthNetEnvironment::SANDBOX; 

        $this->hasAuthorizeDotNet = !empty($this->user->getExternalCustomerProfileId());

        parent::__construct();
    }


    
    // Retrive the current customer's payment profiles here.
    public function list() {

        $contactId = $this->user->getContactId();
        $url = "/customer/$contactId/save";

        if(!$this->hasAuthorizeDotNet && !AUTHORIZE_DOT_NET_AUTO_ENROLL) {

            $message = "You don't have an Authorize.net customer profile.  Click <a href='$url'>here</a> to auto-enroll.";

            throw new Exception($message);

        } else if(!$this->hasAuthorizeDotNet && AUTHORIZE_DOT_NET_AUTO_ENROLL) {

            return redirect($url);
        }

        $req = new AuthNetRequest("authnet://GetCustomerProfile");
        $req->addProperty("customerProfileId", $this->profileId);
        
        $client = new AuthNetClient($this->env);

        $resp = $client->send($req);

        if(!$resp->success()) throw new Exception($resp->getErrorMessage());

        $payments = $resp->getPaymentProfiles();


        // Make this block optional, for now.
        if(self::SHOW_EXPIRATION_DATES) {

            $query = "SELECT Id, ExpirationDate__c, ExternalId__c FROM PaymentProfile__c WHERE Contact__c = '$contactId'";

            $api = $this->loadForceApi();

            $resp = $api->query($query);

            if(!$resp->success()) throw new Exception($resp->getErrorMessage());

            foreach($resp->getRecords() as $sObject) {

                $pp = $payments[$sObject["ExternalId__c"]];
                $pp->setExpirationDate($sObject["ExpirationDate__c"]);
            }
        }


        $tpl = new Template("cards");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["paymentProfiles" => $payments]);
    }



    public function save() {

        $data = $this->getRequest()->getBody();

        $isUpdate = !empty($data->id);

        return $isUpdate ? $this->update($data) : $this->insert($data);
    }


    public function insert($data) {

        $card = new AuthNetAPI\CreditCardType();
        $card->setCardNumber($data->cardNumber);
        $card->setExpirationDate($data->expYear . "-" . $data->expMonth);

        $paymentType = new AuthNetAPI\PaymentType();
        $paymentType->setCreditCard($card);

        $billTo = $this->getBillTo($data);


        $paymentProfile = new AuthNetAPI\CustomerPaymentProfileType();

        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($billTo);
        $paymentProfile->setPayment($paymentType);
        $paymentProfile->setDefaultPaymentProfile(!empty($data->default));


        $req = new AuthNetRequest("authnet://CreateCustomerPaymentProfile");

        $req->addProperty("customerProfileId", $this->profileId);
        $req->addProperty("paymentProfile", $paymentProfile);
        
        $client = new AuthNetClient($this->env);
        
        $resp = $client->send($req);

        if(!$resp->success()) throw new Exception($resp->getErrorMessage());

        if(self::UPDATE_PAYMENT_PROFILE__C_S_OBJECTS) $this->savePaymentProfile__c($resp->getCustomerPaymentProfileId(), $data);
        
        return redirect("/cards");
    }



    public function update($data) {

        $card = new AuthNetAPI\CreditCardType();
        $card->setCardNumber($data->cardNumber);
        $card->setExpirationDate($data->expYear . "-" . $data->expMonth);

        $paymentType = new AuthNetAPI\PaymentType();
        $paymentType->setCreditCard($card);

        $billTo = $this->getBillTo($data);


        $paymentProfile = new AuthNetAPI\CustomerPaymentProfileExType();
        $paymentProfile->setCustomerPaymentProfileId($data->id);
        $paymentProfile->setCustomerType('individual');
        $paymentProfile->setBillTo($billTo);
        $paymentProfile->setPayment($paymentType);
        $paymentProfile->setDefaultPaymentProfile(!empty($data->default));

        $req = new AuthNetRequest("authnet://UpdateCustomerPaymentProfile");

        $req->addProperty("customerProfileId", $this->profileId);
        $req->addProperty("paymentProfile", $paymentProfile);
        
        $client = new AuthNetClient($this->env);
        
        $resp = $client->send($req);

        if(!$resp->success()) throw new Exception($resp->getErrorMessage());

        if(self::UPDATE_PAYMENT_PROFILE__C_S_OBJECTS) $this->savePaymentProfile__c($data->id, $data);

        return redirect("/cards");
    }



    public function savePaymentProfile__c($paymentProfileId, $data) {

        $api = $this->loadForceApi();
        $expDate = $data->expYear . "-" . $data->expMonth;
        $isUpdate = !empty($data->id);
        $contactId = $this->user->getContactId();

        if($isUpdate) {

            $query = "SELECT Id from PaymentProfile__c WHERE ExternalId__c = '$paymentProfileId'";
            $resp = $api->query($query);
    
            if(!$resp->success()) throw new Exception($resp->getErrorMessage());

            $id = $resp->getRecord()["Id"];
        }

        $paymentProfile = new stdClass();
        $paymentProfile->Id = $id;
        $paymentProfile->Contact__c = $contactId;
        $paymentProfile->ExpirationDate__c = $expDate;
        $paymentProfile->ExternalId__c = $paymentProfileId;
        $paymentProfile->PaymentGateway__c = "Authorize.net";

        $resp = $api->upsert("PaymentProfile__c", $paymentProfile);

        if(!$resp->success()) throw new Exception($resp->getErrorMessage());

        return $resp;

    
    }
    


    // Delete a payment profile
    public function delete($id) {

        $req = new AuthNetRequest("authnet://DeleteCustomerPaymentProfile");
        $req->addProperty("customerProfileId", $this->profileId);
        $req->addProperty("customerPaymentProfileId", $id);
        
        $client = new AuthNetClient($this->env);
        $resp = $client->send($req);

        if(!$resp->success()) throw new Exception($resp->getErrorMessage());


        if(self::UPDATE_PAYMENT_PROFILE__C_S_OBJECTS) {
            $api = $this->loadForceApi();
            $query = "SELECT Id FROM PaymentProfile__c WHERE ExternalId__c = '$id'";
            $resp = $api->query($query);
            $recordId = $resp->getRecord()["Id"];
    
            $resp = $api->delete("PaymentProfile__c", $recordId);
        }
        
        return redirect("/cards");
    }




    // Shows one profile in an editable form.
    public function edit($id = null) {

        $profile = new PaymentProfile();

        if(!empty($id)) {

            $req = new AuthNetRequest("authnet://GetCustomerPaymentProfile");
            $req->addProperty("customerProfileId", $this->profileId);
            $req->addProperty("customerPaymentProfileId", $id);
            
            $client = new AuthNetClient($this->env);
            $resp = $client->send($req);
    
            $profile = $resp->getPaymentProfile();
            

            if(self::SHOW_EXPIRATION_DATES) {
                $api = $this->loadForceApi();
                $query = "SELECT ExpirationDate__c FROM PaymentProfile__c WHERE ExternalId__c = '$id'";
                $sfpp = $api->query($query)->getRecord();
                if(!empty($sfpp)) $profile->setExpirationDate($sfpp["ExpirationDate__c"]);
            }
        }

        $tpl = new Template("edit");
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["profile" => $profile]);
    }


    public function getBillTo($data) {

        $billto = new AuthNetAPI\CustomerAddressType();
        $billto->setFirstName($data->firstName);
        $billto->setLastName($data->lastName);
        // $billto->setCompany("Souveniropolis");
        $billto->setAddress($data->address);
        $billto->setCity($data->city);
        $billto->setState($data->state);
        $billto->setZip($data->zip);
        $billto->setCountry("USA");
        $billto->setPhoneNumber($data->phone);

        return $billto;
    }


    // Shows one profile in an editable form.
    public function editRecord($type, $id = null) {


        // Check for post data and if present then save?
        // Do the save and return $this->view($type);
        // Or return whatever is in the ?retURL querystring.
        $record = null;
        $type = "PaymentMethod";
        //  $action = CRUD

        // $record = !empty($id) ? $this->loadAppropriateRecordObject($id) : $this->loadAppropriateEmptyRecordObject();

        $record = empty($id) ? new stdClass() : $this->loadRecord($type);

        $tplType = empty($id) ? "create" : "edit";

        $tplName = implode("-",[$type,$tplType]);

        $tpl = new Template($tplName);
        $tpl->addPath(__DIR__ . "/templates");

        return $tpl->render(["record" => $record]);
    }

    private function loadRecord($type) {
        return new stdClass();
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

        $profile = new AuthNetApi\CustomerProfileType();
        $profile->setDescription($accountName);
        $profile->setMerchantCustomerId($contactId);
        $profile->setEmail($email);

        $req = new AuthNetRequest("authnet://CreateCustomerProfile");
        $req->addProperty("profile", $profile);
        
        $client = new AuthNetClient($this->env);
        $resp = $client->send($req);

        $profileId = $resp->getProfileId();


        if(self::SAVE_NEW_CUSTOMER_PROFILES_TO_SALESFORCE) {
            $contact = new stdClass();
            $contact->Id = $contactId;
            $contact->AuthorizeDotNetCustomerProfileId__c = $profileId;

            $resp = $api->upsert("Contact", $contact);
        }
        
        $this->user->setExternalCustomerProfileId($profileId);
        \Session::setUser($this->user);

        return redirect("/cards");
    }
}

function recordPreprocess($paymentData) {

    return ["card" => $paymentData];

}