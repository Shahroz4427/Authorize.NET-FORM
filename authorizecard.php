<?php

require 'vendor/autoload.php';
require_once 'constants/SampleCodeConstants.php';

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

const AUTHORIZENET_LOG_FILE = "phplog";

function authorizeCreditCard($amount)
{
   if ($_SERVER["REQUEST_METHOD"] == "POST") {
       $cardNumber = $_POST["cardNumber"];
       $expirationDate = $_POST["expirationDate"];
       $cardCode = $_POST["cardCode"];
       $firstName = $_POST["firstName"];
       $lastName = $_POST["lastName"];
       $company = $_POST["company"];
       $country = $_POST["country"];
       $address = $_POST["address"];
       $city = $_POST["city"];
       $state = $_POST["state"];
       $zip = $_POST["zip"];
       $email = $_POST["email"];

       // Create a merchantAuthenticationType object with authentication details retrieved from the constants file
       $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
       $merchantAuthentication->setName(\SampleCodeConstants::MERCHANT_LOGIN_ID);
       $merchantAuthentication->setTransactionKey(\SampleCodeConstants::MERCHANT_TRANSACTION_KEY);

       // Set the transaction's refId
       $refId = 'ref' . time();

       // Create the payment data for a credit card
       $creditCard = new AnetAPI\CreditCardType();
       $creditCard->setCardNumber($cardNumber);
       $creditCard->setExpirationDate($expirationDate);
       $creditCard->setCardCode($cardCode);

       // Add the payment data to a paymentType object
       $paymentOne = new AnetAPI\PaymentType();
       $paymentOne->setCreditCard($creditCard);

       // Create order information
       $randomInvoiceNumber = rand(10000, 99999);
       $order = new AnetAPI\OrderType();
       $order->setInvoiceNumber($randomInvoiceNumber);
       $order->setDescription("Golf Shirts");

       // Set the customer's Bill To address
       $customerAddress = new AnetAPI\CustomerAddressType();
       $customerAddress->setFirstName($firstName);
       $customerAddress->setLastName($lastName);
       $customerAddress->setCompany($company);
       $customerAddress->setAddress($address);
       $customerAddress->setCity($city);
       $customerAddress->setState($state);
       $customerAddress->setZip($zip);
       $customerAddress->setCountry($country);

       // Generate a random customer ID
       $randomCustomerId = rand(100000, 999999);

       // Set the customer's identifying information
       $customerData = new AnetAPI\CustomerDataType();
       $customerData->setType("individual");
       $customerData->setId($randomCustomerId);
       $customerData->setEmail($email);

       // Add values for transaction settings
       $duplicateWindowSetting = new AnetAPI\SettingType();
       $duplicateWindowSetting->setSettingName("duplicateWindow");
       $duplicateWindowSetting->setSettingValue("0"); // Disable duplicate transaction checking
       

       // Create a TransactionRequestType object and add the previous objects to it
       $transactionRequestType = new AnetAPI\TransactionRequestType();
       $transactionRequestType->setTransactionType("authOnlyTransaction");
       $transactionRequestType->setAmount($amount);
       $transactionRequestType->setOrder($order);
       $transactionRequestType->setPayment($paymentOne);
       $transactionRequestType->setBillTo($customerAddress);
       $transactionRequestType->setCustomer($customerData);
       $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);

       // Assemble the complete transaction request
       $request = new AnetAPI\CreateTransactionRequest();
       $request->setMerchantAuthentication($merchantAuthentication);
       $request->setRefId($refId);
       $request->setTransactionRequest($transactionRequestType);

       // Create the controller and get the response
       $controller = new AnetController\CreateTransactionController($request);
       $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

       if ($response != null) {
        // var_dump($response);
           // Check if the API request was successful
           if ($response->getMessages()->getResultCode() == "Ok") {
               header("Location: thankyou.html");
               exit();
               // Process the successful response
               $tresponse = $response->getTransactionResponse();

               if ($tresponse != null && $tresponse->getMessages() != null) {
                   echo "Successfully created transaction with Transaction ID: " . $tresponse->getTransId() . "\n";
                   echo "Transaction Response Code: " . $tresponse->getResponseCode() . "\n";
                   echo "Message Code: " . $tresponse->getMessages()[0]->getCode() . "\n";
                   echo "Auth Code: " . $tresponse->getAuthCode() . "\n";
                   echo "Description: " . $tresponse->getMessages()[0]->getDescription() . "\n";
               } else {
                   echo "Transaction Failed \n";
                   if ($tresponse->getErrors() != null) {
                       echo "Error Code  : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
                       echo "Error Message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";
                   }
               }
           } else {
                header("Location: error.html");
                exit();
               echo "Transaction Failed \n";
               $tresponse = $response->getTransactionResponse();

               if ($tresponse != null && $tresponse->getErrors() != null) {
                   echo "Error Code  : " . $tresponse->getErrors()[0]->getErrorCode() . "\n";
                   echo "Error Message : " . $tresponse->getErrors()[0]->getErrorText() . "\n";
               } else {
                   echo "Error Code  : " . $response->getMessages()->getMessage()[0]->getCode() . "\n";
                   echo "Error Message : " . $response->getMessages()->getMessage()[0]->getText() . "\n";
               }
           }
       } else {
           echo "No response returned \n";
       }

       return $response;
   }

   return 0;
}

if (!defined('DONT_RUN_SAMPLES') && $_SERVER["REQUEST_METHOD"] == "POST") {
   $price = $_POST["price"];
   authorizeCreditCard($price);
}

