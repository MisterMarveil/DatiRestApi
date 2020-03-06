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
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;

/**
 * @Entity 
 * @Table(name="trans_dictionnary")
 * @ORM\Entity(repositoryClass="App\Repository\TransDictionnaryRepository")
 */
class TransDictionnary implements TranslatableInterface
{
    use TranslatableTrait;
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @ORM\Column(type="string", length=64, nullable=true) */
    protected $recipientNameRegExp;
    
    /** @ORM\Column(type="string", length=64, nullable=false) */
    protected $carreerRegExp;

    /** @ORM\Column(type="string", length=64, nullable=false) */
    protected $carreerName;

    /** @ORM\Column(type="string", length=64, nullable=false) */
    protected $transCode;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TransactionMode", cascade={"persist"})
     */
    protected $mode;
    
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TransactionStep", mappedBy="trans", cascade={"persist", "remove"})
     */
    protected $steps;
    
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TransactionError", mappedBy="trans", cascade={"persist", "remove"})
     */
    protected $errors;

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!method_exists(getTranslationEntityClass(), $method)) {
            $method = 'get'.ucfirst($method);
        }
        return $this->proxyCurrentLocaleTranslation($method, $args);
    }
    
     /**
     * Constructor
     */
    public function __construct()
    {
        $this->steps = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCarreerName($carreer){
        $this->carreerName = $carreer;
        return $this;
    }
    
    public function getCarreerName(){
        return $this->carreerName;
    }
    
    public function setCarreerRegExp($carreerRegExp){
        $this->carreerRegExp = $carreerRegExp;        
        return $this;
    }
    
    public function getCarreerRegExp(){
        return $this->carreerRegExp;
    }
    
    public function setTransCode($transCode){
        $this->transCode = $transCode;
        return $this;
    }
    
    public function getTransCode(){
        return $this->transCode;
    }

    public function setRecipientNameRegExp($recipientNameRegExp){
        $this->recipientNameRegExp = $recipientNameRegExp;
        return $this;
    }
    
    public function getRecipientNameRegExp(){
        return $this->recipientNameRegExp;
    }
    
    public function setOperationDescription($operationDescription){
        $this->operationDescription = $operationDescription;
        return $this;
    }
    
    public function getOperationDescription(){
        return $this->operationDescription;
    }
    
    public function setMode(\App\Entity\TransactionMode $mode = null)
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode()
    {
        return $this->mode;
    }
    
    public function addStep(\App\Entity\TransactionStep $step = null)
    {
        $this->steps[] = $step;
        $step->setTrans($this);
        
        return $this;
    }
    
    public function removeStep(\App\Entity\TransactionStep $step)
    {
        $this->steps->removeElement($step);

        return $this;
    }

    public function getSteps()
    {
        return $this->steps;
    }
    
    public function addError(\App\Entity\TransactionError $error = null)
    {
        $this->errors[] = $error;
        $error->setTrans($this);
        
        return $this;
    }
    
    public function removeError(\App\Entity\TransactionError $error)
    {
        $this->errors->removeElement($error);

        return $this;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}