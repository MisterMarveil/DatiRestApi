<?php

/*
 * This file is part of the DATICASH PROJECT
 *
 * (c) ewoniewonimerveil@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @TODO: hash token before storage
 */

namespace App\UserManager;

//use Symfony\Component\EventDispatcher\GenericEvent;
use App\Exception\DatiException;
use App\Entity\User;
use App\Utils\SMSApi;
use App\Utils\MSClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use FOS\UserBundle\Model\UserManagerInterface;

class Manager
{
   protected $userManager;
   protected $em;
   protected $smsManager;
   protected $msClient;
   protected $intranetCustomerService;
   protected $passwordEncoder;
   
   public function __construct(UserManagerInterface $manager, EntityManagerInterface $em, SMSApi $smsMan,
                                MSClientService $msClient, CustomerService $custService, UserPasswordEncoderInterface $encoder){
      $this->userManager = $manager; 
      $this->smsManager = $smsMan;
      $this->msClient = $msClient;
      $this->intranetCustomerService = $custService;
      $this->passwordEncoder = $encoder;
      $this->em = $em;
   }

   public function getEncoder(){
       return $this->passwordEncoder;
   }

    public function getSmsApi(){
        return $this->smsManager;
    }
    
    public function getMSClient(){
        return $this->msClient;
    }
    
    /*
    *   @params   $request["identifier", "unit"]
    */
    public function getBalance($request){
        $user = $this->identifyUser($request);        
        //$callCost = "";
        //$this->getMSClient()->syncBalance($user, $callCost);
        
        throw new DatiException("balance_".$user->getCredit());
    }

    /*
    *   @params   $request["identifier", "unit"]
    */
    public function sipAccountRegistered($request){
        $user = $this->identifyUser($request);        
        $user->setSiPwd($request["registeredsippass"]);
        $this->em->flush();
        
        echo "account sip password registered for ".$user->getUsername();
    }
    
    public function authenticateUser(&$request, &$wasIdentifiedBySession = false){   
        $identifier = "";
        if(array_key_exists("identifier", $request))
            $identifier = $request["identifier"];
        $password = "";
        if(array_key_exists("password", $request))
            $password = $request["password"];
        $session = "";
        if(array_key_exists("sessionid", $request))
            $session = $request["sessionid"];
        
        if((empty($identifier) || empty($password)) && empty($session)) {
            echo "empty identifier or empty password detected ";
            throw new DatiException("login-bad");
        }
        $user = null;
        try{
            $user = $this->identifyUser($request, $wasIdentifiedBySession);
           
        }catch(DatiException $e){
            //echo "catched:".$e->getMessage();
            if(preg_match("/CREDENTIALS_BAD/i", $e->getMessage())){               
                throw new DatiException("non-existent-account");
            }  
        }
        
        if($wasIdentifiedBySession){
            $request["identifier"] = $user->getUsername();
            
            $user->setLastLogin(new \DateTime());
            
            $this->em->flush();
            throw new DatiException("login-ok");    
        }
        
        if(!($user instanceof User)){
            //echo "user was not a regular user instance... will throw login-bad";
            throw new DatiException("login-bad");
        }
            
        if(!$user->isEnabled()){//besoin d'activation du compte 
            //echo "non activated account detected \n";
            $token = $user->getPasswordResetToken();
            $tokenAt = $user->getPasswordRequestedAt();
            
            if((empty($token) && empty($tokenAt)) || (!empty($token)) || (!$this->isPasswordTokenNonExpired($user))){ //code activation requis
                $token = "";
                try{
                    $token = $this->getRandToken();
                    $request["identifier"] = $user->getUsername();
                    $request["token"] = $token;
                    $this->savePasswordResetToken($request);
                }catch(\Exception $e){
                    if(!preg_match("/TOKEN_SAVE_OK/i", $e->getMessage())){
                        //echo "token:".$token;
                        //echo "msg:".$e->getMessage();
                        throw new DatiException($e->getMessage());
                    }                    
                }
            }
            throw new DatiException("confirm-token-form-".$token);
        }
        
        $encoder = $this->getEncoder();
        echo "was there";
        //check password
        if($encoder->isPasswordValid($user, $password)){
            $user->setLastLogin(new \DateTime());
            $this->em->flush();
            
            throw new DatiException("login-ok");
        } else {
            echo "also";
            throw new DatiException("login-bad");
        }        
    }
    
