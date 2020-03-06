<?php

/*
 * This file is part of the DATICASH PROJECT
 *
 * (c) ewoniewonimerveil@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Utils;
  
    use App\Exception\DatiException;
    use Doctrine\ORM\EntityManagerInterface;
    use App\UserManager\Manager;
    use App\Entity\Job;
    use App\Entity\User;
    use App\Utils\CurrencyLayerApi;
    use App\Entity\TransDictionnary;

class MoneyTransferManager
{
    protected $userController;
    protected $em;
    protected $requestTTL;
    protected $accountingAgent;
    protected $currencyApi;
    protected $smsApi;
    protected $successPattern = "(Depot effectue avec succes)";
    
    public function __construct(Manager $controller, EntityManagerInterface $em, AccountingService $accounting, CurrencyLayerApi $currencyApi,SMSApi $smsApi){
        set_time_limit(0);
        $this->requestTTL = "PT30S";
        $this->userController = $controller;
        $this->accountingAgent = $accounting;
        $this->currencyApi = $currencyApi;
        $this->smsApi = $smsApi;
        
        $this->em = $em;
    }
    
    protected function checkTransError(&$request, Job $job, $isMobile=false){
        $trans = $job->getTrans();
        if(empty($trans) || !array_key_exists("infos", $request))
            return true;
        
        $requete = json_decode($job->getRequest(),true);
        if(!array_key_exists("locale", $requete) || empty($requete["locale"])){
            $locale = "en";
        }else{
            $locale = $requete["locale"];
        }
        
        $errors = $trans->getErrors();
        foreach($errors as $error){
            if(preg_match("/".$error->getRegExp()."/i", $request["infos"])){
                $errorString = $error->translate($locale)->getDescription();
                if(!$isMobile){
                    $this->smsApi->sendAdminMsg("DATIMOB USSD(".$request["ussdnumber"]."): \n".$request["infos"]);
                }else{
                    $this->cleanJob($job, false);
                    throw new DatiException("BAD-$errorString");
                }
            }
        }
        return true;
    }
    
    public function putStep(&$json){
        $job = $this->em->getRepository(Job::Class)->getForUser($json["transid"]);
        $result = array();
        $result["transid"] = $json["transid"];
        
        
        if(!empty($job)){
            if(is_array($job))
                $job = $job[0];
            
            $this->checkTransError($json, $job);
                
            $job->setStep($json["step"]);
            $job->setStepDescription($json["infos"]);
            
            if($json["step"] == $json["total"]){
                $job->setResponse($json["infos"]);  
                $job->setStatus("COMPLETED");
                
                $request = json_decode($job->getRequest(), true);
                $user = $job->getUser();
                
                if($job->getTrans() !== null){
                    $matches = array();
                    if(preg_match("/".$job->getTrans()->getRecipientNameRegExp()."/", $json["infos"], $matches)){
                        $recipientName = $matches[1];
                        $request["recipient"] = $recipientName;
                        if(!$request["confirm"]){
                            $request["status"] = "CONFIRM_REQUIRED";
                        }
                    }
                    
                    if(preg_match("/".$this->successPattern."/i", $json["infos"]) && $request["confirm"]){
                        $request["status"] = "APPROVED";
                        $this->syncBalance($user, $request);
                    }
                    
                    $job->setRequest(json_encode($request));
                }else{
                    echo "Oops!  the job involved does not have a corresponding transDic, this should never happen... while putting step... ";
                }
            }else{
                
            }
            
            $this->em->flush();
            $result["received"] = "OK";
        }else{
            $result["received"] = "JOB_CANCELED";
        }
        
         return json_encode($result);
    }
    
    protected function syncBalance(ShopUser &$user, $request){
        if(!array_key_exists("totalcost", $request))    
            return "Oops! totalcost key not given.. sync balance aborted...";
        
        $user->addCredit((-1) * (double)$request["totalcost"]);
        $this->em->flush();
    }
    
    public function prepareTransfer(&$request){
        $request["socketclienttype"] = "PHP_CLIENT";
        if(preg_match("/daticash/i", $request["mode"])){
            $user = $this->userController->identifyUser(array("identifier" => $request["recipient"]));    
            if(is_array($user) && !empty($user))
                $user = $user[0];
                
            if(!($user instanceof ShopUser)){
                throw new DatiException("Bad-BAD_RECIPIENT");
            }
            
            $request["operateur"] = "DATICASH";
            $request["status"] = "1";
        }else{
            $job = $this->em->getRepository(Job::Class)->getForUser($request["user_id"]);
            if(!empty($job)){
                if(is_array($job))
                    $job = $job[0];
                $this->cleanJob($job, false);
            }
            
            $user = $this->userController->identifyUser($request);    
            $response = $this->userController->getMSClient()->getCallInitData($user, $request["recipient"]);
            if($response !== false){
                $response = "{".$response."}";
                $response = json_decode($response, true);
                
                $request["operateur"] = $response["ratedescription"];
            }else{
                $request["operateur"] = "UNKNOWN_CARREER";
                echo "probleme pour prendre contact avec le serveur microsoft... operation annulée";
            }  
        
            $transDics = $this->em->getRepository(TransDictionnary::Class)->findAll();
            $found = false;
            
            foreach($transDics as $key => $dic){
                $pattern = "/".$dic->getCarreerRegExp()."/";
               
                if(preg_match($pattern, $request["recipient"])){
                    $found = true;
                    $request["status"] = "1";
                    break;
                }
            }
            if(!$found)
                $request["status"] = "0";
        }
        
        return json_encode($request);
    }
    
    public function getTrans($json){
        $now = new \DateTime();
        
        if(preg_match("/USSD_CLIENT/i", $json["socketclienttype"])){
            $jobs = $this->em->getRepository(Job::Class)->getOrderedByDate();
            
            foreach($jobs as $job){
                $jobCreatedTime = $job->getUpdatedAt() !== null ? $job->getUpdatedAt() : $job->getCreatedAt();
                $jobCreatedTime->add(new \DateInterval($this->requestTTL));
               
                if($job->getStatus() == "WAITING"){
                    if($jobCreatedTime < $now){
                        $this->cleanJob($job, false);
                        continue;
                    }
                    
                    $requestToSend = json_decode($job->getRequest(), true);
                    $requestToSend["amount"] = $this->currencyApi->convert("USD", $json["currencycode"], $requestToSend["amount"]);
                    
                    //verifions que l'USSD est capable de traiter la requête
                    if(!preg_match("/".$job->getTrans()->getCarreerRegExp()."/", $json["ussdnumber"])){
                        continue;
                    }  
                    
                    //changement de statut
                    $job->setStatus("PROCESSING");
                    $job->setUssdHandlerNumber($json["ussdnumber"]);
                    $job->setLastRequestedAt(new \DateTime());
                    $this->em->flush();
                    
                    $requestToSend["sessionid"] = $json["sessionid"];
                    $requestToSend["ussdnumber"] = $json["ussdnumber"];
                    return json_encode($requestToSend);
                }else{
                    continue;
                }
            }
            return "{\"sessionid\": \"".$json["sessionid"]."\"}";
        }else{
            $job = $this->em->getRepository(Job::Class)->getForUser($json["transid"]);
            
            if(empty($job)){
                throw new DatiException("BAD-TRANSACTION_CANCELED");
            }
            
            if(is_array($job))
                $job = $job[0];
            
            $jobCreatedTime = $job->getUpdatedAt() !== null ? $job->getUpdatedAt() : $job->getCreatedAt();
            $jobCreatedTime->add(new \DateInterval($this->requestTTL));
            $request = json_decode($job->getRequest(), true);
            
            if($now > $jobCreatedTime){
                if(!$request["confirm"] && $job->getStatus() == "WAITING"){
                    $this->cleanJob($job, false);
                    throw new DatiException("BAD-TRANS_REQUEST_EXPIRED");
                }  
            }
            
            $step = $job->getStep();
            $details = "";
            $currentStep = "0";
            $steps = $job->getTrans()->getSteps();
            
            if(!empty($step)){
                foreach($steps as $etape){
                    if($etape->getPosition() == $step){
                        $locale = preg_match("/fr/", $request["locale"]) ? "fr" : "en";
                        $details = $etape->translate($locale)->getDescription();
                        $currentStep = $etape->getPosition();
                        break;
                    }
                }
            }
            
            $request["total"] = $request["confirm"] ? count($steps) : (count($steps) - 1);
            $request["step"] = $currentStep;
            $request["transid"] = $json["transid"];
            
            $request["details"] = $currentStep == "0" ? "Transaction initialization..." : $details;
            
            $job->setLastConsultedAt(new \DateTime());
            $this->em->flush();
            
            
            if($job->getResponse() !== null){//nous sommes à la dernière étape nous allons supprimer la tache
                $tableau = array("infos" => $job->getResponse());
                $this->checkTransError($tableau, $job, true);
                $confirm = false;
            
                if(preg_match("/".$this->successPattern."/i", $job->getResponse())){
                    $request["balance"] = $job->getUser()->getCredit();
                    $request["currencycode"] = $json["currencycode"];
                    $confirm = true;
                }
                
                $response = $job->getResponse();
                $this->cleanJob($job, $confirm);
            }
            
            return json_encode($request);
        }
    }
    
    protected function cleanJob(\App\Entity\Job $job, $confirmed = true){
        $user = $job->getUser();
        if($confirmed){
            $this->accountingAgent->validateOrderAndSetBilled($user);
        }else{
            $this->accountingAgent->cancelOrder($user);
        }
        
        $this->em->remove($job);
        $this->em->flush();
    }
   
     /*
    * handle a mobile request in order to init a money transfer. 
    * build internal objects (orders, payments, customer, inventory...)to keep data for accounting and statistic    
    */
    public function handle(&$request){
        
        if(preg_match("/MOBILE_CLIENT/i", $request["socketclienttype"])){
           $job = null;
           $newInstance = false;
           $request["transid"] = $request["user_id"];
       
        
            $job = $this->em->getRepository(Job::Class)->getForUser($request["user_id"]);
            if(is_array($job) && !empty($job))
                $job = $job[0];
            
            if(empty($job)){
                $job = new Job();
                
                $job->setStatus("WAITING");
                $newInstance = true;
                $transDics = $this->em->getRepository(TransDictionnary::Class)->findAll();
                foreach($transDics as $dic){
                    if(preg_match("/".$dic->getCarreerRegExp()."/", $request["to"])){
                        $job->setTrans($dic);
                        break;
                    }
                }
                
                if($job->getTrans() == null)
                    throw new DatiException("BAD-SERVICE_NOT_AVAILABLE");
            
                $user = $this->em->getRepository(ShopUser::Class)->find($request["user_id"]);
                $job->setUser($user);
                $job->setStep("0");
                $thirdId = $this->accountingAgent->getThirdPartyId($user);
                
                if(preg_match("/^\d+$/",trim($thirdId))){
                    $request["third_partie_id"] = $thirdId;
                    $user->setThirdPartieId($request["third_partie_id"]);
                    
                    //retrieve cost and concerned product
                    $this->accountingAgent->retrieveCostAndProductId($request);
                    if(!array_key_exists("product_id", $request) || !array_key_exists("cost", $request))
                        throw new DatiException("BAD-SERVICE_NOT_AVAILABLE");
                    
                    //check if the current user has enough money in his balance
                    //TODO make some conversion from the request to be sure that the amount compared is in USD
                    if(!$this->checkCreditAvailability($user, $request))
                        throw new DatiException("BAD-INSUFFICIENT_BALANCE-".$request["totalcost"]."|".$user->getCredit()."|".$request["cost"]."|".$request["amount"]);
                    
                    $orderId = $this->accountingAgent->placeMoneyTransferOrder($request);
                    $user->setLastOrderId($orderId);
                }else{
                    echo "failed to retrieve thirdparty id from intranet accountancy...";
                }
                    
                $this->em->persist($job);
                $this->em->flush();
            }
            
            if($job instanceof Job){
                $transDic = $job->getTrans();
                $request["code"] = $request["confirm"] ? substr($transDic->getTransCode(), 0, (strlen($transDic->getTransCode()) - 1))."*PASS#" : $transDic->getTransCode();
                if($newInstance){
                    $job->setRequest(json_encode($request)); 
                }
                
                $request["step"] = $job->getStep() !== null ? $job->getStep() : "0";
                if($request["step"] == "0"){
                    $request["details"] = "transfer initialization";
                }else{
                    $steps = $job->getTrans()->getSteps();
                    foreach($steps as $step){
                        if($step->getPosition() == $request["step"]){
                            $matches = array();
                            if(array_key_exists("locale", $request) && preg_match("/^(en|fr)/i",$request["locale"],$matches)){
                               $request["details"] = $step->translate($matches[1])->getDescription();
                            }else{
                               $request["details"] = $step->translate("en")->getDescription(); 
                            }
                           break;
                        }
                    }
                }
                 $this->em->flush();
                $request["total"] =  count($job->getTrans()->getSteps()) - (array_key_exists("confirm", $request) && $request["confirm"] ? 0 : 1);
                return json_encode($request); 
            }
            throw new DatiException("BAD-TRANSACTION_CANCELED");
        }else{
            return "Oops! this operation is only sent by mobile. Pls check...";
        }
    }
   
    protected function checkCreditAvailability(ShopUser $user, &$request):bool{       
        if(!array_key_exists("cost", $request) || !array_key_exists("amount", $request)){
            echo "Oops!!! there is no cost or amount in the request comparison aborted...";
            return false;
        }
        
        $total = (double)$request["cost"] + (double)$request["amount"];
        $request["totalcost"] = $total;
        
        return (double)($user->getCredit()) >= (double)($total);
    }
}