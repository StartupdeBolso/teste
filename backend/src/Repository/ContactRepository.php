<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findByCampaign(Campaign $campaign): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByCampaign(Campaign $campaign): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
