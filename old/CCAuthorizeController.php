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


class CCAuthorizeController extends ControllerBase
{

	const RESPONSE_OK = "Ok";
	
	private $testBillTo = array(
		'BillingFirstName' => 'Jose',
		'BillingLastName' => 'Bernal',
		'BillingStreet' => '1040 NW 10 AVE APT 219',
		'BillingCity' => 'Portland',
		'BillingStateCode' => 'OR',
		'BillingPostalCode' => '97209'
	);
	

	private $testOrderInfo =  array(
		'OrderNumber' => '001111',
		'OrderSummary' => 'An order for OCDLA products.'
	);

	
	
	public function processCcPassAll()
	{	
		
		$ccNum = $_POST['ccNum'];
		$ccExp = $_POST['ccExp'];
		$amount = $_POST['amount'];
		$ccCode = $_POST['ccCode'];
		
		return '{"TransactionResponseCode":"1","TransactionResponseAuthorizationCode":"AP21MB","TransactionResponseId":"60017619040","TransactionResponseMessageCode":"1","TransactionResponseDescription":"This transaction has been approved."}';
	}
	
	
	public function processCcTest()
	{	
		ini_set('display_errors','1');
		$ccNumber = '4111111111111111';
		$ccExpiration = '0820';
		$ccCode = '142';
		$amount = 152.40;

		

		$result = $this->processCc($ccNumber,$ccExpiration,$ccCode,$amount,$this->testBillTo,$this->testOrderInfo);
		return "<pre>" . print_r($result,true)."</pre>";
	}
	
	
	
	public function processCcWithProfileTest($profileId = null, $paymentProfileId = null)
	{	
		ini_set('display_errors','1');
		// $ccNumber = '4111111111111111';
		// $ccExpiration = '0820';
		// $ccCode = '142';
		$amount = 152.40;
		$orderInfo = $this->testOrderInfo;
		// $billTo = $this->testBillTo;


		$result = $this->processCcWithPaymentProfile($amount, $orderInfo, $profileId, $paymentProfileId);

		return $result;
		// return "<pre>" . print_r($result,true)."</pre>";
	}
	
	
	
	/**
	 *
	 *
	 * @method processCcFromPost
	 *
	 * @description Process a debit/credit card against an Authorize.net merchant account.
	 *
	 * @info https://developer.authorize.net/hello_world/testing_guide/
	 */
	public function processOrderEntryApp()
	{
		
		$ccNum = $_POST['ccNum'];
		$ccExp = $_POST['ccExp'];
		$ccCode = $_POST['ccCode'];
		$profileId = $_POST['AuthorizeDotNetCustomerProfileId__c'];

		/*
		if(empty($profileId)) {
			print "ProfileId is empty.";
		} else {
			print "ProfileId IS NOT EMPTY";
		}
		*/

		$paymentProfileId = $_POST['authNetPaymentProfileId'];
		$amount = $_POST['amount'];

		
		$orderInfo = array(
			'OrderNumber' 	=> $_POST['OrderNumber'],
			'OrderSummary' 	=> $_POST['shoppingCartSummary']
		);
		
		$billTo = array(
			'SavePaymentMethod' 	=> true,
			'BillingContactId' 		=> $_POST['ContactId'],
			'BillingFirstName' 		=> $_POST['BillingFirstName'],
			'BillingLastName' 		=> $_POST['BillingLastName'],
			'BillingStreet' 		=> $_POST['BillingStreet'],
			'BillingEmail'			=> $_POST['BillingEmail'],
			'Description'			=> $_POST['description'],
			'BillingCity' 			=> $_POST['BillingCity'],
			'BillingState' 			=> $_POST['BillingStateCode'],
			'BillingZip'			=> $_POST['BillingPostalCode']
		);
		
		


		// If a customer profile is not selected we automatically save a new profile.
		// If both customerId and paymentProfileId have been provided use the card on file.
		// Otherwise we should place a new order but NOT save a duplicate customer profile.
		if(empty($profileId)) {
			$options = false; // array("saveProfile" => false);
			$result = $this->processCc($ccNum, $ccExp, $ccCode, $amount, $billTo, $orderInfo, $options);
		} else if(!empty($profileId) && !empty($paymentProfileId)) {
			$result = $this->processCcWithPaymentProfile($amount, $orderInfo, $profileId, $paymentProfileId);
		} else {
			$result = $this->processCc($ccNum, $ccExp, $ccCode, $amount, $billTo, $orderInfo);
		}
		
		return $result;
	}
	
	
	