    public function testAccount(&$request){   
        $identifier = "";
        if(array_key_exists("identifier", $request))
            $identifier = $request["identifier"];
        
        try{
            $user = $this->userManager->findUserByUsername($identifier);
        }catch(\Exception $e){
            throw new DatiException("account-non-existent");
        }
        
        if(!($user instanceof User)){
            //echo "user was not a regular user instance... will throw account-non-existent";
            throw new DatiException("account-non-existent");
        }
            
        if(!$user->isEnabled()){//besoin d'activation du compte 
            echo "non activated account detected \n";
            $token = $user->getPasswordResetToken();
            $tokenAt = $user->getPasswordRequestedAt();
            
            if((empty($token) && empty($tokenAt)) || (!empty($token) && (!$this->isPasswordTokenNonExpired($user)))){ //code activation requis
                $token = "";
                try{
                    $token = $this->getRandToken();
                    $request["identifier"] = $user->getUsername();
                    $request["token"] = $token;
                    $this->savePasswordResetToken($request);
                }catch(\Exception $e){
                    if(!preg_match("/TOKEN_SAVE_OK/i", $e->getMessage())){
                        //echo "token:".$token;
                        //echo "msg:".$e->getMessage();
                        throw new DatiException($e->getMessage());
                    }                    
                }
            }
            throw new DatiException("confirm-token-form-".$token);
        }else{
            throw new DatiException("ready");
        }
    }
    
    
    public function authorizeUser(&$request){
        $wasIdentifiedBySession = false;
        try{
            $this->authenticateUser($request, $wasIdentifiedBySession);
        }catch(DatiException $e){
            if(!preg_match("/login-ok/i", $e->getMessage())){
                 throw new DatiException($e->getMessage());
            }else{
                if($wasIdentifiedBySession)
                    throw new DatiException("login-ok");
                
                    
                $password = "";
                $user = $this->identifyUser($request);
                
                $session  = uniqid("sec_");
                
                $request["sessionid"] = $session;
                $user->setSecurityToken($session);
                if(!array_key_exists("user_id", $request))
                    $request["user_id"] = $user->getId();
                
                if($user->getSiPwd() !== null && strlen($user->getSiPwd()) > 4){
                    $password = $user->getSiPwd();
                }else{
                    $this->getMSClient()->registerSipAccount($user);
                    if($user->getSiPwd() !== null && strlen($user->getSiPwd()) > 4){
                        $password = $user->getSiPwd();
                    }else{
                        echo "Oops! something bad happen while trying to register account ";
                    }
                }
                $this->em->flush();
                throw new DatiException("login-ok-".$password);
            }
        }      
    }
    
    public function sendTokenMail($token, $json){        
        $user = $this->identifyUser($json);
        $this->getEmailSender()->send("activation_token", [$user->getEmail()], ['user' => $user]); 
        //$this->sender->send($smsCmd);            
    }
    
    public function savePasswordResetToken($request)
    {
        if(!array_key_exists("token", $request) || strlen($request["token"]) < 5)
            return false;
        
        $token = $request["token"];
        $user = $this->identifyUser($request);
        $user->setPasswordResetToken($token);
        $user->setPasswordRequestedAt(new \DateTime());
        $this->em->flush();
    
            throw new DatiException("TOKEN_SAVE_OK");
    }
    
    public function resetPassword($request)
    {   
            $token = $request["token"];
            $user = $this->identifyUser($request);
        
         if($user->getPasswordResetToken() === $token){
            $lifetime = new \DateInterval("PT5H");
            if (!$user->isPasswordRequestNonExpired($lifetime)){
                return $this->handleExpiredToken($user);
            }
            
            if(!array_key_exists("newpassword", $request) || strlen($request["newpassword"]) < 4)
                throw new DatiException("PASSWORD-LENGTH-BAD");
        
            return $this->handleResetPassword($user, $request["newpassword"]);
        }else{
            throw new DatiException("TOKEN-BAD");
        }
    }
    
