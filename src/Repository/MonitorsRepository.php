<?php

namespace App\Repository;

use App\Entity\Monitor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Monitors>
 *
 * @method Monitors|null find($id, $lockMode = null, $lockVersion = null)
 * @method Monitors|null findOneBy(array $criteria, array $orderBy = null)
 * @method Monitors[]    findAll()
 * @method Monitors[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MonitorsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Monitor::class);
    }

//    /**
//     * @return Monitors[] Returns an array of Monitors objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Monitors
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
