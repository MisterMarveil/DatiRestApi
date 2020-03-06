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

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;


class MSClientService
{
    protected $dbh;
    protected $serverName = "192.168.100.181";
    protected $portNo = "1433";
    protected $dbName = "TEL_DATA";
    protected $dbUser = "pradippatel265";
    protected $dbPass = "pra875@tel";
    protected $em;
    
    public function __construct(EntityManagerInterface $em){
        set_time_limit(0);
        $this->em = $em;
    }
    
    public function initPDO(){
        $connection_string = "odbc:DRIVER=FreeTDS;SERVER=".$this->serverName.";PORT=".$this->portNo.";DATABASE=".$this->dbName; 
            try {
               return new \PDO($connection_string, $this->dbUser, $this->dbPass);
            }catch(\PDOException $e) {
                echo "oops! erreur: ".$e->getMessage();
            }
            return false;
    }
    
    /**
        registerSipAccount is responsible to create a sip user in call database and save sip credentials in user account
    */
    public function registerSipAccount(User &$user){
        if(($dbh = $this->initPDO()) !== false){
            $sipPassword = $this->getSipPass($user, $dbh);            
            $user->setSiPwd($sipPassword);
            
            $this->em->flush();
             odbc_close_all();
            return true;
        }else{
            echo "oops! problem while opening pdo object in registerSipAccount method...";
            return false;
        }
    }
        
    protected function getSipPass(User &$user, &$dbh){
        $accountId = $this->getSipAccountId($user, $dbh);
        
        $sth = $dbh->prepare("SELECT PASSWORD FROM ACCOUNT_ALIASES WHERE ALIAS = ? ");
        $sth->execute(array($user->getUsername()));
        $results = $sth->fetchAll();
        
        $password = "";        
        foreach($results as $result){               
            $password = $result['PASSWORD'];
        }
        
        if(empty($password)){
            $sth = $dbh->prepare("INSERT INTO ACCOUNT_ALIASES(DNIS,ALIAS, ACCOUNT_ID, ACCOUNT_ALIAS_TYPE, PASSWORD,".
                                     " SIP_USER_ID) values(?,?,?,?,?,?)");
            
            $password = uniqid("sip_pass_");
            $sth->execute(array("*",$user->getUsername(), $accountId, "2", $password, "*"));  
        }
        
        return $password;
    }
    
