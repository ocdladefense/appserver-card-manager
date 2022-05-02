<?php

use Clickpdx\Core\Controller\ControllerBase;
use Clickpdx\Core\User\ForceUser;
use Clickpdx\OAuth\OAuthGrantTypes;
use Clickpdx\SfRestApiRequestTypes;
use Clickpdx\Http\HttpRequest;
use Clickpdx\ResourceLoader;
use Clickpdx\Salesforce\RestApiAuthenticationException;
use Clickpdx\Salesforce\RestApiInvalidUrlException;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

// require 'vendor/autoload.php'; 
define("AUTHORIZENET_LOG_FILE","phplog");


class CustomerProfile extends ControllerBase
{

	const RESPONSE_OK = "Ok";
	
	private $endpoint = null;
	
	private $merchantAuthentication = null;
	/**
	 *
	 *
	 * @method setupEndpoint
	 *
	 * @description Process a debit/credit card against an Authorize.net merchant account.
	 *
	 * @info https://developer.authorize.net/hello_world/testing_guide/
	 */
	private function setupEndpoint(){
		$this->endpoint = \setting('ccAuthorize.useSandbox') ? \setting('ccAuthorize.sandboxEndpoint') : \setting('ccAuthorize.endpoint');
		
		$this->merchantAuthentication = $this->getMerchantAuth();
	}
	
	
	/**
	 *
	 *
	 * @method getCustomerProfileAction
	 *
	 * @description Retrieves a representation of CIM CustomerProfile 
	 *	for the given <customerProfileId>
	 *
	 * @info 
	 */
	public function getCustomerProfileAction()
	{	
		
		$customerProfileId = $_POST['customerProfileId'];

		$result = $this->getCustomerProfile($customerProfileId);
		
		return $result;
	}
	
	






	/**
	 *
	 *
	 * @method getFullCustomerProfileAction
	 *
	 * @description Retrieves a representation of CIM CustomerProfile 
	 *	for the given <customerProfileId>
	 *
	 * @info 
	 */
	public function getFullCustomerProfileAction($customerProfileId)
	{	
		$entityBody = file_get_contents('php://input');
		$json = json_decode($entityBody);
		$customerProfileId = $json->customerProfileId;
		$customerProfileId = $customerProfileId ?: $_POST['customerProfileId'];

		if(empty($customerProfileId)) {
			return new stdClass;
		}
		
		
		$customerProfile = $this->getCustomerProfile($customerProfileId);
		
		return $customerProfile;
	}
	
	
	
	/**
	 *
	 *
	 * @method getCustomerPaymentProfileAction
	 *
	 * @description Retrieves a representation of CIM CustomerProfile 
	 *	for the given <customerProfileId>
	 *
	 * @info 
	 */
	public function getCustomerPaymentProfileAction()
	{	
		
		$customerPaymentProfileId = $_POST['customerPaymentProfileId'];

		$result = $this->getCustomerPaymentProfile($customerPaymentProfileId);
		
		return $result;
	}

	


	public function getFullCustomerProfileForProfileId($cprofileId) {
		// var_dump($customerProfileId);
		// exit;
		return $this->getCustomerProfile($cprofileId);
	}
	/**
	 * Retrieve the customer's profile from Authorize.net.
	 *
	 * 
	 */
	private function getCustomerProfile($cprofileId)
	{	
		$this->setupEndpoint();
		
		$forJson = array();
		
		try
		{

			$request = new AnetAPI\GetCustomerProfileRequest();
			$request->setMerchantAuthentication($this->merchantAuthentication);
			$request->setCustomerProfileId($cprofileId);
			$controller = new AnetController\GetCustomerProfileController($request);
			$response = $controller->executeWithApiResponse($this->endpoint); 
		
			// Cust profile.
			$profilec = $response->getProfile();

			// Profile object to return for the customer.
			$profile = array(
				'customerProfileId' 	=> $profilec->getCustomerProfileId(),
				'merchantCustomerId' 	=> $profilec->getMerchantCustomerId(),
				'description' 			=> $profilec->getDescription(),
				'email' 				=> $profilec->getEmail()
			);

			
			// Customer's saved payment profiles.
			$profiles = array_map(function($masked) {
				// $masked = $pp[$i];
				$billTo = $this->getPaymentProfileBillToAddress($masked);

				
				$payment = $masked->getPayment();
				$card = $payment->getCreditCard();

				return array(
					'defaultPaymentProfile' 	=> $masked->getDefaultPaymentProfile(),
					'customerPaymentProfileId' 	=> $masked->getCustomerPaymentProfileId(),
					'customerProfileId' 		=> $masked->getCustomerProfileId(),
					'cardType' 					=> $card->getCardType(),
					'cardNumber' 				=> $card->getCardNumber(),
					'expirationDate' 			=> $card->getExpirationDate(),
					'billToAddress' 			=> $billTo
				);
			}, $profilec->getPaymentProfiles());

			
			return array('customerProfile' => $profile, 'paymentProfiles' => $profiles);
		}
		catch(\Exception $e)
		{
			$forJson = array('error' => $e->getMessage());
		}
	}



