<?php

declare(strict_types=1);

namespace App\Tests;

use App\Environment\EnvironmentEntity;
use Generator;
use Symfony\Component\Finder\Finder;

trait TestConfigurationTrait
{
    public function provideMultipleInstallContexts(): Generator
    {
        yield [EnvironmentEntity::TYPE_MAGENTO2, 'www.magento.localhost magento.localhost'];
        yield [EnvironmentEntity::TYPE_MAGENTO2, ''];

        yield [EnvironmentEntity::TYPE_SYLIUS, 'www.sylius.localhost sylius.localhost'];
        yield [EnvironmentEntity::TYPE_SYLIUS, ''];

        yield [EnvironmentEntity::TYPE_SYMFONY, 'www.symfony.localhost symfony.localhost'];
        yield [EnvironmentEntity::TYPE_SYMFONY, ''];
    }

    protected function assertConfigurationIsInstalled(string $type, string $destination, string $phpVersion): void
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__."/../src/Resources/{$type}");

        foreach ($finder as $file) {
            $pathname = $file->getPathname();
            $relativePath = substr($pathname, (strpos($pathname, $type) ?: 0) + \strlen($type) + 1);

            static::assertFileEquals($file->getPathname(), $destination.'/'.$relativePath);
        }

        /** @var string $projectConfiguration */
        $projectConfiguration = file_get_contents(sprintf('%s/.env', $destination));
        static::assertStringContainsString($phpVersion, $projectConfiguration);
    }
}
