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
        yield 'Magento environment with domain' => ['magento2-project', EnvironmentEntity::TYPE_MAGENTO2, 'magento.localhost'];
        yield 'Magento environment without domain' => ['magento2-project', EnvironmentEntity::TYPE_MAGENTO2, ''];

        yield 'Sylius environment with domain' => ['sylius-project', EnvironmentEntity::TYPE_SYLIUS, 'sylius.localhost'];
        yield 'Sylius environment without domain' => ['sylius-project', EnvironmentEntity::TYPE_SYLIUS, ''];

        yield 'Symfony environment with domain' => ['symfony-project', EnvironmentEntity::TYPE_SYMFONY, 'symfony.localhost'];
        yield 'Symfony environment without domain' => ['symfony-project', EnvironmentEntity::TYPE_SYMFONY, ''];
    }

    protected function getFakeBlackfireCredentials(): array
    {
        return [
            'BLACKFIRE_SERVER_ID' => 'server_foo',
            'BLACKFIRE_SERVER_TOKEN' => 'server_bar',
            'BLACKFIRE_CLIENT_ID' => 'client_foo',
            'BLACKFIRE_CLIENT_TOKEN' => 'client_bar',
        ];
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

    protected function assertConfigurationContainsBlackfireCredentials(string $destination, array $credentials): void
    {
        /** @var string $projectConfiguration */
        $projectConfiguration = file_get_contents("{$destination}/.env");

        foreach ($credentials as $key => $value) {
            static::assertStringContainsString("{$key}={$value}\n", $projectConfiguration);
        }
    }
}
