<?php

namespace App\Repository;

use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithStats(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'COUNT(DISTINCT u.id) as userCount', 'COUNT(DISTINCT c.id) as campaignCount')
            ->leftJoin('o.users', 'u')
            ->leftJoin('o.campaigns', 'c')
            ->groupBy('o.id')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