    public function identifyUser(&$request, &$wasIdentifiedBySession = false){
        
        if(array_key_exists("identifier", $request)){            
            $identifier =  $request["identifier"];
        }else if(array_key_exists("countrycode", $request) && array_key_exists("phonenumber", $request)){
            $identifier = $request["countrycode"]."".$request["phonenumber"];
        }else if(array_key_exists("sessionid", $request) && !empty($request["sessionid"])){
            $sessionTtlForSecuritySessionInSec = 3600;
            
            $user = $this->getUserRepository()->findBySecurityToken($request["sessionid"]);           
            if(is_array($user) && !empty($user))
                $user = $user[0];
            
            if($user instanceof User){
                if($user->getLastLogin() == null){
                    throw new DatiException("CREDENTIALS_BAD");
                }
                
                $lastConnectionTimestamp = $user->getLastLogin()->getTimestamp();
                $now = new \DateTime();
                $spentTimestamp = $now->getTimestamp() - $lastConnectionTimestamp;
                //echo "Spent Time: $spentTimestamp <br/><br/>";
                if($spentTimestamp > $sessionTtlForSecuritySessionInSec){
                    //the spent time from the last connection is greater than allowed
                    throw new DatiException("CREDENTIALS_BAD");
                }
                
                $user->setLastLogin(new \DateTime());
                $this->em->flush();
                
                if(!array_key_exists("user_id", $request))
                    $request["user_id"] = $user->getId();
                $wasIdentifiedBySession = true;
                return $user;
            }
            
            throw new DatiException("CREDENTIALS_BAD");
        }else{
            echo "there's no identifier provided \n";
            throw new DatiException("CREDENTIALS_BAD");
        }
        
        try{
            $user = $this->userManager->findUserByUsername($identifier);
        }catch(\Exception $e){      
            echo "username was not founded \n";
            throw new DatiException("CREDENTIALS_BAD");
        }       
        
        if($user instanceof User){  
             if(!array_key_exists("user_id", $request))
                $request["user_id"] = $user->getId();
            return $user;
        }
        
       // echo "user was not instanceof User \n";
        throw new DatiException("CREDENTIALS_BAD");
    }

    public function verifyEmail($request)
    { 
        //TODO: verifyEmail
        /** @var UserInterface $user */
        /*$user = $this->getUserRepository()->findOneBy(['emailVerificationToken' => $token]);       

        $user->setVerifiedAt(new \DateTime());
        $user->setEmailVerificationToken(null);
        $user->enable();

        $eventDispatcher = $this->getEventDispatcher();
        $eventDispatcher->dispatch(UserEvents::PRE_EMAIL_VERIFICATION, new GenericEvent($user));

        $this->manager->flush();

        $eventDispatcher->dispatch(UserEvents::POST_EMAIL_VERIFICATION, new GenericEvent($user));

        if (!$configuration->isHtmlRequest()) {
            return $this->viewHandler->handle($configuration, View::create($user));
        }

        $flashMessage = $this->getSyliusAttribute($request, 'flash', 'sylius.user.verify_email');
        $this->addTranslatedFlash('success', $flashMessage);

        return $response;*/
    }

    public function requestVerificationToken($request)
    {
        /* $user = $this->identifyUser($request);
        if ((bool)($user->getCustomer()->getShopMailActivated())) {
            throw new DatiException("already_confirmed");
        }else if(!filter_var($user->getCustomer()->getShopMail(), FILTER_VALIDATE_EMAIL)){
            throw new DatiException("bad_or_no_mail");
        }
        
        
       
        $token = $this->getRandToken();

        $user->setEmailVerificationToken($token);

        $this->em->flush();

        throw new DatiException("VERIFICATION-TOKEN-SAVE-OK-".$token."-".$user->getCustomer()->getShopMail());*/
    }
    
    public function updateProfile($request){
        //TODO update intranet
        $user = $this->identifyUser($request);
        $customer = $user->getCustomer();
        
        if(array_key_exists("billtolastname", $request) && !empty(trim($request["billtolastname"]))){
            $customer->setLastName($request["billtolastname"]);
        }
        
        if(array_key_exists("billtofirstname", $request) && !empty(trim($request["billtofirstname"]))){
            $customer->setFirstName($request["billtofirstname"]);
        }
        
        if(array_key_exists("billtocountry", $request) && !empty(trim($request["billtocountry"]))){
            $customer->setCountry($request["billtocountry"]);
        }
        
        
        if(array_key_exists("billtostate", $request) && !empty(trim($request["billtostate"]))){
            $customer->setState($request["billtostate"]);
        }
        
        if(array_key_exists("billtozip", $request) && !empty(trim($request["billtozip"]))){
            $customer->setZip($request["billtozip"]);
        }
        
        if(array_key_exists("billtocity", $request) && !empty(trim($request["billtocity"]))){
            $customer->setCity($request["billtocity"]);
        }
        
        if(array_key_exists("billtoaddress", $request) && !empty(trim($request["billtoaddress"]))){
            $customer->setAddress($request["billtoaddress"]);
        }
        
        $token = "null";
        $needMailConfirm = "0";
        if(array_key_exists("email", $request) && filter_var($request["email"], FILTER_VALIDATE_EMAIL)){
            if(strtolower($customer->getShopMail()) != strtolower($request["email"])){
                $customer->setShopMail($request["email"]);
                $customer->setShopMailActivated(false);
                 
                $token = $this->getRandToken();
                $user->setEmailVerificationToken($token);
                $needMailConfirm = "1";
            }
            
        }
        
        //echo "user data successfully updated...";
        $this->em->flush();
        
        $user->setUsername($request["countrycode"]."".$request["phonenumber"]);
        $user->setUsernameCanonical($request["countrycode"]."".$request["phonenumber"]);
        $this->em->flush();
        
        throw new DatiException("profile-update-ok-".$token."-".$customer->getShopMail()."-".$needMailConfirm); 
    }

