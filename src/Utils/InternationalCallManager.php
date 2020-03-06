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
  
    use App\Utils\PaymentServiceInterface;
    use App\Exception\DatiException;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use App\UserManager\Manager as UserManager;
    


class InternationalCallManager
{
    protected $userController;
    protected $orderFactory;
    protected $channelContext;
    protected $variantRepository;
    protected $orderItemFactory;
    protected $itemQtyModifier;
    protected $orderProcessor;
    protected $orderRepository;
    protected $addressRepository;
    protected $addressFactory;
    protected $smFactory;
    protected $em;
    protected $paymentFactory;
    protected $paymentMethodRepository;
    
    public function __construct(UserManager $controller, EntityManagerInterface $em){
        set_time_limit(0);
        $this->userController = $controller;
        $this->em = $em;
    }
    
    public function registerSipPass(&$request){        
        $user = $this->userController->identifyUser($request);    
        if(array_key_exists("registeredpass", $request) && !empty($request["registeredpass"])
            && $user->getSiPwd() != $request["registeredpass"]){            
            $user->setSiPwd($request["registeredpass"]);
            $this->em->flush();
            echo "sip pass updated successfully.";
        }else{
            echo "sip password insertion aborted. Given pass value: ".$request["registeredpass"];
        }        
        
        throw new DatiException("nottosend");
    }
    
     public function initCall(&$request, $callerphonenumber){
        $user = $this->userController->identifyUser($request);    
        $response = $this->userController->getMSClient()->getCallInitData($user, $callerphonenumber);
        throw new DatiException($response);
    }
    
    /*
    * register a call initialization requested by a mobile
    */
    public function registerCall(&$request){
        /*if(!array_key_exists("callcost", $request) || ((double)$request["callcost"]) <= 0.0){
            throw new DatiException("nottosend");
        }*/
        
        $user = $this->userController->identifyUser($request);    
        $callCost = "0";        
        $lastCallDuration = "0";
        
        $result = $this->userController->getMSClient()->retrieveLastCallData($user);
        
        if(is_array($result) && array_key_exists("ACTUAL_DURATION", $result) && array_key_exists("AMOUNT", $result)){
            $lastCallDuration = $result["ACTUAL_DURATION"];
            $callCost = $result["AMOUNT"];
        }
        
    
        if((double)$lastCallDuration == 0){
            echo "this call duration is less than a second. store call register process aborted...";
        }else{
            //at this step the balance is already synced
            //TODO INSERT INVOICE DATA TO INTRANET
            
        }
        
        throw new DatiException("\"callcost\": \"$callCost\",\"duration\": \"$lastCallDuration\" ");
        
    }
}