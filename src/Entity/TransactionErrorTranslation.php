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
use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;


/**
 * @Entity 
 */
class TransactionErrorTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    
    
     /**
     * @ORM\Column(type="string", length=255)
     */
    protected $description;
     
    public function __toString(){
		return $this->description."";
	}
	
    public function getId(){
        return $this->id;
    }
    
    public function setDescription($value){
        $this->description = $value;
        return $this;
    }
    
    public function getDescription(){
        return $this->description;
    }
}