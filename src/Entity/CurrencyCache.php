<?php

 /*
 * This file is part of the DATICASH PROJECT
 *
 * (c) ewoniewonimerveil@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use App\Command\DatiException;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity 
 * @Table(name="currency_cache")
 * @ORM\Entity(repositoryClass="App\Repository\CurrencyCacheRepository")
 */
class CurrencyCache
{

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

   /** @ORM\Column(type="string", length=32, nullable=false) */
    protected $source;
    
    /** @ORM\Column(type="string", nullable=false) */
    protected $timestamp;
    
    /** @ORM\Column(type="integer", nullable=false) */
    protected $hitCount = 0;
    
    /** @ORM\Column(type="text", nullable=false) */
    protected $quote;
    

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    public function setSource($source){
        $this->source = $source;
        return $this;
    }
    
    public function getSource(){
        return $this->source;
    }
    
    public function setTimestamp($timestamp){
        $this->timestamp = $timestamp;        
        return $this;
    }
    
    public function getTimestamp(){
        return $this->timestamp;
    }
    
    public function setQuote($quote){
        $this->quote = $quote;
        return $this;
    }
    
    public function getQuote(){
        return $this->quote;
    }
    
    public function setHitCount($count){
        $this->hitCount = $count;
        
        return $this;
        
    }
    public function getHitCount(){
        return $this->hitCount;
    }
}