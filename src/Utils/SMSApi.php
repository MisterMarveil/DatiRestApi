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

use App\Entity\User\ShopUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class SMSApi
{
    protected $type = "SMSPM";
    protected $authKey = "769a935887b3208c12fc650c2662f5b1e9083175";
    protected $sender = "8080";    
    protected $url;
    protected $admins = array("237696934483", "237698672241", "19095284904");
    protected $telnyxUrlSms;
    protected $telnyxSender = "+17068736160";
    protected $telnyxApiKey = "KEY016F40A84DBE0269FD0296BBC970A135_HgZ7dQL4Xuo1yh0wsQYFfi";
    protected $router;
    protected $logDir;
    
    public function __construct(UrlGeneratorInterface $router){
        $this->url = "http://panel.smspm.com/gateway/".$this->authKey."/api.v1/send";
        $this->telnyxUrlSms = "https://api.telnyx.com/v2/messages";
        $this->router = $router;
        $this->logDir = __DIR__."/../../var/log";
    }
    
    public function sendAdminMsg($content){
        if(strlen($content) > 159){
            $content = substr($content, 0, 159);
            
        }
        if(empty(trim($content))){
            echo "Oops! empty message. nothing to send. Operation canceled...";
        }
        
        foreach($this->admins as $admin){
            $this->sendMessage($content, $admin);
        }
    }
    public function sendMessage($content, $recipient){
        if($this->testRecipient($recipient)){
            if(preg_match("/^1\d+$/",$recipient)){
                echo "sending message to US...";
                $this->sendUsMsg($content, $recipient);
                return true;
            }else{
                echo "send message in the rest of the world...";
                $result = file_get_contents($this->buildUri($content, $recipient));
                $result = json_decode($result, TRUE );
                if(array_key_exists("submitted", $result) && $result["submitted"]){
                    return true;
                }else{
                   $line = "SMS SEND FAIL: To $recipient($content)- Response: ".var_dump($result);
                   $this->registerErrorLog($line);
                }
            }
        }
        
        echo "Oops! Bad recipient given. SMS operation to $recipient aborted...";
        return false;
    }
    
    protected function sendUsMsg($content, $recipient){        
        $data = array("from" => $this->telnyxSender, "to" => "+".$recipient, "text" =>  $content);
        $postdata = json_encode($data);
                
        $ch = curl_init($this->telnyxUrlSms);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Authorization: Bearer '.$this->telnyxApiKey));
        
        $result = curl_exec($ch);
        curl_close($ch);
        //print_r ($result);        
        
    }
    
    protected function registerErrorLog($line){
        $logFileName = $this->logDir . '/sms_error.log';
            $current = "";
            // Ouvre un fichier pour lire un contenu existant
            try{
                $current = file_get_contents($logFileName);
            }catch(\Exception $e){
                touch($logFileName);
                $current = file_get_contents($logFileName);
            }
        
            $now = new \DateTime();
			// Ajoute un path
			$current = $line."\n".$current;
			// Écrit le résultat dans le fichier
			$contentExists = file_put_contents($logFileName, $current);        
    }
    
    protected function buildUri($content, $recipient){
        $sender = $this->sender;
        
        return $this->url."?output=json&sender=$sender&phone=00"
                            .urlencode($recipient)
                            ."&message=".urlencode($content)
                            ."&reports_url=".urlencode($this->router->generate("dati_sms_delivery_status",
                                                                         array("user_id"=>$recipient,
                                                                         "status"=>"{status}"),
                                                                         UrlGeneratorInterface::ABSOLUTE_URL));
    }
    
    protected function testRecipient($recipient){
        return preg_match("/^(\d{4,16})+$/",$recipient);
    }
}