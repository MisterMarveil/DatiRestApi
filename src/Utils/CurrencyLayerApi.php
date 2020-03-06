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
//use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\CurrencyCache;


class CurrencyLayerApi
{
    protected $liveUrl;
    protected $accessKey;
    protected $accessKeyAlt;
    protected $daysLeftToMonth; 
    protected $refreshTimeDelay;
    protected $monthlyHitThreshold;
    protected $em;
    
    public function __construct(EntityManagerInterface $em){
        $this->liveUrl = "http://apilayer.net/api/live";
        $this->accessKey = "0514141ab2ebccb4989f64e7085b0f11";
        $this->accessKeyAlt = "5473705b3394c230fe53f5b2f7b17c57";
        $this->daysLeftToMonth = 31;
        $this->refreshTimeDelay = (31 * 24 * 3600);
        $this->monthlyHitThreshold = 480;
        $this->em = $em;        
    }
    
    public function convert($from, $to, $amount){
        if(strtolower(trim($from)) == strtolower(trim($to)))
            return $amount;
        $currencyCache = $this->refreshCacheData();
        
        if(!$currencyCache instanceof CurrencyCache)
            throw new \Exception("Caution! bad currency object given...");
        
        $quotes = json_decode($currencyCache->getQuote(), true);
        
        if(!array_key_exists("USD".$from, $quotes)){
            echo "Unsupported $from (from) Currency requested. <br/>";
            return $amount;
        }else if(!array_key_exists("USD".$to, $quotes)){
             echo "Unsupported $to (to) Currency requested. <br> ";
            return $amount;
        }else{
            $fromCoef = (double)($quotes["USD".$from]);
            $toCoef = (double)($quotes["USD".$to]);

            return (($toCoef / $fromCoef) * $amount);
        }
    }    
    
    protected function buildLiveUri($currenciesArray = array(), $alt = false){
        return $this->liveUrl."?access_key=".($alt ? $this->accessKey : $this->accessKeyAlt).(count($currenciesArray) >= 1 ? "&currencies=".implode(",", $currenciesArray) : "");
    }
    
    protected function refreshCacheData(){
        $currencyCache = $this->em->getRepository(CurrencyCache::class)->findAll();
        if(is_array($currencyCache) && !empty($currencyCache))
            $currencyCache = $currencyCache[0];
        
        if(empty($currencyCache) || ($currencyCache instanceof CurrencyCache && $this->needToRenewCache($currencyCache))){
            if(empty($currencyCache))
                $currencyCache = new CurrencyCache();
                
            $ch = curl_init($this->buildLiveUri());
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            
            $result = curl_exec($ch);
            curl_close($ch);
            
            
            $result = json_decode($result, TRUE );
            
            if(array_key_exists("success", $result) && $result["success"]){
                $hitCount = $currencyCache->getHitCount() != null ? (int)$currencyCache->getHitCount() + 1 : 1;
                echo "*latest hitCount: $hitCount <br/>";
                
                //var_dump($result);
                
                $now = new \DateTime();
                $currencyCache->setTimestamp($now->getTimestamp());
                $currencyCache->setQuote(json_encode($result["quotes"]));
                $currencyCache->setHitCount($hitCount);
                $currencyCache->setSource($result["source"]);
                $this->em->persist($currencyCache);
                $this->em->flush();
                
                echo "cache retrieved online successfully <br><br>";                
            }else{
                echo "problem while retrieving live currency data..<br/> ";
                if(array_key_exists("error", $result) && array_key_exists("code", $result["error"]) &&
                    $result["error"]["code"] == 104){
                    
                    $ch = curl_init($this->buildLiveUri(array(),true));
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                    
                    $result = curl_exec($ch);
                    curl_close($ch);
             
                    $result = json_decode($result, TRUE );
            
                    if(array_key_exists("success", $result) && $result["success"]){
                        $hitCount = $currencyCache->getHitCount() != null ? (int)$currencyCache->getHitCount() + 1 : 0;
                        echo "latest hitCount: $hitCount <br/>";
                        
                        $now = new \DateTime();
                        $currencyCache->setTimestamp($now->getTimestamp());
                        $currencyCache->setQuote($result["quotes"]);
                        $currencyCache->setHitCount($hitCount);
                        $currencyCache->setSource($result["source"]);
                        $this->em->persist($currencyCache);
                        $this->em->flush();
                        
                        echo "cache retrieved online successfully <br><br>";
                    }
                }
               
            }
        }else{
            echo "no need to refresh currencies was found <br/>";
        }
        
        return $currencyCache;
    }
   
    protected function needToRenewCache(CurrencyCache $cache){
        $now = new \DateTime();
        
        $hitCount = $cache->getHitCount() != null ? (int)($cache->getHitCount()) : 0;
        $leftHit = $this->monthlyHitThreshold - $hitCount;
        
        $currentDay = (int)($now->format('d'));
        $nbOfDaysInTheCurrentMonth = (int)($now->modify('last day of')->format('d'));
        
        $nbOfDayLeftToMonth = $nbOfDaysInTheCurrentMonth - $currentDay;
        
        $nbOfSecondsDelayToRefresh = round((24.0 / ((double)$leftHit / (double)$nbOfDayLeftToMonth)) * 3600.0);
        echo "refresh time computed: $nbOfSecondsDelayToRefresh <br/>";
        
        $now = new \DateTime();
        return ($now->getTimestamp() - $cache->getTimestamp()) > $nbOfSecondsDelayToRefresh;
    }
}