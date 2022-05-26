<?php

class PaymentProfile__c {

    private $api;

    public function __construct($api) {

        $this->api = $api;
    }


    public static function all($api, $contactId) {

        $query = "SELECT Id, ExpirationDate__c, ExternalId__c FROM PaymentProfile__c WHERE Contact__c = '$contactId'";

        $resp = $api->query($query);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        return $resp->getRecords();
    }

    public function save($contactId, $pProfileId, $pProfile) {

        $expDate = $pProfile->expYear . "-" . $pProfile->expMonth;
        $isUpdate = !empty($pProfile->id);

        if($isUpdate) {

            $query = "SELECT Id from PaymentProfile__c WHERE ExternalId__c = '$pProfileId'";
            $resp = $this->api->query($query);
    
            if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());
    
            $id = $resp->getRecord()["Id"];
        }

        $paymentProfile = new stdClass();
        $paymentProfile->Id = $id;
        $paymentProfile->Contact__c = $contactId;
        $paymentProfile->ExpirationDate__c = $expDate;
        $paymentProfile->ExternalId__c = $pProfileId;
        $paymentProfile->PaymentGateway__c = "Authorize.net";

        $resp = $this->api->upsert("PaymentProfile__c", $paymentProfile);

        return $resp;
    }


    public static function delete($api, $externalId) {

        $query = "SELECT Id FROM PaymentProfile__c WHERE ExternalId__c = '$externalId'";

        $resp = $api->query($query);

        if(!$resp->success()) throw new PaymentProfileManagerException($resp->getErrorMessage());

        $PaymentProfileId = $resp->getRecord()["Id"];

        return $api->delete("PaymentProfile__c", $PaymentProfileId);
    }
}