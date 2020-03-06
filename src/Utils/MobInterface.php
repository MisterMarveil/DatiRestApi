<?php

/*
 * This file is part of the DATICASH PROJECT
 *
 * (c) ewoniewonimerveil@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * this file centralize all the mobile clients transactions. 
 *
 */
namespace App\Utils;

use App\UserManager\Manager as UserManager;
use App\Exception\DatiException;

class MobInterface //implements EventSubscriberInterface
{
    protected $userManager;
    protected $moneyTransferManager;
    protected $intCallManager;    
    protected $currencyApi;
    protected $anonymousOperation;
    protected $sipIp;
    protected $kernelDir;
    
    public function __construct(UserManager $userManager, MoneyTransferManager $moneyManager, InternationalCallManager $icManager,
            CurrencyLayerApi $currencyApi, $sipIp, $kernelDir)
    {       
        $this->userManager = $userManager;
        $this->moneyTransferManager = $moneyManager;
        $this->intCallManager = $icManager;
        $this->currencyApi = $currencyApi;
        $this->sipIp = $sipIp;
        $this->kernelDir = $kernelDir;
        $this->anonymousOperation = array("money-converter", "test-account", "login", "change-password", "authentication", "send-sms", "verif-token", "register-form", "confirm-token",
                                    "get-reset-token", "reset-password", "get-trans", "put-step");
    }    
    
    /**
     * {@inheritdoc}
     */
    public function get($msg, $isRequestedFromHttp = false){
        set_time_limit(0);
        //fermeture de la connexion
        if(preg_match("/STOP_PHP_CLIENT/i", $msg)){
           return "client stop not available";
        }
                
        if(preg_match("/ping/i", $msg)){
             return "{\"socketclienttype\": \"PHP\",\"received\": \"OK\"}";
        }

        //fin gestion de l'evenement d'information dans le cadre de la capture d'argent 
        $json = $msg;
        if(json_decode($json, TRUE ) !== NULL){
            
            $json = json_decode($json, TRUE ); //we need to json_decode twice as it was noticed that the first one return a json string rather than an array
            if(!is_array($json))
                $json = json_decode($json, TRUE);
            
            if(array_key_exists("type", $json)){
                $session = array_key_exists("sessionid", $json) ? $json["sessionid"] : "";
                return $this->testJson($json, $session, $isRequestedFromHttp);
            }else{
                return $this->sendNotif("TYPE_MISSING");
            }        
        }else{
            return $this->sendNotif("JSON_INPUT_BAD");
        }        
    }
   
    protected function throwCredentialsBad(){
        $toSend = "{\"credentials\": \"BAD\"}";
        
        return $toSend;
    }
   
    protected function sendNotif($notif){
        $toSend = "{\"notif\": \"".$notif."\"}";
        
        return $toSend;
    }
   
