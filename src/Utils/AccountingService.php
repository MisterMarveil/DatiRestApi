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


class AccountingService
{
    protected $apiKey = "295e21ba156499a53fdc89dea537eec0570c18e6";
    protected $em;
    
    public function __construct(EntityManagerInterface $em){
        set_time_limit(0);
        $this->em = $em;
    }
    
    protected function getResponse($url, $data = array(), $method="POST"){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if($method == "POST"){
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json', 
                'Accept: application/json',
                'DOLAPIKEY: '.$this->apiKey,
                'Content-Length: ' . strlen(json_encode($data))                                                                       
            ));
        }else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json', 
                'DOLAPIKEY: '.$this->apiKey
            ));
        }
        
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    public function retrieveCostAndProductId(&$request){
        $MONEY_TRANSFERT_PRODUCT_URL = "http://intranet.daticloud.com/htdocs/api/index.php/categories/1/objects?type=product";
    
        $results = $this->getResponse($MONEY_TRANSFERT_PRODUCT_URL, array(), "GET");
        $cost = "0";
        $code = "";
        
        if(!empty($results)){
            foreach($results as $result){
                $matches = array();
                if(preg_match("/^D\-(\d+\.?\d+?)\-(\d+\.?\d+?)$/i", trim($result["ref"]),$matches)){
                    $minPrice = (double)$matches[1];
                    $maxPrice = (double)$matches[2];
                    if($minPrice <= $request['convertedamount'] && $maxPrice >= $request['convertedamount']){
                        $cost = (double)$result["price_min_ttc"]; 
                        $request['product_id'] = $result['id'];
                        break;
                    }
                }else{
                    continue;
                }
            }
        }
        
        $request["cost"] = $cost;
        return; 
   }
    
    
    public function getThirdPartyId(ShopUser &$user){
        $thirdId = $user->getThirdPartieId();
        if($thirdId !== null)
            return $thirdId;
    
        $firstName = $user->getCustomer()->getFirstName();
        $lastName = $user->getCustomer()->getLastName();
        $customerData = array(
            "name" => ($firstName !== null || $lastName !== null) ? ($lastname != null ? $lastname : "")."".($firstname != null ? $firstname : "") : $user->getUsername(),
            "name_alias" => "App Users",
            "fournisseur" => "0",
            "client" => "1",    //0=no customer, 1=customer, 2=prospect, 3=customer and prospect
            "address" => $user->getCustomer()->getAddress(),
            "zip" => $user->getCustomer()->getZip(),
            "email" => $user->getCustomer()->getEmail(),
            "currency" => $user->getCustomer()->getCurrency(),
            "phone" => "00".$user->getUsername()
        );
        
        $THIRD_PARTIE_URL = "http://intranet.daticloud.com/htdocs/api/index.php/thirdparties";
        $id = $this->getResponse($THIRD_PARTIE_URL, $customerData);
        
        $user->setThirdPartieId($id);
        $this->em->flush();
        return $id;
    }
    
    public function placeMoneyTransferOrder(&$request){
        if((!array_key_exists("third_partie_id", $request) || empty($request["third_partie_id"]))
        || !array_key_exists("product_id", $request) || empty($request["product_id"])){
            echo "Oops !!! missing third_partie_id or product_id, order can not be placed. Operation Canceled.";
            
            return;
        }
        $now = new \DateTime();
        
       $orderData = array(
            "socid" =>  $request["third_partie_id"],
            "status" => "0",  // 1=validated, -1=canceled, 0=draft, 2=accepted
            "date" => $now->format("mm/dd/yy"),
            "note_public" => $now->format("mm/dd/yy")."- Money Transfer to ".$request["to"]
        );
       
       $CREATE_ORDER_URL = "http://intranet.daticloud.com/htdocs/api/index.php/orders";
    
       $orderId = $this->getResponse($CREATE_ORDER_URL, $orderData);
       
       $request["order_id"] = $orderId;
       $this->addOrderLine($request);
       
       return $orderId;
   }
    protected function addOrderLine(&$request){
       if(!array_key_exists("order_id", $request) || empty($request["order_id"])){
           echo "Oops !!! missing order_id, order line can not be added. Operation Canceled.";
            return;
       }
       
       $orderId = $request["order_id"];
       $ADD_LINE_TO_ORDER_URL = "http://intranet.daticloud.com/htdocs/api/index.php/orders/$orderId/lines";
       $orderLineData = array(
            "fk_product" => $request["product_id"],
            "fk_commande" => $orderId,
            "subprice" => $request["cost"],
            "qty" => "1",
            "product_type" => "1"
           );
       $response = $this->getResponse($ADD_LINE_TO_ORDER_URL, $orderLineData);
       
   }
    public function cancelOrder(ShopUser &$user){
        $SET_ORDER_DRAFT_URL = "http://intranet.daticloud.com/htdocs/api/index.php/orders/".$user->getLastOrderId()."/settodraft";
        $data = array("idwarehouse" => "1");
        
        $response = $this->getResponse($SET_ORDER_DRAFT_URL, $data);
        return (empty($response) || array_key_exists("error", $response));
    }         
    public function validateOrderAndSetBilled(ShopUser &$user){
        $SET_ORDER_BILLED_URL = "http://intranet.daticloud.com/htdocs/api/index.php/orders/".$user->getLastOrderId()."/setinvoiced";
         
        $response = $this->getResponse($SET_ORDER_BILLED_URL);
     
        return (array_key_exists("error", $response));
    }
}