	public function getCustomerPaymentProfile($customerProfileId,$customerPaymentProfileId){
		$this->setupEndpoint();

		$forJson = array();
		
		//request requires customerProfileId and customerPaymentProfileId
		$request = new AnetAPI\GetCustomerPaymentProfileRequest();
		$request->setMerchantAuthentication($this->merchantAuthentication);
		$request->setRefId($refId);
		$request->setCustomerProfileId($customerProfileId);
		$request->setCustomerPaymentProfileId($customerPaymentProfileId);
		
		$controller = new AnetController\GetCustomerPaymentProfileController($request);
		$response = $controller->executeWithApiResponse($this->endpoint);
		
		$this->getCustomerPaymentProfileResponseResults($response);
		exit;
	}

	private function getTransactionResponseResults($response){}
	

	private function getCustomerPaymentProfileResponseResults($response){
		if(($response != null)){
			if ($response->getMessages()->getResultCode() == "Ok")
			{
				echo "GetCustomerPaymentProfile SUCCESS: " . "\n";
				echo "Customer Payment Profile Id: " . $response->getPaymentProfile()->getCustomerPaymentProfileId() . "\n";
				echo "Customer Payment Profile Billing Address: " . $response->getPaymentProfile()->getbillTo()->getAddress(). "\n";
				echo "Customer Payment Profile Card Last 4 " . $response->getPaymentProfile()->getPayment()->getCreditCard()->getCardNumber(). "\n";

				if($response->getPaymentProfile()->getSubscriptionIds() != null) 
				{
					if($response->getPaymentProfile()->getSubscriptionIds() != null)
					{

						echo "List of subscriptions:";
						foreach($response->getPaymentProfile()->getSubscriptionIds() as $subscriptionid)
							echo $subscriptionid . "\n";
					}
				}
			}
			else
			{
				echo "GetCustomerPaymentProfile ERROR :  Invalid response\n";
				$errorMessages = $response->getMessages()->getMessage();
					echo "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
			}
		}
		else{
			echo "NULL Response Error";
		}

	}	


