<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\MessageBundle\Model\ParticipantInterface;
use FOS\UserBundle\Model\User as BaseUser;
use App\Exception\DatiException;

 /**
 * @ORM\Entity
 * @ORM\Table(name="dati_user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends BaseUser implements ParticipantInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
   
    /** @ORM\Column(type="string", nullable=true) */
    protected $credit;
   
    /** @ORM\Column(type="integer", nullable=true) */
    protected $thirdPartieId;
    
    /** @ORM\Column(type="integer", nullable=true) */
    protected $lastCallId;
    
    /** @ORM\Column(type="integer", nullable=true) */
    protected $lastOrderId;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $callBalanceReference;
    
    /** @ORM\Column(type="string", nullable=true, unique=true) */
    protected $securityToken;
    
    
    /** @ORM\Column(type="datetime", nullable=true) */
    protected $securityTokenRequestedAt;

    /** @ORM\Column(name="sip_password", type="string", nullable=true) */
    protected $siPwd; //sip password for call purposes
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $passwordResetToken; 
    
     public function __construct()
    {
        parent::__construct();
        // your own logic
    }    
    
    public function getSiPwd(): ?string 
    {
        return $this->siPwd;
    }
    
    public function setSiPwd(string $pwd): void 
    {
        $this->siPwd = $pwd;
        return;
    }
    
    public function getCallBalanceReference(): ?string
    {
        return ($this->callBalanceReference === null) ? "0" : $this->callBalanceReference;
    }

    public function setCallBalanceReference(string $callBalanceReference): void
    {   
        if(preg_match("/^(\-?\d+(\.\d+)?)$/", $callBalanceReference)){
            $this->callBalanceReference = $callBalanceReference;
            return;
        }
        
        throw new DatiException("BAD_CREDIT_VALUE_GIVEN");     
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }
    
    public function setPasswordResetToken(string $token): void
    {   
        $this->passwordResetToken = $token;        
        
        return;
    }
    
    public function getSecurityToken(): ?string
    {
        return $this->securityToken;
    }

    public function setSecurityToken(string $token): void
    {   
        $this->securityToken = $token;
        $this->securityTokenRequestedAt = new \DateTime();
        
        return;
    }

    public function getSecurityTokenRequestedAt()
    {
        return $this->securityTokenRequestedAt;
    }
    
    public function getThirdPartieId(): ?int
    {
        return $this->thirdPartieId;
    }

    public function setLastOrderId(int $value): void
    {  
        $this->lastOrderId = $value;   
        return;
    }
    
    public function getLastOrderId(): ?int
    {
        return $this->lastOrderId;
    }
    
    
    public function setLastCallId(int $value): void
    {  
        $this->lastCallId = $value;   
        return;
    }
    
    public function getLastCallId(): ?int
    {
        return $this->lastCallId;
    }

    public function setThirdPartieId(int $value): void
    {  
        $this->thirdPartieId = $value;   
        return;
    }
    
    public function getCredit(): ?string
    {
        return ($this->credit === null) ? "0" : $this->credit;
    }

    public function setCredit(string $credit): void
    {   
        if(preg_match("/^(\-?\d+(\.\d+)?)$/", $credit)){
            $this->credit = $credit;
            return;
        }
        
        throw new DatiException("BAD_CREDIT_VALUE_GIVEN");     
    }
    public function addCredit(string $credit): void
    {        
        if(preg_match("/^(\-?\d+(\.\d+)?)$/", $credit)){
            $oldCredit = (double)($this->credit);
            $newCredit = (double)($credit);
            $credit = $oldCredit + $newCredit;
            $this->credit = "".$credit;
            return;
        }
        
        echo "bad credit value given. value: $credit. add operation aborted";
        throw new DatiException("BAD_CREDIT_VALUE_GIVEN");        
    }
    
    public function removeCredit(string $credit): void
    {       
        $oldCredit = (double)($this->credit);
        $newCredit = (double)($credit);
        
        if(preg_match("/^(\-?\d+(\.\d+)?)$/", $newCredit) && ($newCredit <= $oldCredit)){
            $credit = $oldCredit - $newCredit;
            $this->credit = "".$credit;
            return;
        }
        
        echo "bad credit value given. value: $credit. decrease operation aborted";
        throw new DatiException("BAD_CREDIT_VALUE_GIVEN");        
    }
}