<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware;

use App\Exception\InvalidConfigurationException;
use App\Service\ApplicationContext;
use App\Service\Middleware\Database;
use App\Tests\TestEnvironmentTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 *
 * @covers \App\Service\Middleware\Database
 */
final class DatabaseTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    /**
     * @dataProvider provideDatabaseConfigurations
     */
    public function testItRetrievesDatabaseDetails(string $databaseType, string $databaseVersion): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configuration = $environment->getLocation().'/var/docker/docker-compose.yml';
        $content = str_replace(
            [
                '# <== DATABASE PLACEHOLDER ==>',
                '${DOCKER_DATABASE_IMAGE}',
            ],
            [
                file_get_contents(__DIR__."/../../../src/Resources/docker-fragments/{$databaseType}.yml"),
                "{$databaseType}:{$databaseVersion}",
            ],
            file_get_contents($configuration)
        );
        file_put_contents($configuration, $content);

        $applicationContext
            ->getEnvironmentConfiguration()
            ->shouldBeCalled()
            ->willReturn(Yaml::parse($content))
        ;

        $database = new Database($applicationContext->reveal());
        static::assertSame($databaseType, $database->getDatabaseType());
        static::assertSame($databaseVersion, $database->getDatabaseVersion());

        if ($databaseType === 'mariadb' || $databaseType === 'mysql') {
            static::assertSame('root', $database->getDatabaseUsername());
        } elseif ($databaseType === 'postgres') {
            static::assertSame('postgres', $database->getDatabaseUsername());
        }

        static::assertSame('YourPwdShouldBeLongAndSecure', $database->getDatabasePassword());
    }

    /**
     * @dataProvider provideDatabaseConfigurations
     */
    public function testItDetectsInvalidConfigurationWhileRetrievingDatabaseVersion(string $databaseType): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configuration = $environment->getLocation().'/var/docker/docker-compose.yml';
        $content = str_replace(
            ['# <== DATABASE PLACEHOLDER ==>'],
            [file_get_contents(__DIR__."/../../../src/Resources/docker-fragments/{$databaseType}.yml")],
            file_get_contents($configuration)
        );
        file_put_contents($configuration, $content);

        $applicationContext
            ->getEnvironmentConfiguration()
            ->shouldBeCalled()
            ->willReturn(Yaml::parse($content))
        ;

        $database = new Database($applicationContext->reveal());
        self::expectException(InvalidConfigurationException::class);
        $database->getDatabaseVersion();
    }

    /**
     * @dataProvider provideDatabaseConfigurations
     */
    public function testItDetectsInvalidConfigurationWhileRetrievingDatabaseType(string $databaseType): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configuration = $environment->getLocation().'/var/docker/docker-compose.yml';
        $content = str_replace(
            ['# <== DATABASE PLACEHOLDER ==>'],
            [file_get_contents(__DIR__."/../../../src/Resources/docker-fragments/{$databaseType}.yml")],
            file_get_contents($configuration)
        );
        file_put_contents($configuration, $content);

        $applicationContext
            ->getEnvironmentConfiguration()
            ->shouldBeCalled()
            ->willReturn(Yaml::parse($content))
        ;

        $database = new Database($applicationContext->reveal());
        self::expectException(InvalidConfigurationException::class);
        $database->getDatabaseType();
    }

    /**
     * @dataProvider provideDatabaseConfigurations
     */
    public function testItDetectsInvalidConfigurationWhileRetrievingDatabaseUsername(string $databaseType): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configuration = $environment->getLocation().'/var/docker/docker-compose.yml';
        $content = str_replace(
            ['# <== DATABASE PLACEHOLDER ==>'],
            [file_get_contents(__DIR__."/../../../src/Resources/docker-fragments/{$databaseType}.yml")],
            file_get_contents($configuration)
        );
        file_put_contents($configuration, $content);

        $applicationContext
            ->getEnvironmentConfiguration()
            ->shouldBeCalled()
            ->willReturn(Yaml::parse($content))
        ;

        $database = new Database($applicationContext->reveal());
        self::expectException(InvalidConfigurationException::class);
        $database->getDatabaseUsername();

        self::expectException(InvalidConfigurationException::class);
        $database->getDatabasePassword();
    }

    /**
     * @dataProvider provideDatabaseConfigurations
     */
    public function testItDetectsInvalidConfigurationWhileRetrievingDatabasePassword(string $databaseType): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configuration = $environment->getLocation().'/var/docker/docker-compose.yml';
        $content = str_replace(
            ['# <== DATABASE PLACEHOLDER ==>'],
            [file_get_contents(__DIR__."/../../../src/Resources/docker-fragments/{$databaseType}.yml")],
            file_get_contents($configuration)
        );
        file_put_contents($configuration, $content);

        $applicationContext
            ->getEnvironmentConfiguration()
            ->shouldBeCalled()
            ->willReturn(Yaml::parse($content))
        ;

        $database = new Database($applicationContext->reveal());
        self::expectException(InvalidConfigurationException::class);
        $database->getDatabasePassword();
    }

    public function provideDatabaseConfigurations(): \Iterator
    {
        yield 'mariadb 10.7' => ['mariadb', '10.7'];
        yield 'mariadb 10.6' => ['mariadb', '10.6'];
        yield 'mysql 8.0' => ['mysql', '8.0'];
        yield 'mysql 5.7' => ['mysql', '5.7'];
        yield 'postgres 14-alpine' => ['postgres', '14-alpine'];
        yield 'postgres 13-alpine' => ['postgres', '13-alpine'];
    }
}
