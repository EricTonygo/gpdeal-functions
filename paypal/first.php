<?php

require 'bootstrap.php';

//Define PP_CONFIG_PATH directory
if(!defined("PP_CONFIG_PATH")){
    define("PP_CONFIG_PATH", __DIR__);
}

//Create a credit card to Vault using Vault API mentionned
$creditCard = new \PayPal\Api\CreditCard();
$creditCard->setType("visa")
           ->setNumber("4417119669820331")
           ->setExpireMonth("11")
           ->setExpireYear("2019")
           ->setCvv2("012")
           ->setFirstName("Joe")
           ->setLastName("Shopper");

//Make a create call and print a card
try{
    $creditCard->create();
    echo $creditCard;
} catch (\PayPal\Exception\PayPalConnectionException $ex) {
    echo $ex->getData();
}

