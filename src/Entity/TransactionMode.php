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
 * @Table(name="trans_mode")
 * @ORM\Entity(repositoryClass="App\Repository\TransactionModeRepository")
 */
class TransactionMode
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /** @ORM\Column(type="string", length=64, nullable=false, unique=true) */
    protected $name;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name){
        $this->name = $name;
        return $this;
    }
    
    public function getName(){
        return $this->name;
    }
}