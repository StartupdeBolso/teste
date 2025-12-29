<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\Dispatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dispatch>
 */
class DispatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dispatch::class);
    }

    public function findByCampaign(Campaign $campaign): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingDispatches(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.status = :status')
            ->setParameter('status', 'pending')
            ->setMaxResults($limit)
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(Campaign $campaign, string $status): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.campaign = :campaign')
            ->andWhere('d.status = :status')
            ->setParameter('campaign', $campaign)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