	public function processCcFromPost()
	{
		
		$ccNum = $_POST['ccNum'];
		$ccExp = $_POST['ccExp'];
		$ccCode = $_POST['ccCode'];
		$profileId = $_POST['AuthorizeDotNetCustomerProfileId__c'];
		$paymentProfileId = $_POST['authNetPaymentProfileId'];
		$amount = $_POST['amount'];

		
		$orderInfo = array(
			'OrderNumber' => $_POST['OrderNumber'],
			'OrderSummary' => $_POST['shoppingCartSummary']
		);
		
		$billTo = array(
			'SavePaymentMethod' => true,
			'BillingContactId' =>  $_POST['ContactId'],
			'BillingFirstName' =>  $_POST['BillingFirstName'],
			'BillingLastName' =>  $_POST['BillingLastName'],
			'BillingStreet' =>  $_POST['BillingStreet'],
			'BillingEmail'					=> $_POST['BillingEmail'],
			'Description'					=> $_POST['description'],
			'BillingCity' =>  $_POST['BillingCity'],
			'BillingState' =>  $_POST['BillingStateCode'],
			'BillingZip' =>  $_POST['BillingPostalCode']
		);
		
		
		if(isset($profileId) && !empty($paymentProfileId)){
			$result = $this->processCcWithPaymentProfile($amount, $orderInfo, $profileId, $paymentProfileId);
		}
		
		else {
			$result = $this->processCc($ccNum, $ccExp, $ccCode, $amount, $billTo, $orderInfo);
		}
		
		return $result;
	}



	
	private function processCcWithPaymentProfile($amount, $orderInfo, $profileId, $paymentProfileId){
		$endpoint = \setting('ccAuthorize.useSandbox') ? \setting('ccAuthorize.sandboxEndpoint') : \setting('ccAuthorize.endpoint');
			
		$paymentProfile = new AnetAPI\PaymentProfileType();
		$paymentProfile->setPaymentProfileId($paymentProfileId);
		
		$profileToCharge = new AnetAPI\CustomerProfilePaymentType();
		$profileToCharge->setCustomerProfileId($profileId);
		$profileToCharge->setPaymentProfile($paymentProfile);

		$forJson = array();
		
		try
		{		

			$order = new AnetAPI\OrderType();
			$order->setInvoiceNumber($orderInfo['OrderNumber']);
			$order->setDescription($orderInfo['OrderSummary']);
		
			/*
			$bill = new AnetAPI\CustomerAddressType();
			$bill->setFirstName($billTo['BillingFirstName']);
			$bill->setLastName($billTo['BillingLastName']);
			$bill->setAddress($billTo['BillingStreet']);
			$bill->setCity($billTo['BillingCity']);
			$bill->setState($billTo['BillingState']);
			$bill->setZip($billTo['BillingZip']);
			*/
		
			// $paymentOne = new AnetAPI\PaymentType();
			// $paymentOne->setCreditCard($creditCard);

			// Create a transaction
			$transactionRequestType = new AnetAPI\TransactionRequestType();
			$transactionRequestType->setTransactionType("authCaptureTransaction");   
			$transactionRequestType->setAmount($amount);
			// $transactionRequestType->setPayment($paymentOne);	
			$transactionRequestType->setProfile($profileToCharge);			
			$transactionRequestType->setOrder($order);
			// $transactionRequestType->setBillTo($bill);
			// $transactionRequestType->setCustomer($customer);
			// $transactionRequestType->setSolution($solution);

		
			// ->addToLineItems ->setLineItems @param \net\authorize\api\contract\v1\LineItemType[] $lineItems
			$merchantAuthentication = $this->getMerchantAuth();
		
			$request = new AnetAPI\CreateTransactionRequest();
			$request->setMerchantAuthentication($merchantAuthentication);
			$request->setRefId($refId);
		
			$request->setTransactionRequest($transactionRequestType);
			$controller = new AnetController\CreateTransactionController($request);
		
			$response = $controller->executeWithApiResponse($endpoint); 
			
			// print "<h1>".$response->getMessages()->getResultCode()."</h1>";
			// print "<pre>".print_r($response,true)."</pre>";
			$forJson = $this->getTransactionResponseResults($response);
			
			if($billTo['SavePaymentMethod'])
			{
				if(empty($forJson['TransactionResponseId']) || empty($billTo['BillingContactId']))
				{
					$forJson['PROFILE ERROR'] = 'Error when saving Profile from Transaction: either the Transaction was unsuccessful or the Merchant did not submit a valid Contact ID.';
				}
				else
				{
					$profileResponses = $this->createCustomerProfile($forJson['TransactionResponseId'],$billTo['BillingContactId'],$billTo['BillingEmail'],$billTo['Description']);
					$forJson = array_merge($forJson,$profileResponses);
				}
			}
		}
		catch(\Exception $e)
		{
			$forJson = array('error' => $e->getMessage());
		}
		finally
		{
			$forJson = array_merge($forJson,$orderInfo);
			$forJson['ApiEndpointName'] = \setting('ccAuthorize.useSandbox') ? 'ApiEndpointSandbox' : 'ApiEndpointProduction';
			return $forJson;
		}
	
}




	

	
	
