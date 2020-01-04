<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Environment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * @codeCoverageIgnore
 */
class EnvironmentFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $environment = new Environment('foo', 'path/to/foo', 'symfony', null, true);
        $manager->persist($environment);

        $environment = new Environment('bar', 'path/to/bar', 'magento2', null, false);
        $manager->persist($environment);

        $manager->flush();
    }
}