   protected function testJson($json, $session, $isRequestedFromHttp = false){
        if(array_key_exists("currencycode", $json) && array_key_exists("amount", $json)){
            $json["amount"] = $this->currencyApi->convert($json["currencycode"], "USD", $json["amount"]);
        }
        
        if(array_key_exists("countrycode", $json) && array_key_exists("phonenumber", $json)){
            $json["identifier"] = $json["countrycode"].$json["phonenumber"];
        }
        
        if(!in_array($json["type"], $this->anonymousOperation)){
            if(!$this->checkSecurity($json)){
                return $this->throwCredentialsBad();
            }
        }
        
        try{    
            switch($json["type"]){
                case "sipcallend":           
                    $neededFields = array("currencycode");                   
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->intCallManager->registerCall($json);
                    }
                break;
                case "sipcallinit": 
                    $neededFields = array("callercountrycode","currencycode","callerphonenumber");                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->intCallManager->initCall($json, $json["callercountrycode"]."".$json["callerphonenumber"]);
                    }
                break;  
                case "get-trans": 
                    if(preg_match("/USSD_CLIENT/i", $json["socketclienttype"])){
                        $neededFields = array("ussdnumber","currencycode");                            
                        if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                            $trans = $this->moneyTransferManager->getTrans($json);
                            return trim($trans);
                        }
                    }else if(preg_match("/MOBILE_CLIENT/i", $json["socketclienttype"])){
                        //the identifier key is injected by the security check step
                        $neededFields = array("transid","currencycode");
                        if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                            $trans = $this->moneyTransferManager->getTrans($json);
                            $this->amountReverseConversion($trans);
                            return trim($trans);
                        }                  
                    }else{
                        echo "Oops! the socketclienttype must be either mobile_client or ussd_client for money-transfer operation. ". $json["socketclienttype"]." given...";
                        return "";
                    }
                break; 
                case "put-step": 
                    if(preg_match("/USSD_CLIENT/i", $json["socketclienttype"])){
                        $neededFields = array("transid","infos","ussdnumber", "step");                            
                        if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                            return trim($this->moneyTransferManager->putStep($json));
                        }
                    }else{
                        echo "Oops! the socketclienttype must be ussd_client for money-transfer operation. ". $json["socketclienttype"]." given...";
                        return "";
                    }
                break; 
                case "money-transfer":                    
                    if(preg_match("/USSD_CLIENT/i", $json["socketclienttype"])){
                        $neededFields = array("transid", "status", "message");
                        if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                            //$matches = $array();
                            //if(preg_match("/(confirm)|(bad)/i", $json["status"],$matches)){
                            $result = trim($this->moneyTransferManager->handle($json));
                            $this->amountReverseConversion($result);
                            return $result;
                            //}                           
                        }else{
                            echo "oops... there";
                        }
                        
                    }else if(preg_match("/MOBILE_CLIENT/i", $json["socketclienttype"])){
                        //the identifier key is injected by the security check step
                        $neededFields = array("user_id", "transid", "to", "mode", "locale", "confirm", "amount", "currencycode","countrycode");   
                        if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                            $json["convertedamount"] = $this->currencyApi-> convert($json["currencycode"], "USD", $json["amount"]);
                            $result = trim($this->moneyTransferManager->handle($json));
                            return $result;
                        }                  
                    }else{
                        echo "Oops! the socketclienttype must be either mobile_client or ussd_client for money-transfer operation. ". $json["socketclienttype"]." given...";
                        return "";
                    }
                break;
                case "test-account":                    
                    $neededFields = array("countrycode", "phonenumber");   
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                       return $this->userManager->testAccount($json);
                    }                  
                break;
                case "money-converter":
                    $neededFields = array("from", "to", "amount");
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                        $json["converted"] = $this->currencyApi->convert($json["from"], $json["to"], $json["amount"]);
                        $json["precision"] = $this->currencyApi->convert($json["from"], $json["to"], "1");
                        
                       return json_encode($json);
                    }                  
                break;
                case "prepare-money-transfer":
                    $neededFields = array("user_id","from", "to", "mode", "amount", "recipient");
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                        $json["converted"] = $this->currencyApi->convert($json["from"], $json["to"], $json["amount"]);
                        $json["precision"] = $this->currencyApi->convert($json["from"], $json["to"], "1");
                        $result = trim($this->moneyTransferManager->prepareTransfer($json));
                        return $result;
                    }                  
                break;
                case "authentication":                    
                    $neededFields = array("ping", "currencycode", "busy", "locale");   
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                       return $this->buildResponseObject("OK", $json, $session);
                    }                  
                break;
                case "account-topup":                    
                    $neededFields = array("currencycode", "amount", "creditcardnumber", "creditcardexpirationdate", "creditcardcode",
                                            "billtostate", "billtocountry", "billtofirstname", "billtolastname", 
                                            "billtoaddress", "billtocustomertype", "billtocity", "billtozip", "locale");   
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){                        
                        //$this->captureManager->buildTrackingData($json,$isRequestedFromHttp);
                    }                  
                break;
                case "sip-register-new-account":                    
                    $neededFields = array("registeredsippass");   
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->userManager->sipAccountRegistered($json);
                    }
                break;
                case "balance":                    
                    $neededFields = array("unit");   
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->userManager->getBalance($json);
                    }
                break;
                case "register-form":                             
                    $neededFields = array("email", "password");
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->userManager->createUserFromRequest($json, array("ROLE_USER"));
                    }
                break;
                case "confirm-token": 
                    $neededFields = array("token", "countrycode", "phonenumber");                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        
                        $this->userManager->activateUser($json);
                    }
                break;
                case "getprofiledata":
                    $neededFields = array();                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                       return $this->sendProfileData($json);                    
                    }
                break;
                case "get-reset-token":
                    $neededFields = array();                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->userManager->handleResetPasswordRequest($json);                        
                    }
                break;
                case "updateprofiledata":
                    $neededFields = array("billtozip","billtocountry","billtostate","billtocity","billtoaddress", "billtolastname", "billtofirstname", "email");                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->userManager->updateProfile($json);  
                    }
                break;
                 case "sendmailconfirmtoken":
                    $neededFields = array();                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->userManager->requestVerificationToken($json);        
                    }
                break;
                case "reset-password":
                    $neededFields = array("newpassword", "token");                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){                                             
                        $this->userManager->resetPassword($json);
                    }
                break;
                case "change-password":
                    $neededFields = array("newpassword", "oldpassword", "email");                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){
                        $this->userManager->handleChangePassword($json);
                    }
                break;
                case "login":
                    $neededFields = array("password","currencycode");                            
                    if($this->validateArguments($json, $neededFields, $json["type"], $session)){                                              
                        $json["sessionid"] = "";

                        $this->userManager->authorizeUser($json);
                    }
                break;
                default:
                    return $this->sendNotToBeSendSignal();
                    break;
            }
        }catch(DatiException $e){ 
            $value = $this->trackSpecialValue($e->getMessage(), $json, $isRequestedFromHttp); 
            return $this->buildResponseObject($value, $json, $session);
        }       
    }
    
    
    protected function checkSecurity(&$json):bool{        
        if((!array_key_exists("sessionid", $json) || empty($json["sessionid"])) && (!array_key_exists("countrycode", $json) ||
            !array_key_exists("phonenumber", $json) || empty(trim($json["countrycode"].$json["phonenumber"])))){
            //echo var_dump($json);
            return false;
        }
        
        try{
            $user = $this->userManager->authorizeUser($json);
        }catch(DatiException $e){
            return preg_match("/login-ok/i", $e->getMessage());
        }
        echo "something went wrong while checking security... This should not happen";
    }
    /* 
    *   sendNotToBeSendSignal
    *   @description: convenient way to terminate a php operation that has nothing to send back (useful to freed the php client so that it receives other operations)
    *
    */
    protected function sendNotToBeSendSignal(){
        $notToBeSendSignal = "{\"nottosend\":\"nottosend\"}";
        
        return $notToBeSendSignal;
       
    }
    
    protected function amountReverseConversion(&$request){
        $request = json_decode($request, true);
        if(array_key_exists("amount", $request) && array_key_exists("currencycode", $request))
            $request["amount"] = $this->currencyApi->convert("USD", $request["currencycode"], $request["amount"]);
        
        if(array_key_exists("cost", $request) && array_key_exists("currencycode", $request))
            $request["cost"] = $this->currencyApi->convert("USD", $request["currencycode"], $request["cost"]);
            
        if(array_key_exists("balance", $request) && array_key_exists("currencycode", $request))
            $request["balance"] = $this->currencyApi->convert("USD", $request["currencycode"], $request["balance"]);
            
        if(array_key_exists("totalcost", $request) && array_key_exists("currencycode", $request))
            $request["totalcost"] = $this->currencyApi->convert("USD", $request["currencycode"], $request["totalcost"]);
            
        $request = json_encode($request);
    }
    
    protected function sendTokenSMS($token, $json, $isRequestedFromHttp = false){
         if(!$isRequestedFromHttp){
            $smsCmd = "{".
                "\"socketclienttype\": \"PHP_CLIENT\",".
                "\"type\": \"send-sms\",".
                "\"token\": \"".$token."\",".
                "\"sender\": \"\",".
                "\"session\": \"".($json["sessionid"])."\",".
                "\"recipients\": \"".($json["countrycode"]."".$json["phonenumber"])."\",".
                "\"content\": \"verif-token\"".
            "}";
         }
        
        if(!$isRequestedFromHttp){
            return $smsCmd;
        }else{
            $this->userManager->getSmsApi()->sendMessage("DATI CODE: $token", $json["identifier"]);
        }
        
    }
    
    /*
        return User Balance
        params $request["identifier", "unit"]
    */
    protected function getUserBalance($request){
        //uniformisation des clés unit et currencycode
        if(array_key_exists("currencycode", $request))
            $request["unit"] = $request["currencycode"];
            
        try{
            $this->userManager->getBalance($request);
        }catch(DatiException $e){
            $matches = array();
            var_dump($e->getMessage());
            if(preg_match("/^balance\_\-?\d+(\.\d+)?/i", $e->getMessage(), $matches)){
                $value = $matches[0];
                $amount = substr($value, (strpos($value, "_") + 1));
               
                if(array_key_exists("unit", $request)){
                    return $this->currencyApi->convert("USD", $request["unit"], $amount);
                }
                return $amount;
            }else{
                echo "there is a problem with balance retrieve, not throwing the expected exception message...";
            }
        }
    }
    
    protected function sendTokenMail($token, $json, $mail, $contentType = "reset-password-token"){
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)){
            echo "Bad mail given. sending token mail aborted for ".$mail;
            
            return;    
        }
        
        $mailCmd = "{".
            "\"socketclienttype\": \"PHP_CLIENT\",".
            "\"type\": \"send-mail\",".
            "\"token\": \"".$token."\",".
            "\"sender\": \"\",".
            "\"session\": \"".($json["sessionid"])."\",".
            "\"recipients\": \"".$mail."\"".
            "\"content\": \"".$contentType."\"".
        "}";
        
        return $mailCmd;
        
    }
    
    protected function sendProfileData($json){
        $user = $this->userManager->identifyUser($json);
        $customer = $user->getCustomer();
        
        $result = "{".
           "\"socketclienttype\": \"PHP_CLIENT\",".
           "\"type\": \"".$json["type"]."\",".
           "\"phonenumber\": \"".$json["phonenumber"]."\",".
           "\"countrycode\": \"".$json["countrycode"]."\",".
           "\"registeredmail\": \"".$customer->getShopMail()."\",".
           "\"email_need_activation\": \"".((bool)$customer->getShopMailActivated() ? "0" : "1" )."\",".
           "\"billtolastname\": \"".$customer->getLastName()."\",".
           "\"billtofirstname\": \"".$customer->getFirstName()."\",".
           "\"billtocountry\": \"".$customer->getCountry()."\",".
           "\"billtostate\": \"".$customer->getState()."\",".
           "\"billtocity\": \"".$customer->getCity()."\",".
           "\"billtoaddress\": \"".$customer->getAddress()."\",".
           "\"billtozip\": \"".$customer->getZip()."\",".
           "\"sessionid\": \"".$session."\"}";   
           
        //echo "\n sended to bridge: ".$result."\n";       
        return $result;
        
    }
    
    protected function buildResponseObject($value, $json, $session){             
        if(preg_match("/^nottosend(.+)?/i", $value)){
             return $this->sendNotToBeSendSignal();             
        } 
        
        if(preg_match("/keys/i", $value)){
            $result = json_decode($value, true);
            if(is_array($result) && array_key_exists("sessionid", $result) && array_key_exists("socketclienttype", $result))
                return $value;             
        } 
        
        $result = "{".
           "\"socketclienttype\": \"PHP_CLIENT\",";
        
        if(!preg_match("/(get\-trans)/i", $json["type"])){
            $result .= 
               "\"type\": \"".$json["type"]."\",";
        }
           
        $result .=  "\"sessionid\": \"".(empty(trim($session)) ? (array_key_exists("sessionid", $json) ? $json["sessionid"]: "") : $session)."\"";
           
        if(array_key_exists("phonenumber", $json))
            $result .= ",\"phonenumber\": \"".$json["phonenumber"]."\"";
        
        if(array_key_exists("countrycode", $json))   
           $result .= ",\"countrycode\": \"".$json["countrycode"]."\"";     
           
        if(preg_match("/authentication/i", $json["type"])){
             $result .= ",\"ping\": \"OK\"";
        }
        
        if(preg_match("/sipcallinit/i", $json["type"])){            
             $result .= ",".$value;
             $value = "OK";
             $result .= ",\"value\": \"".$value."\"";
             $result .= "}";
             $result = json_decode($result, true);
             $result["rateperminute"] = $this->currencyApi->convert("USD", $json["currencycode"], $result["rateperminute"]);
             return json_encode($result);
        }
        
        if(preg_match("/sipcallend/i", $json["type"])){            
              $amount = $this->getUserBalance($json);
             $result .= ",".$value;            
             $result .= ",\"balance\": \"$amount\"";             
             $value = "OK";
             $result .= ",\"value\": \"".$value."\"";
             $result .= "}";
             $result = json_decode($result, true);
             $result["callcost"] = $this->currencyApi->convert("USD", $json["currencycode"], $result["callcost"]);
             return json_encode($result);
        }
        
                        
        if(preg_match("/(money\-transfer)|(get\-trans)/i", $json["type"])){
            if(preg_match("/(get\-trans)/i", $json["type"])){
                $result .= 
                   ",\"type\": \"money-transfer\"";
            }
            
            $matches = array();
            if(preg_match("/^BAD\-(.+)$/i", $value, $matches)){                
                $details = $matches[1];
                $subMatches = array();
                if(preg_match("/^(INSUFFICIENT_BALANCE)\-(.+)\|(.+)\|(.+)\|(.+)$/i", $details, $subMatches)){
                    $details = $subMatches[1];
                    $totalCost = $subMatches[2];
                    $credit = $subMatches[3];
                    $cost = $subMatches[4];
                    $amount = $subMatches[5];
                    
                    
                    $result .= ",\"balance\":  \"".($this->currencyApi->convert("USD", $json["currencycode"], $credit))."\"".   
                                ",\"cost\":  \"".($this->currencyApi->convert("USD", $json["currencycode"], $cost))."\"".
                                ",\"amount\":  \"".($this->currencyApi->convert("USD", $json["currencycode"], $amount))."\"".
                                ",\"totalcost\":\"".($this->currencyApi->convert("USD", $json["currencycode"], $totalCost))."\"";
                }
                 $result .= ",\"status\":  \"bad\"".
                            ",\"details\":  \"".$details."\"";
            }
            
            if(array_key_exists("recipient", $json)){
                $result .= ",\"recipient\": \"".$json["recipient"]."\"";
            }
            
            if(array_key_exists("status", $json)){
                $result .= ",\"status\": \"".$json["status"]."\"";
            }
            $value = "";
        }
        
        $matches = array();
        if(preg_match("/^profile\-update\-ok\-(null|\\d{6})\-(.+)\-(0|1)$/i", $value, $matches)){
            $value = (bool)$matches[3] ? "email_need_activation" : "OK";
        }
        
        if(preg_match("/register-form/i", $json["type"])){
            if(preg_match("/bad_*/i", $value)){
                $key = strtolower(substr($value, (strpos($value, "_") + 1)));
                $value = "bad";
            }else if(preg_match("/already-*/i", $value)){
                $key = strtolower(substr($value, (strpos($value, "-") + 1)));
                $value = "already";
            }else{                
                $key = "";
            }
            $result .= ",\"keys\": \"".$key."\"";
        }
        
        $matches = array();        
        if(preg_match("/^balance_(\-?\d+)$/i", $value, $matches)){
            $value = $matches[1];
            $amount = substr($value, (strpos($value, "_") + 1));
            $amount = $this->currencyApi->convert("USD", $json["unit"], $amount);
            
            $result .= ",\"amount\": \"$amount\"";
            if(array_key_exists("unit", $json)){
                $result .= ",\"unit\": \"".$json["unit"]."\"";                
            }
        }
        
        $matches = array();        
        if(preg_match("/^(login-ok)-(.+)?$/i", $value, $matches)){
            $value = $matches[1];            
             $amount = $this->getUserBalance($json);
             //already converted in getUserBalance
             //$amount = $this->currencyApi->convert("USD", $json["currencycode"], $amount);
             
            $password = array_key_exists(2, $matches) ? $matches[2] : "";            
            $result .= ",\"sippass\": \"".$password."\"";
            $result .= ",\"balance\": \"$amount\"";     
            $result .= ",\"marketingstr\": \"The new Way of doing things...\"";
            $result .= ",\"host\": \"".$this->sipIp."\"";
        }
        
        
        $matches = array();
        if(preg_match("/VERIFICATION\-TOKEN\-SAVE\-OK\-(\d{6})/i", $value, $matches)){
            $value = "OK";
        }
        
         $matches = array();
        if(preg_match("/token\-(mail|sms)\-send\-ok\-(\d{6})\-(.+)/i", $value, $matches)){
            $value = "token-send-ok";
            $method = $matches[1];
            $result .= ",\"method\": \"".$method."\"";
        }
        
        $matches = array();
        if(preg_match("/(CONFIRM\-TOKEN\-FORM)\-(\d{6})/i", $value, $matches)){
            $value = "confirm-token-form";
        }
        
        if(preg_match("/account\-topup/i", $json["type"])){
            $matches = array();
                if(preg_match("/approved/i", $value, $matches)){                    
                    $value = $this->getUserBalance($json);
                    
                    //already converted in getUserBalance
                    //$value = $this->currencyApi->convert("USD", $json["currencycode"], $value);
             
                    $result .= ",\"status\": \"approved\"";
                    $result .= ",\"balance\": \"".$value."\"";
                    $result .= ",\"details\": \"\"";
                    $result .= ",\"avsresultcode\": \"Y\"";
                }
                
            $matches = array();
            if(preg_match("/declined\-*/i", $value, $matches)){
                $details = substr($value, (strpos($value, "-") + 1));
                
                $result .= ",\"status\": \"declined\"";
                $result .= ",\"details\": \"".$details."\"";
                $result .= ",\"avsresultcode\": \"Y\"";
            } 
            
            if(preg_match("/error\-*/i", $value)){
                $details = substr($value, (strpos($value, "-") + 1));
                
                $result .= ",\"status\": \"error\"";
                $result .= ",\"details\": \"".$details."\"";
                $result .= ",\"avsresultcode\": \"Y\"";
            }
        }    
        
        $result .= ",\"value\": \"".$value."\"";
        $result .= "}";
               
        return $result;
    }
    protected function trackSpecialValue($value, $json, $isRequestedFromHttp = false){
        $matches = array();
        if(preg_match("/(confirm-token-form)\-(\d{6})/i", $value, $matches)){
            //echo "sending a token sms for account confirmation\n";
            if(!$isRequestedFromHttp){
                echo "".$this->sendTokenSMS($matches[2], $json);            
            }else{
                $this->sendTokenSMS($matches[2], $json, $isRequestedFromHttp);            
            }
            //$this->userManager->sendTokenMail($matches[2], $json);
            
        }else  if(preg_match("/token\-(mail|sms)\-send\-ok\-(\d{6})\-(.+)/i", $value, $matches)){
            $method = $matches[1];
            $token = $matches[2];
            
            if(trim(strtolower($method)) == "mail"){
                $mail = $matches[3];
                //echo "sending a token mail for resetting password \n";
               echo $this->sendTokenMail($token, $json, $mail);                
            }else{
                //echo "sending a token sms for resetting password \n";
                if(!$isRequestedFromHttp){
                    echo $this->sendTokenSMS($token, $json);                
                }else{
                    $this->sendTokenSMS($token, $json, $isRequestedFromHttp);                
                }
            }
            //$result .= ",\"method\": \"".$method."\"";
        }else if(preg_match("/^profile\-update\-ok\-(\d{6})\-(.+)\-1$/i", $value, $matches)){
                $mail = $matches[2];  
                $token = $matches[1];
                  //echo "sending a token mail for validation to $mail \n";
              echo $this->sendTokenMail($token, $json, $mail);
        }else if(preg_match("/VERIFICATION\-TOKEN\-SAVE\-OK\-(\d{6})\-(.+)/i", $value, $matches)){
            $token = $matches[1];
            $mail  = $matches[2];
            
            echo $this->sendTokenMail($token, $json, $mail, "confirm-mail-token");
        }
        
        return strtolower($value);
    }
    
    protected function validateArguments(&$messageArray, $neededFields, $type, $session){        
        array_merge($neededFields, array("countrycode", "phonenumber"));        
        foreach($neededFields as $field){
            if(!array_key_exists($field, $messageArray) || ((strtolower($field) == "mail" || strtolower($field) == "email") && !filter_var($messageArray[$field], FILTER_VALIDATE_EMAIL))){
                
                $details = strtolower($field) == "mail" || strtolower($field) == "email" ? "badly formatted or empty mail value" : "";
                $result = "{".
                               "\"socketclienttype\": \"PHP_CLIENT\",".
                               "\"type\": \"".$type."\",".
                               "\"value\":  \"bad\",".
                               "\"details\": \"".$details."\",".
                               "\"sessionid\": \"".$session."\",". 
                               "\"keys\":  \"".$field."\"".
                            "}";
               
                throw new DatiException($result);               
            }
        }
        
        if(!array_key_exists("identifier", $messageArray) && array_key_exists("countrycode", $messageArray) && array_key_exists("phonenumber", $messageArray))
            $messageArray["identifier"] = ($messageArray["countrycode"].$messageArray["phonenumber"]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {       
             
    }
}