	private function getCustomerProfileResponseResults($response)
	{
		$result = array();
		
		
		if ($response != null) 
		{
			if($response->getMessages()->getResultCode() == self::RESPONSE_OK)
			{
				$tresponse = $response->getTransactionResponse();

				if ($tresponse != null && $tresponse->getMessages() != null)   
				{
					$result['TransactionResponseCode'] = $tresponse->getResponseCode();
					$result['TransactionResponseAuthorizationCode'] = $tresponse->getAuthCode();
					$result['TransactionResponseId'] = $tresponse->getTransId();
					$result['TransactionResponseMessageCode'] =  $tresponse->getMessages()[0]->getCode();
					$result['TransactionResponseDescription'] =  $tresponse->getMessages()[0]->getDescription();
				}
				else
				{
					// echo "Transaction Failed \n";
					if($tresponse->getErrors() != null)
					{
						$result['TransactionResponseMessageCode'] =  "Error code: " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
						$result['TransactionResponseDescription'] =  $tresponse->getErrors()[0]->getErrorText() . "\n";            
					}
				}
			}
			else
			{
				// echo "Transaction Failed \n";
				$tresponse = $response->getTransactionResponse();
				if($tresponse != null && $tresponse->getErrors() != null)
				{
					$result['TransactionResponseMessageCode'] = "Error code: " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
					$result['TransactionResponseDescription'] = $tresponse->getErrors()[0]->getErrorText() . "\n";                      
				}
				else
				{
					$result['TransactionResponseMessageCode'] = "Error code: " . $response->getMessages()->getMessage()[0]->getCode() . "\n";
					$result['TransactionResponseDescription'] = $response->getMessages()->getMessage()[0]->getText() . "\n";
				}
			}      
		}
		else
		{
			$result['TransactionError']= "Charge Credit Card Null response returned";
		}
		

		return $result;
	}
	/**
	 *
	 *
	 * @method executeRequest
	 *
	 * @description 
	 *
	 * @param $request
	 *
	 * @param $transactionRequestType
	 *
	 * @param $controller
	 *
	 */
	private function getMerchantAuth()
	{
		$merchantId = \setting('ccAuthorize.useSandbox') ? \setting('ccAuthorize.sandboxMerchantId') :  \setting('ccAuthorize.merchantId');
		$merchantTransactionKey = \setting('ccAuthorize.useSandbox') ? \setting('ccAuthorize.sandboxMerchantTransactionKey') :  \setting('ccAuthorize.merchantTransactionKey');

		
		$merchantAuthentication = new AnetAPI\MerchantAuthenticationType(); 
		$merchantAuthentication->setName($merchantId);
		$merchantAuthentication->setTransactionKey($merchantTransactionKey);   
		$refId = 'ref' . time();
		
		return $merchantAuthentication;

	}
	

	
	public function createCustomerProfile($transId, $contactId)
  {

		$customerProfile = new AnetAPI\CustomerProfileBaseType();
		$customerProfile->setMerchantCustomerId($contactId);
		$customerProfile->setEmail('jbernal.web.dev@gmail.com');//$profileOptions['OrderApi__Work_Email__c']);
		$customerProfile->setDescription($profileOptions['OrderApi__Work_Email__c']?:'My Default Card');
      
	  $request = new AnetAPI\CreateCustomerProfileFromTransactionRequest();
	  $request->setMerchantAuthentication($this->getMerchantAuth());
	  $request->setTransId($transId);
	  // You can either specify the customer information in form of customerProfileBaseType object
	  $request->setCustomer($customerProfile);
	  //  OR   
 	  // You can just provide the customer Profile ID
      //$request->setCustomerProfileId("123343");

		$endpoint = \setting('ccAuthorize.useSandbox') ? \setting('ccAuthorize.sandboxEndpoint') : \setting('ccAuthorize.endpoint');
			
	  $controller = new AnetController\CreateCustomerProfileFromTransactionController($request);

	  $response = $controller->executeWithApiResponse($endpoint);


	  if (($response != null) && ($response->getMessages()->getResultCode() == self::RESPONSE_OK) )
	  {
		  $msg['profileId'] = $response->getCustomerProfileId();
	  }
	  else
	  {
		  $errorMessages = $response->getMessages()->getMessage();
		  $msg['errors'] = array("PROFILE ERROR : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText());
	  }
	  
	  return $msg;
	}


	private function getPaymentProfileBillToAddress($CustomerPaymentProfileMaskedType) {
		$billTo = $CustomerPaymentProfileMaskedType->getbillTo();
		return array(
			'phoneNumber' => $billTo->getPhoneNumber(),
			'faxNumber' => $billTo->getFaxNumber(),
			'email'					=> $billTo->getEmail(),
			'firstName'			=> $billTo->getFirstName(),
			'lastName'			=> $billTo->getLastName(),
			'company'				=> $billTo->getCompany(),
			'address'				=> $billTo->getAddress(),
			'city'					=> $billTo->getCity(),
			'state'					=> $billTo->getState(),
			'zip'						=> $billTo->getZip(),
			'country'				=> $billTo->getCountry()
		);
	}

}