    protected function handleExpiredToken(UserInterface $user)
    {
        $user->setPasswordResetToken(null);
        $user->setPasswordRequestedAt(null);

        $this->em->flush();
        
        throw new DatiException("EXPIRED");
    }
    
    public function handleResetPasswordRequest($request){
        $user = $this->identifyUser($request);
        $token = $user->getPasswordResetToken();
        $tokenAt = $user->getPasswordRequestedAt();
            
        if((empty($token) && empty($tokenAt)) || (!empty($token)) && (!$this->isPasswordTokenNonExpired($user))){ //code activation requis
            $token = "";
            try{
                $token = $this->getRandToken();
                $request = array("identifier"=>$user->getUsername(), "token"=>$token);
                $this->savePasswordResetToken($request);
            }catch(\Exception $e){                    
                if(!preg_match("/TOKEN_SAVE_OK/i", $e->getMessage())){
                      throw new DatiException("token-send-bad"); 
                }                
            }
        }
        
        switch($this->getTokenSendMethod($user)){
            case "mail":
                throw new DatiException("token-mail-send-ok-".$token."-".$user->getShopMail());
            break;
            default:   //sms
                throw new DatiException("token-sms-send-ok-".$token."-sms");
               
        }
    }
    
    /*
    * getTokenSendMethod
    * responsible to evaluate the $method to use for token sending
    * if the mail is already activated, the mail is usen, the sms is used if not
    */
    protected function getTokenSendMethod($user){
        if((bool)$user->getCustomer()->getShopMailActivated()){
            return "mail";
        }else{
            return "sms";
        }
    }
    
    protected function getRandToken(){
        return mt_rand(200002,999999);
    }    

    protected function handleResetPassword(UserInterface $user, string $newPassword){
        $request = array("identifier"=>$user->getUsername(), $request["newpassword"]);
        $this->updatePassword($user, $request);
        
        throw new DatiException("PASSWORD-RESET-OK");
    }

    public function handleChangePassword($request){
        $user = $this->identifyUser($request);
        $oldPassword = $request["oldpassword"];
        
        try{
            //verification des identifiants
            $specialRequest = array("password"=>$oldPassword, "identifier"=>$request["identifier"]);
            $this->authenticateUser($specialRequest);
        }catch(DatiException $e){
            if(preg_match("/login-ok/i", $e->getMessage())){
                if(!array_key_exists("newpassword", $request) || strlen($request["newpassword"]) < 4)
                    throw new DatiException("PASSWORD-LENGTH-BAD");
 
                if(filter_var($request["email"], FILTER_VALIDATE_EMAIL)) {
                    $userTest = $this->getUserRepository()->findOneByEmail($request["email"]);
                    if ($userTest instanceof User && $userTest->getId() != $user->getId()) {
                        throw new DatiException("mail-already");
                    }                    
                    $user->getCustomer()->setShopMail($request["email"]); 
                } else{
                     throw new DatiException("mail-bad");
                }
                
                if(array_key_exists("billtofirstname", $request)){
                    $user->getCustomer()->setFirstName($request["billtofirstname"]);
                }
                if(array_key_exists("billtolastname", $request)){
                    $user->getCustomer()->setLastName($request["billtolastname"]);
                }
                if(array_key_exists("identifier", $request)){
                    $user->getCustomer()->setPhoneNumber($request["identifier"]);
                }
                
                if(array_key_exists("billtozip", $request)){
                    $user->getCustomer()->setZip($request["billtozip"]);
                }
                
                if(array_key_exists("billtocity", $request)){
                    $user->getCustomer()->setCity($request["billtocity"]);
                }
                
                if(array_key_exists("billtoaddress", $request)){
                    $user->getCustomer()->setAddress($request["billtoaddress"]);
                }
                
                if(array_key_exists("billtocountry", $request)){
                    $user->getCustomer()->setCountry($request["billtocountry"]);
                }

                if(array_key_exists("billtostate", $request)){
                    $user->getCustomer()->setCountry($request["billtostate"]);
                }
                
                $this->updatePassword($user, $request);
                throw new DatiException("password-change-ok");
            }else{
                throw new DatiException("oldpassword-bad");
            }
        } 
    }
    protected function applyUpdatePassword($user, $request){
        $user->setPlainPassword($request["newpassword"]);
        $this->updatePassword($user);
        $this->em->flush();
        $user->setUsername($request["identifier"]);
        $user->setUsernameCanonical($request["identifier"]);
        $this->em->flush();        
    }
    
