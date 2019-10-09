<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Environment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

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
        $environment = new Environment();
        $environment->setName('foo');
        $environment->setLocation('path/to/foo');
        $environment->setActive(true);
        $environment->setType('magento2');
        $manager->persist($environment);

        $environment = new Environment();
        $environment->setName('bar');
        $environment->setLocation('path/to/bar');
        $environment->setActive(false);
        $environment->setType('magento2');
        $manager->persist($environment);

        $manager->flush();
    }
}