    protected function getSipAccountId(User &$user, &$dbh){
        $customerId = $this->getSipCustomerId($user, $dbh);
        
        $accountId = $this->getAccountId($user->getUsername(), $dbh);
        
        if(empty($accountId)){//le compte n'existe pas
            $sth = $dbh->prepare("INSERT INTO ACCOUNTS(".
                                "ACCOUNT,".
                                "PIN,".
                                "CUSTOMER_ID,".
                                "PARENT_ACCOUNT_ID,".
                                "BATCH_ID,".
                                "SEQUENCE_NUMBER,".
                                "ACCOUNT_GROUP_ID,".
                                "ACCOUNT_TYPE,".
                                "CALLBACK_NUMBER,".
                                "BILLING_TYPE,".
                                "CREATION_DATE_TIME,".
                                "STARTING_BALANCE,".
                                "CREDIT_LIMIT,".
                                "BALANCE,".
                                "STARTING_PACKAGED_BALANCE1,".
                                "PACKAGED_BALANCE1,".
                                "COS_ID,".
                                "WRITE_CDR,".
                                "SERVICE_CHARGE_STATUS,".
                                "CALLS_TO_DATE,".
                                "MINUTES_TO_DATE_BILLED,".
                                "MINUTES_TO_DATE_ACTUAL,".
                                "PACKAGED_BALANCE2,".
                                "PACKAGED_BALANCE3,".
                                "PACKAGED_BALANCE4,".
                                "PACKAGED_BALANCE5,".
                                "STARTING_PACKAGED_BALANCE2,".
                                "STARTING_PACKAGED_BALANCE3,".
                                "STARTING_PACKAGED_BALANCE4,".
                                "STARTING_PACKAGED_BALANCE5,".
                                "PERIOD_CALLS_TO_DATE,".
                                "PERIOD_MINUTES_TO_DATE_BILLED,".
                                "PERIOD_MINUTES_TO_DATE_ACTUAL,".
                                "ACCOUNT_STATUS_TYPE".
                             ") values(?,?,?,?,?,?,?,?,?,?,GETDATE(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $sth->execute(array(
                $user->getUsername(),
                "",
                $customerId,
                "2",
                "1",
                "0",
                "3",
                "4",
                "",
                "2",
                "0",
                "0",
                "0",
                "0",
                "0",
                "807",
                "1",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "0",
                "4"
            )); //nous créons le compte avec la balance à 0 puisque nous allons synchroniser la balance la première fois que le solde sera consultée.
            
            $accountId = $this->getAccountId($user->getUsername(), $dbh);
        }
        return $accountId;
    }
    
    protected function getCustomerId($account, &$dbh){
        $sth = $dbh->prepare('SELECT CUSTOMER_ID FROM CUSTOMERS WHERE CUSTOMER = ?');
        $sth->execute(array($account));
        $results = $sth->fetchAll();
        
        
        foreach($results as $result){               
            return $result['CUSTOMER_ID'];
        }
        
        return "";
    }
    
    protected function getAccountId($account, &$dbh){
        $sth = $dbh->prepare('SELECT ACCOUNT_ID FROM ACCOUNTS WHERE ACCOUNT = ?');
        $sth->execute(array($account));
        $results = $sth->fetchAll();
        
        $accountId = "";
        
        foreach($results as $result){               
            return $accountId = $result['ACCOUNT_ID'];
        }
        
        return "";
    }
    
    protected function getSipCustomerId(User &$user, &$dbh){
        $customerId = $this->getCustomerId($user->getUsername(), $dbh);
        
        if(empty($customerId)){//le compte n'existe pas
            $sth = $dbh->prepare('INSERT INTO CUSTOMERS(CUSTOMER, FIRST_NAME, PUBLIC_CUSTOMER) values(?, ?, ?)');
            $sth->execute(array($user->getUsername(),"AppClient","1"));            
            $customerId = $this->getCustomerId($user->getUsername(), $dbh);
        }
        
        return $customerId;
    }
    
    public function getCallInitData(User &$user, $callerPhone){       
        if(($dbh = $this->initPDO()) !== false){
            $sth = $dbh->prepare("SELECT DESCRIPTION, PER_MINUTE_CHARGE  ".
                        " from rates where RATE_PLAN_ID = ?".
                        " and ? like number".
                        " order by number DESC");
                        
            $sth->execute(array("421", $callerPhone,"AppClient","1"));                        
            $results = $sth->fetchAll();
            
            $ratePerMinute = "";
            $rateDescription = "";
            
            foreach($results as $result){               
                $rateDescription = $result['DESCRIPTION'];
                $ratePerMinute = $result['PER_MINUTE_CHARGE'];
                break;
            }
            if(empty($ratePerMinute))
                $ratePerMinute = "false";
            
            if(empty($rateDescription))
                $rateDescription = "false";
            
            $sipPass = $user->getSiPwd();
            
            $lastData = $this->retrieveLastCallData($user, true);
            if(is_array($lastData) && array_key_exists("BILLING_ID", $lastData) && !empty($lastData["BILLING_ID"])){
                $user->setLastCallId($lastData["BILLING_ID"]);
                $this->em->flush();
            }
            
            return "\"host\": \"sip.viskiglobal.com\",\"registerednum\": \"".
                    $user->getUsername()."\", \"ratedescription\": \"$rateDescription\", \"rateperminute\": \"$ratePerMinute\",\"tmppassword\": \"$sipPass\"";
        }else{
            echo "oops! problem while opening pdo object in getCallInitData method...";
            return false;
        }
    }
            
    /*
        This function is responsible to sync user balance with the TEL_DATA db and store db
        the difference between the referencebalance of user and balance read from TEL_DATA, represent the cost of lasts calls
    */
    public function syncBalance(User $user, &$callCost):bool{
        return true;
        if(($dbh = $this->initPDO()) !== false){
            $sth = $dbh->prepare('SELECT BALANCE,ACCOUNT_ID FROM ACCOUNTS WHERE ACCOUNT = ?');
            $sth->execute(array($user->getUsername()));
            $results = $sth->fetchAll();
            $retrievedBalance = 0.00;
            $accountId = "";
            
            foreach($results as $result){
                $retrievedBalance = (double)($result['BALANCE']);
                $accountId = $result['ACCOUNT_ID'];
            }
            
            $reference = (double)$user->getCallBalanceReference();            
            
            if($reference == $retrievedBalance && $reference == (double)$user->getCredit()){//balance already synced
                return true;
            }else if($reference > 0 && $reference > $retrievedBalance){
                $callCost  = $reference - $retrievedBalance;
                
                //we subtract the cost of the calls from user's balance.
                $user->addCredit((-1) * $callCost);
                $this->em->flush();
            }
            
            $this->updateBalance($user, $retrievedBalance, $accountId);
            
            
             odbc_close_all();
            return true;
        }else{
            echo "oops! problem while opening pdo object in syncBalance method...";
            return false;
        }
    }
    
    public function retrieveLastCallData(User $user, $doNotUpdate=false){
        
        if(($dbh = $this->initPDO()) !== false){
            
            //we create billing entry
            $sth = $dbh->prepare("SELECT TOP (1) BILLING_ID".
                                                  ",CALL_SESSION_ID".
                                                  ",CALL_ID".
                                                  /*",ENTRY_TYPE".
                                                  ",ACCOUNT_ID".
                                                  ",ACCOUNT".
                                                  ",ACCOUNT_GROUP".
                                                  ",START_DATE_TIME".
                                                  ",CONNECT_DATE_TIME".
                                                  ",DISCONNECT_DATE_TIME".
                                                  ",LOGIN_NAME".
                                                  ",RATE_SCHEDULE".
                                                  ",RATE_PLAN".
                                                  ",NODE".
                                                  ",NODE_TYPE".
                                                  ",ORIGIN".
                                                  ",COUNTRY_CODE".
                                                  ",NPA".
                                                  ",NXX".
                                                  ",LOCAL_NUMBER".
                                                  ",DESCRIPTION".
                                                  ",DETAIL".*/
                                                  ",PER_CALL_CHARGE".
                                                  ",PER_MINUTE_CHARGE".
                                                  ",PER_CALL_SURCHARGE".
                                                  ",PER_MINUTE_SURCHARGE".
                                                  ",ACTUAL_DURATION".
                                                  ",QUANTITY".
                                                  ",AMOUNT ".
                                                   /*",CURRENCY".
                                                  ",CONVERSION_RATE".
                                                  ",MODULE_NAME".
                                                  ",ANI".
                                                  ",DNIS".
                                                  ",SALES_GROUP".
                                                  ",TAX_GROUP".
                                                  ",USER_1".
                                                  ",USER_2".
                                                  ",USER_3".
                                                  ",USER_4".
                                                  ",USER_5".
                                                  ",USER_6".
                                                  ",USER_7".
                                                  ",USER_8".
                                                  ",USER_9".
                                                  ",USER_10".
                                                  ",INFO_DIGITS".
                                                  ",RATE_INTERVAL".
                                                  ",DISCONNECT_CHARGE".
                                                  ",BILLING_DELAY".
                                                  ",GRACE_PERIOD".
                                                  ",ACCOUNT_TYPE".
                                                  ",PARENT_ACCOUNT".
                                                  ",PARENT_ACCOUNT_ID".
                                                  ",PACKAGED_BALANCE_INDEX ".*/
                                                " FROM BILLING ".
                            "where account = ? order by start_date_time DESC, connect_date_time DESC");
            
            $sth->execute(array($user->getUsername()));
            $results = $sth->fetchAll();
            $result = array();
            
            foreach($results as $resultat){               
                 $result = $resultat;
                 break;
            }
            
            if($result["ACTUAL_DURATION"] != 0 && !$doNotUpdate && $user->getLastCallId() != $result["BILLING_ID"]){//if the callcost is not 0, we update the balance
                //we update 
                $sth = $dbh->prepare('SELECT TOP(1) BALANCE FROM ACCOUNTS WHERE ACCOUNT = ?');
                $sth->execute(array($user->getUsername()));
                $results = $sth->fetchAll();
                foreach($results as $resultat){
                    $result["BALANCE"] = $resultat["BALANCE"];
                    if((double)$result["BALANCE"] != (double)$user->getCredit()){
                        $user->setCredit($result["BALANCE"]);
                        $this->em->flush();
                    }
                    break;
                }
            }else if($user->getLastCallId() == $result["BILLING_ID"]){
                $result["ACTUAL_DURATION"] = "0";
                $result["AMOUNT"] = "0";
            }
            
            odbc_close_all();
            
            
            return $result;
        }else{
            echo "unable to open database..";
        }
        return "0";
    }
    
    public function updateBalance(User $user, $oldRetrievedBalanceValue, $accountId){
        if((double)($user->getCredit()) == (double)($oldRetrievedBalanceValue)){//situation qui ne devrait jamais arriver si l'ancienne balance est égale à la nouvelle, il n'y a pas de mise à jour à faire
            //nothing to update 
            echo "Nothing to update cause old and new balances are equals... \n";
            return true;
        }
        
        if(($dbh = $this->initPDO()) !== false){
            //we update 
            $sth = $dbh->prepare('UPDATE ACCOUNTS SET BALANCE = ? WHERE ACCOUNT = ?');
            $sth->execute(array($user->getCredit(), $user->getUsername()));
            
            //we create billing entry
            $sth = $dbh->prepare("insert into BILLING(".
                         "CALL_SESSION_ID,".
                         "CALL_ID,".
                         "ENTRY_TYPE,".
                         "ACCOUNT_ID,".
                         "ACCOUNT,".
                         "ACCOUNT_GROUP,".
                         "START_DATE_TIME,".
                         "CONNECT_DATE_TIME,".
                         "DISCONNECT_DATE_TIME,".
                         "LOGIN_NAME,".
                         "RATE_SCHEDULE,".
                         "NODE,".
                         "NODE_TYPE,".
                         "ORIGIN,".
                         "PER_CALL_CHARGE,".
                         "PER_MINUTE_CHARGE,".
                         "PER_CALL_SURCHARGE,".
                         "PER_MINUTE_SURCHARGE,".
                         "ACTUAL_DURATION,".
                         "QUANTITY,".
                         "AMOUNT,".
                         "CURRENCY,".
                         "CONVERSION_RATE,".
                         "RATE_INTERVAL,".
                         "DISCONNECT_CHARGE,".
                         "BILLING_DELAY,".
                         "GRACE_PERIOD,".
                         "ACCOUNT_TYPE,".
                         "PARENT_ACCOUNT,".
                         "PARENT_ACCOUNT_ID,".
                         "PACKAGED_BALANCE_INDEX".
                         ") values(?,?,?,?,?,?,GETDATE(),GETDATE(),".
                             "GETDATE(),?,?,?,?,'',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            
        
            $sessionId = uniqid("call_trans_");
            $type = ""; //precise the type of the billing either a refund or a payment
            $amountCredited = 0;
            if((double)($user->getCredit()) > (double)($oldRetrievedBalanceValue)){//ajout dans la facturation
                $type = "11"; //payment type        
                $amountCredited = (double)($user->getCredit()) - (double)($oldRetrievedBalanceValue);
            }else if((double)($user->getCredit()) < (double)($oldRetrievedBalanceValue)){//retrait dans la facturation                
                $type = "13"; //refund type
                $amountCredited =  (double)($oldRetrievedBalanceValue) - (double)($user->getCredit());
            }
            
            $sth->execute(array($sessionId,
                                $sessionId,
                                $type,
                                $accountId,
                                $user->getUsername(),
                                "Viski Global",
                                $user->getUsername(),
                                "VISKI GLOBAL",
                                "TMC",
                                "3",
                                "0",
                                "0",
                                "0",
                                "0",
                                "0",
                                "0",
                                $amountCredited,
                                "USD",
                                "1",
                                "0",
                                "0",
                                "0",
                                "0",
                                "6",
                                "RES0991147",
                                "7257",
                                "0"));
            //echo "new billing entry of type ".($type == "11" ? "Payment" : "Refund")." pushed...";
            
            $user->setCallBalanceReference($user->getCredit());
            $this->em->flush();
            odbc_close_all();
            
            return $sessionId;            
        }else{ 
            echo "oops! problem while opening pdo object in updateBalance method...";
            return false;
        }
    }
            
}