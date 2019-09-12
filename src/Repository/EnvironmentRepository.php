<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Environment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method null|Environment find($id, $lockMode = null, $lockVersion = null)
 * @method null|Environment findOneBy(array $criteria, array $orderBy = null)
 * @method Environment[]    findAll()
 * @method Environment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnvironmentRepository extends ServiceEntityRepository
{
    /**
     * EnvironmentRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Environment::class);
    }
}