    public function findUserBySecurityToken($token){
        return $this->getUserRepository()->findOneBy(['securityToken' => $token]); 
    }
    
    public function createUserFromRequest(&$request, array $securityRoles = ["ROLE_CLIENT_MOBILE", "ROLE_USER"])
    { 
        $phoneNumber = $request["countrycode"].$request["phonenumber"];
        $email = $request["email"];
        $password = $request["password"];
        $request["sessionid"] = "";
        
    
        $user = $this->userManager->findUserByUsername($phoneNumber);
        if ($user instanceof User) {
            throw new DatiException("ALREADY-phonenumber");
        }
        
        
        $user = $this->userManager->findUserByEmail($email);
        if ($user instanceof User) {
            throw new DatiException("ALREADY-email");
        }
        
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {           
            throw new DatiException("BAD_EMAIL");
        }
        
        //user creation 
        $user = $this->userManager->createUser();
        $user->setPlainPassword($password);
        if (preg_match("/^\d{4,16}$/", $phoneNumber)){ //numero de téléphone valide
            $user->setUsername($phoneNumber);            
            $user->setUsernameCanonical($phoneNumber);            
        }else{
            throw new DatiException("BAD_PHONENUMBER");
        }        
        
        //email already verified above
        $user->setEmail($email);            
        $user->setEmailCanonical($email);
        
        foreach ($securityRoles as $role) {
            $user->addRole($role);
        }
        
        $this->em->persist($user);
        $this->em->flush();
        
        $token = "";
        try{
            $token = $this->getRandToken();
            $request["identifier"] = $user->getUsername();
            $request["token"] = $token;
            $this->savePasswordResetToken($request);
        }catch(\Exception $e){
            if(!preg_match("/TOKEN_SAVE_OK/i", $e->getMessage())){
                //echo "token:".$token;
                //echo "msg:".$e->getMessage();
                $token = "";
            }
        }
        
        throw new DatiException("CONFIRM-TOKEN-FORM-".$token);
    }
    
    protected function isPasswordTokenNonExpired(UserInterface $user){
         $lifetime = new \DateInterval("PT5H");
         $requestTime = $user->getSecurityTokenRequestedAt();
         
         if($requestTime !== null){
             $now = new \DateTime();
             $requestTime->add($lifetime);
             return $now < $requestTime;
         }
         
         return false;
    }
    
    public function activateUser($request){
        $user = $this->identifyUser($request);
        if(!array_key_exists("token", $request)){
            throw new DatiException("BAD");
        }
        
        if($user->isEnabled()){//le compte est déjà activé, il s'agit de l'activation du mail
            
           /* if ((bool)($user->getCustomer()->getShopMailActivated())) {
            
            }else{
                if($user->getEmailVerificationToken() == $request["token"]){
                    $user->getCustomer()->setShopMailActivated(true);   
                    $user->setVerifiedAt(new \DateTime());
                    $user->setEmailVerificationToken(null);
                }else{
                    throw new DatiException("BAD");
                }
            }*/
            
            throw new DatiException("ALREADY");
        }
        
        if($user->getPasswordResetToken() == $request["token"]){            
            /*if (!$this->isPasswordTokenNonExpired($user)){
                echo "the token is expired... <br/>";
                return $this->handleExpiredToken($user);
            }*/
            
            $user->setPasswordResetToken("");
            $user->setEnabled(true);
            $user->setPasswordRequestedAt(null);
            $this->em->flush();
            
            $this->getMSClient()->registerSipAccount($user);

            throw new DatiException("OK");
        }
    }
   
}
