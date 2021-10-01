<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware;

use App\Exception\DatabaseException;
use App\Exception\FilesystemException;
use App\Exception\InvalidConfigurationException;
use App\Service\CurrentContext;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Database;
use App\Tests\TestEnvironmentTrait;
use Iterator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

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
     * @dataProvider provideMysqlServices
     */
    public function testItTriggersMysqlDump(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->dumpMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider provideMysqlServices
     */
    public function testItDetectsMysqlDumpFailure(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->dumpMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider providePostgresServices
     */
    public function testItTriggersPostgresDump(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->dumpPostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider providePostgresServices
     */
    public function testItDetectsPostgresDumpFailure(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->dumpPostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->dump($backupFile);
    }

    public function testItDoesNotDumpDatabaseWithoutConfiguration(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $this->expectException(FilesystemException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->dump($backupFile);
    }

    public function testItDoesNotDumpDatabaseWithInvalidImages(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', "  database\n    image: foobar", file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $this->expectException(InvalidConfigurationException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider provideMysqlServices
     */
    public function testItTriggersMysqlRestore(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->restoreMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider provideMysqlServices
     */
    public function testItDetectsMysqlRestoreFailure(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->restoreMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider providePostgresServices
     */
    public function testItTriggersPostgresRestore(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->restorePostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider providePostgresServices
     */
    public function testItDetectsPostgresRestoreFailure(string $databaseService): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', $databaseService, file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $docker
            ->restorePostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->restore($backupFile);
    }

    public function testItDetectsMissingDump(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $docker
            ->restoreMysqlDatabase()
            ->shouldNotBeCalled()
        ;

        $docker
            ->restorePostgresDatabase()
            ->shouldNotBeCalled()
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->restore($backupFile);
    }

    public function testItDoesNotRestoreDatabaseWithoutConfiguration(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $this->expectException(FilesystemException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->restore($backupFile);
    }

    public function testItDoesNotRestoreDatabaseWithInvalidImages(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $configurationPath = $this->location.$installDir.'/docker-compose.yml';

        file_put_contents(
            $configurationPath,
            str_replace('# <== DATABASE PLACEHOLDER ==>', "  database\n    image: foobar", file_get_contents($configurationPath))
        );

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $currentContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $this->expectException(InvalidConfigurationException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider provideDatabaseImages
     */
    public function testItReplacesPlaceholder(string $databaseImage): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.'/var/docker';

        static::assertStringContainsString(
            '# <== DATABASE PLACEHOLDER ==>',
            file_get_contents("{$destination}/docker-compose.yml")
        );

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->replaceDatabasePlaceholder($databaseImage, $destination);

        static::assertStringNotContainsString(
            '# <== DATABASE PLACEHOLDER ==>',
            file_get_contents("{$destination}/docker-compose.yml")
        );
    }

    public function testItDoesNotReplacePlaceholderWithInvalidImage(): void
    {
        $currentContext = $this->prophesize(CurrentContext::class);
        $docker = $this->prophesize(Docker::class);
        $installDir = '/var/docker';

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.'/var/docker';

        $this->expectException(InvalidConfigurationException::class);

        $database = new Database($currentContext->reveal(), $docker->reveal(), $installDir);
        $database->replaceDatabasePlaceholder('foobar', $destination);
    }

    /**
     * @return \Iterator<string[]>
     */
    public function provideMysqlServices(): Iterator
    {
        yield 'MariaDB latest version' => ["  database:\n    image: mariadb:latest"];
        yield 'MariaDB specific version' => ["  database:\n    image: mariadb:10.5"];

        yield 'MySQL latest version' => ["  database:\n    image: mysql:latest"];
        yield 'MySQL specific version' => ["  database:\n    image: mysql:8.0"];
    }

    /**
     * @return \Iterator<string[]>
     */
    public function providePostgresServices(): Iterator
    {
        yield 'Postgres latest version' => ["  database:\n    image: postgres:latest"];
        yield 'Postgres specific version' => ["  database:\n    image: postgres:13-alpine"];
    }

    /**
     * @return \Iterator<string[]>
     */
    public function provideDatabaseImages(): Iterator
    {
        yield 'MariaDB latest version' => ['mariadb:latest'];
        yield 'MariaDB specific version' => ['mariadb:10.5'];
        yield 'MySQL latest version' => ['mysql:latest'];
        yield 'MySQL specific version' => ['mysql:8.0'];
        yield 'Postgres latest version' => ['postgres:latest'];
        yield 'Postgres specific version' => ['postgres:13-alpine'];
    }
}
