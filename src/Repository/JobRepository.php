<?php

 /*
 * This file is part of the DATICASH PROJECT
 *
 * (c) ewoniewonimerveil@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Repository;

use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    public function countJobs(): int
    {
        return (int) $this->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getOrderedByDate(): array
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.updatedAt', 'DESC')
            ->addOrderBy('j.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
   
    public function findLatest(int $count): array
    {
        return $this->createQueryBuilder('j')
            ->addOrderBy('j.createdAt', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getForUser(int $userId): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.user', 'u')            
            ->where('u.id = :user_id')   
            //->andWhere('j.status != :completed_status')
            ->addOrderBy('j.createdAt', 'DESC')
            ->setParameter("user_id", $userId)
            //->setParameter("completed_status", "COMPLETED")
            ->getQuery()
            ->getResult()
        ;
    }

    public function findEarlier(int $count): array
    {
        return $this->createQueryBuilder('j')
            ->addOrderBy('j.createdAt', 'ASC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult()
        ;
    }
}