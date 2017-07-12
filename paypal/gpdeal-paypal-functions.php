<?php

require 'bootstrap.php';

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\PaymentCard;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

//Define PP_CONFIG_PATH directory
if (!defined("PP_CONFIG_PATH")) {
    define("PP_CONFIG_PATH", __DIR__);
}

function executePaypalPaymentWithCreditCard($card_type, $card_number, $card_cvc, $card_expire_month, $card_expire_year, $billing_country_code = null, $firstname = null, $lastname = null) {

    $card = new PaymentCard();
    $card->setType($card_type)
            ->setNumber($card_number)
            ->setExpireMonth($card_expire_month)
            ->setExpireYear($card_expire_year)
            ->setCvv2($card_cvc);
    if ($firstname) {
        $card->setFirstName($firstname);
    }
    if ($billing_country_code) {
        $card->setBillingCountry(strtoupper($billing_country_code));
    }
    if ($lastname) {
        $card->setLastName($lastname);
    }

    //Create Funding Instrument and set it my payment card
    $fi = new FundingInstrument();
    $fi->setPaymentCard($card);

    $payer = new Payer();
    $payer->setPaymentMethod("credit_card")
            ->setFundingInstruments(array($fi));

//    $item1 = new Item();
//    $item1->setName('Amount transaction')
//            ->setDescription('Amount transaction')
//            ->setCurrency('EUR')
//            ->setQuantity(1)
//            ->setTax(0.0)
//            ->setPrice(0.00);
//
//    $itemList = new ItemList();
//    $itemList->setItems(array($item1));
    //Details
    $details = new Details();
    $details->setShipping(0.0)
            ->setTax(0.0)
            ->setSubtotal(2.00);

    //Amount
    $amount = new Amount();
    $amount->setCurrency("EUR")
            ->setTotal(2.00)
            ->setDetails($details);

    //Transaction
    $transaction = new Transaction();
    $transaction->setAmount($amount)
            //->setItemList($itemList)
            ->setDescription("GPDEAL Amount transaction")
            ->setInvoiceNumber(uniqid());

    //Payment
    $payment = new Payment();
    $payment->setIntent("sale")
            ->setPayer($payer)
            ->setTransactions(array($transaction));

    //$request = clone $payment;

    try {
        $payment->create();
    } catch (Exception $ex) {
        return null;
    }
    return $payment;
}

function executePaypalPaymentUsingPaypalAccount() {
    //Payer
    $payer = new Payer();
    $payer->setPaymentMethod("paypal");

    //List of item
//    $item1 = new Item();
//    $item1->setName('Amount transaction')
//            ->setDescription('Amount transaction')
//            ->setCurrency('EUR')
//            ->setQuantity(1)
//            ->setTax(0.0)
//            ->setPrice(0.00);
//
//    $itemList = new ItemList();
//    $itemList->setItems(array($item1));
    //Details
    $details = new Details();
    $details->setShipping(0.0)
            ->setTax(0.0)
            ->setSubtotal(2.00);

    //Amount
    $amount = new Amount();
    $amount->setCurrency("EUR")
            ->setTotal(2.00)
            ->setDetails($details);

    //Transaction
    $transaction = new Transaction();
    $transaction->setAmount($amount)
            //->setItemList($itemList)
            ->setDescription("GPDEAL Amount transaction")
            ->setInvoiceNumber(uniqid());

    //Redirect Urls
    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl(esc_url(add_query_arg(array('success' => 'true'), get_permalink(get_page_by_path(__('select-transport-offers', 'gpdealdomain') . '/' .__('payment', 'gpdealdomain'))))))
            ->setCancelUrl(esc_url(get_permalink(get_page_by_path(__('select-transport-offers', 'gpdealdomain') . '/' .__('payment', 'gpdealdomain')))));

    //Payment
    $payment = new Payment();
    $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

    //$request = clone $payment;

    try {
        $payment->create();
    } catch (Exception $ex) {
        $error_cancel_redirect_url = $_SESSION['error_cancel_redirect_url'] != null ? $_SESSION['error_cancel_redirect_url'] : home_url('/');
        $_SESSION['faillure_process'] = __("An error occured during payment", "gpdealdomain");
        wp_safe_redirect($error_cancel_redirect_url);
        exit;
        
    }
    $approvalUrl = $payment->getApprovalLink();
    wp_redirect($approvalUrl);
    exit;
    //return $payment;
}

function executePaypalPayment($paymentId, $payerId) {
    $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
            'AXD1BevVsOeKnhyvRXDRG5gGIr77_XgMorBUAdFluWlrqxXcjF0shMV7Sz7_-a7wmKkfMQcS3ggXCPWq', 'EKyueQeN0r7cA_Hz2OZZjPcLmkVNH_I_fd8wz_W2B03eeUx-S_8PDQnd_A3Jz-nYdY7exzrOHpcpwsea')
    );

    $apiContext->setConfig(array(
        'log.LogEnabled' => true,
        'log.FileName' => 'PayPal.log',
        'log.LogLevel' => 'DEBUG'
    ));
    $payment = Payment::get($paymentId, $apiContext);

    $execution = new PaymentExecution();
    $execution->setPayerId($payerId);
    try {
        $result = $payment->execute($execution, $apiContext);
        return $result;
    } catch (Exception $ex) {
        $error_cancel_redirect_url = $_SESSION['error_cancel_redirect_url'] != null ? $_SESSION['error_cancel_redirect_url']: home_url('/');
        unset($_SESSION["error_cancel_redirect_url"]);
        $_SESSION['faillure_process'] = __("An error occured during payment", "gpdealdomain");
        wp_safe_redirect($error_cancel_redirect_url);
        exit;
    }
}
