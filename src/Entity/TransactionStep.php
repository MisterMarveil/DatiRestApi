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
 * @Table(name="trans_step")
 * @ORM\Entity(repositoryClass="App\Repository\TransactionStepRepository")
 */
class TransactionStep implements TranslatableInterface
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
    
    /** @ORM\Column(type="integer", nullable=false) */
    protected $position;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TransDictionnary", inversedBy="steps", cascade={"persist"})
     */
    protected $trans;
    
    
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
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setPosition($value){
        $this->position = $value;
        return $this;
    }
    
    public function getPosition(){
        return $this->position;
    }
    
    public function setTrans(\App\Entity\TransDictionnary $value){
        $this->trans = $value;
        return $this;
    }
    
    public function getTrans(){
        return $this->trans;
    }
}