	/**
	 * Show an easy to read list of projects.
	 *
	 * A list of projects that are currently being worked on.
	 */
	private function processCc($ccNumber,$ccExpiration,$ccCode,$amount,$billTo,$orderInfo,$createProfile = true)
	{	
		/*
		if(false === $createProfile) {
			print "Create Profile is false.";
		} else {
			print "Create Profile is true.";
		}
		*/
		$forJson = array();
		
		try
		{		
		// Common setup for API credentials  


		// Create the payment data for a credit card
			$creditCard = new AnetAPI\CreditCardType();
			$creditCard->setCardNumber($ccNumber);  
			$creditCard->setExpirationDate($ccExpiration);
			$creditCard->setCardCode($ccCode);		
		

		
			$order = new AnetAPI\OrderType();
			$order->setInvoiceNumber($orderInfo['OrderNumber']);
			$order->setDescription($orderInfo['OrderSummary']);
		
		
			// $customer = new AnetAPI\CustomerDataType();  
			// $customer->setType('Account');
			// $customer->setId('0001111');
		
		
		
			$bill = new AnetAPI\CustomerAddressType();
			$bill->setFirstName($billTo['BillingFirstName']);
			$bill->setLastName($billTo['BillingLastName']);
			$bill->setAddress($billTo['BillingStreet']);
			$bill->setCity($billTo['BillingCity']);
			$bill->setState($billTo['BillingState']);
			$bill->setZip($billTo['BillingZip']);
			$bill->setCountry('USA');
		
			$email = $billTo["BillingEmail"];
			// $solution = new AnetAPI\SolutionType();
			// $solution->setId('CLICKPDX');
		
		
			$paymentOne = new AnetAPI\PaymentType();
			$paymentOne->setCreditCard($creditCard);

			// Create a transaction
			$transactionRequestType = new AnetAPI\TransactionRequestType();
			$transactionRequestType->setTransactionType("authCaptureTransaction");   
			$transactionRequestType->setAmount($amount);
			$transactionRequestType->setPayment($paymentOne);				
			$transactionRequestType->setOrder($order);
			$transactionRequestType->setBillTo($bill);
			// $transactionRequestType->setCustomer($customer);
			// $transactionRequestType->setSolution($solution);

		
			// ->addToLineItems ->setLineItems @param \net\authorize\api\contract\v1\LineItemType[] $lineItems
			$merchantAuthentication = $this->getMerchantAuth();
		
			$request = new AnetAPI\CreateTransactionRequest();
			$request->setMerchantAuthentication($merchantAuthentication);
			$request->setRefId($refId);
		
			$request->setTransactionRequest($transactionRequestType);
			$controller = new AnetController\CreateTransactionController($request);
		
			// $response = $this->executeRequest($request, $controller);  
			$endpoint = \setting('ccAuthorize.useSandbox') ? \setting('ccAuthorize.sandboxEndpoint') : \setting('ccAuthorize.endpoint');
			$response = $controller->executeWithApiResponse($endpoint); 
			
			// print "<h1>".$response->getMessages()->getResultCode()."</h1>";
			// print "<pre>".print_r($response,true)."</pre>";
			$forJson = $this->getTransactionResponseResults($response);
			
			
			if(true === $createProfile && $billTo['SavePaymentMethod'])
			{
				if(empty($forJson['TransactionResponseId']) || empty($billTo['BillingContactId']))
				{
					$forJson['PROFILE ERROR'] = 'Error when saving Profile from Transaction: either the Transaction was unsuccessful or the Merchant did not submit a valid Contact ID.';
				}
				else
				{
					$profileResponses = $this->createCustomerProfile($forJson['TransactionResponseId'],$billTo['BillingContactId'], $email);
					$forJson = array_merge($forJson,$profileResponses);
				}
			}
			
		}
		catch(\Exception $e)
		{
			$forJson = array('error' => $e->getMessage());
		}
		finally
		{
			$forJson = array_merge($forJson,$orderInfo);
			$forJson['ApiEndpointName'] = \setting('ccAuthorize.useSandbox') ? 'ApiEndpointSandbox' : 'ApiEndpointProduction';
			return $forJson;
		}
	}
	
	private function getTransactionResponseResults($response)
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
	

	
	public function createCustomerProfile($transId, $contactId, $email, $description = 'My Default Card') {

		$customerProfile = new AnetAPI\CustomerProfileBaseType();
		$customerProfile->setMerchantCustomerId($contactId);
		$customerProfile->setEmail($email);
		$customerProfile->setDescription($description);

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
			$msg['AuthorizeDotNetCustomerProfileId__c'] = $response->getCustomerProfileId();
		}
		else
		{
			$errorMessages = $response->getMessages()->getMessage();
			$msg['errors'] = array("PROFILE ERROR : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText());
		}

		return $msg;
	}

}