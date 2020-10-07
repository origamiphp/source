<?php

declare(strict_types=1);

namespace App\Tests\Environment\Configuration;

use App\Environment\EnvironmentEntity;
use Generator;
use Symfony\Component\Finder\Finder;

trait TestConfigurationTrait
{
    public function provideMultipleInstallContexts(): Generator
    {
        yield 'Symfony environment with domain' => [
            'symfony-project',
            EnvironmentEntity::TYPE_SYMFONY,
            '8.0',
            '10.5.5',
            'symfony.localhost',
        ];

        yield 'Symfony environment without domain' => [
            'symfony-project',
            EnvironmentEntity::TYPE_SYMFONY,
            '8.0',
            '10.5.5',
            '',
        ];
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

    protected function assertConfigurationIsInstalled(string $type, string $destination, string $phpVersion, string $databaseVersion): void
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__."/../../../src/Resources/{$type}");

        foreach ($finder as $file) {
            $pathname = $file->getPathname();
            $relativePath = substr($pathname, (strpos($pathname, $type) ?: 0) + \strlen($type) + 1);

            static::assertFileEquals($file->getPathname(), $destination.'/'.$relativePath);
        }

        /** @var string $projectConfiguration */
        $projectConfiguration = file_get_contents(sprintf('%s/.env', $destination));
        static::assertStringContainsString($phpVersion, $projectConfiguration);
        static::assertStringContainsString($databaseVersion, $projectConfiguration);
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
