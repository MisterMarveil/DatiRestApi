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
 * @Table(name="job")
 * @ORM\Entity(repositoryClass="App\Repository\JobRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Job
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /** @ORM\Column(type="datetime", nullable=true) */
    protected $lastConsultedAt;
    
    /** @ORM\Column(type="integer", nullable=true) */
    protected $step;
    
    /** @ORM\Column(type="string", length=32, nullable=true) */
    protected $ussdHandlerNumber;
    
    
    /** @ORM\Column(type="text", nullable=true) */
    protected $stepDescription;
    
     /** @ORM\Column(type="string", options={"default": "WAITING"}, length=32, nullable=true) */
    protected $status;  //WAITING|PROCESSING|COMPLETED

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $lastRequestedAt;
    
    /** @ORM\Column(type="datetime", nullable=false) */
    protected $createdAt;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected $updatedAt;
    
    /** @ORM\Column(type="text", nullable=true) */
    protected $response;

    /** @ORM\Column(type="text", nullable=true) */
    protected $request;  
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TransDictionnary", cascade={"persist"})
     */
    protected $trans;
    
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false) 
     * 
     */
    protected $user;

    /**
    * @ORM\PrePersist
    */
    public function persistDate(){
        $this->createdAt = new \DateTime();
    }

    /**
    * @ORM\PreUpdate
    */
    public function updateDate(){
        $this->updatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function setStep($step){
        $this->step = $step;
        return $this;
    }
    
    public function getStep(){
        return $this->step;
    }

    public function setUssdHandlerNumber($number){
        $this->ussdHandlerNumber = $number;
        return $this;
    }
    
    public function getUssdHandlerNumber(){
        return $this->ussdHandlerNumber;
    }

    public function setRequest($request){
        $this->request = $request;
        return $this;
    }
    
    public function getRequest(){
        return $this->request;
    }
    
     public function setStatus($status){
        $this->status = $status;
        return $this;
    }
    
    public function getStatus(){
        return $this->status;
    }
    
    
    public function setResponse($response){
        $this->response = $response;        
        return $this;
    }
    
    public function getResponse(){
        return $this->response;
    }
    
    public function setStepDescription($response){
        $this->stepDescription = $response;        
        return $this;
    }
    
    public function getStepDescription(){
        return $this->stepDescription;
    }
    
    public function setUpdatedAt($updatedAt){
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    public function getUpdatedAt(){
        return $this->updatedAt;
    }

    public function setCreatedAt($createdAt){
        $this->createdAt = $createdAt;
        return $this;
    }
    
    public function getCreatedAt(){
        return $this->createdAt;
    }
    
    public function setLastRequestedAt($at){
        $this->lastRequestedAt = $at;
        return $this;
    }
    
    public function getLastRequestedAt(){
        return $this->lastRequestedAt;
    }

    public function setLastConsultedAt($at){
        $this->lastConsultedAt = $at;
        return $this;
    }
    
    public function getLastConsultedAt(){
        return $this->lastConsultedAt;
    }
    
    public function setTrans(\App\Entity\TransDictionnary $trans = null)
    {
        $this->trans = $trans;

        return $this;
    }

    public function getTrans()
    {
        return $this->trans;
    }
    
    public function setUser(\App\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }
}