<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware;

use App\Exception\DatabaseException;
use App\Service\Middleware\Binary\Docker;
use App\Service\Middleware\Database;
use App\Tests\TestEnvironmentTrait;
use Ergebnis\Environment\FakeVariables;
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
     * @dataProvider provideMysqlImages
     */
    public function testItTriggersMysqlDump(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $docker
            ->dumpMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($docker->reveal(), $systemVariables);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider provideMysqlImages
     */
    public function testItDetectsMysqlDumpFailure(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $docker
            ->dumpMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider providePostgresImages
     */
    public function testItTriggersPostgresDump(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $docker
            ->dumpPostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($docker->reveal(), $systemVariables);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider providePostgresImages
     */
    public function testItDetectsPostgresDumpFailure(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $docker
            ->dumpPostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->dump($backupFile);
    }

    public function testItDoesNotDumpDatabaseWithoutConfiguration(): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => '']);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider provideInvalidImages
     */
    public function testItDoesNotDumpDatabaseWithInvalidImages(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->dump($backupFile);
    }

    /**
     * @dataProvider provideMysqlImages
     */
    public function testItTriggersMysqlRestore(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $docker
            ->restoreMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($docker->reveal(), $systemVariables);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider provideMysqlImages
     */
    public function testItDetectsMysqlRestoreFailure(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $docker
            ->restoreMysqlDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider providePostgresImages
     */
    public function testItTriggersPostgresRestore(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $docker
            ->restorePostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        $database = new Database($docker->reveal(), $systemVariables);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider providePostgresImages
     */
    public function testItDetectsPostgresRestoreFailure(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $docker
            ->restorePostgresDatabase($backupFile)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider provideMysqlImages
     */
    public function testItDetectsMissingMysqlDump(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;

        $docker
            ->restoreMysqlDatabase()
            ->shouldNotBeCalled()
        ;

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->restore($backupFile);
    }

    public function testItDoesNotRestoreDatabaseWithoutConfiguration(): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => '']);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider provideInvalidImages
     */
    public function testItDoesNotRestoreDatabaseWithInvalidImages(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $backupFile = $this->location.'/'.Database::DEFAULT_BACKUP_FILENAME;
        touch($backupFile);

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->restore($backupFile);
    }

    /**
     * @dataProvider provideMysqlImages
     */
    public function testItReplacesPlaceholder(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.'/var/docker';

        static::assertStringContainsString(
            '# <== DATABASE PLACEHOLDER ==>',
            file_get_contents("{$destination}/docker-compose.yml")
        );

        $database = new Database($docker->reveal(), $systemVariables);
        $database->replaceDatabasePlaceholder($dockerImage, $destination);

        static::assertStringNotContainsString(
            '# <== DATABASE PLACEHOLDER ==>',
            file_get_contents("{$destination}/docker-compose.yml")
        );
    }

    /**
     * @dataProvider provideInvalidImages
     */
    public function testItDoesNotReplacePlaceholderWithInvalidImages(string $dockerImage): void
    {
        $docker = $this->prophesize(Docker::class);
        $systemVariables = FakeVariables::fromArray(['DOCKER_DATABASE_IMAGE' => $dockerImage]);

        $environment = $this->createEnvironment();
        $this->installEnvironmentConfiguration($environment);

        $destination = $this->location.'/var/docker';

        $this->expectException(DatabaseException::class);

        $database = new Database($docker->reveal(), $systemVariables);
        $database->replaceDatabasePlaceholder($dockerImage, $destination);
    }

    /**
     * @return \Iterator<string[]>
     */
    public function provideMysqlImages(): Iterator
    {
        yield 'MariaDB latest version' => ['mariadb:latest'];
        yield 'MariaDB specific version' => ['mariadb:10.5'];
        yield 'MySQL latest version' => ['mysql:latest'];
        yield 'MySQL specific version' => ['mysql:8.0'];
    }

    /**
     * @return \Iterator<string[]>
     */
    public function providePostgresImages(): Iterator
    {
        yield 'Postgres latest version' => ['postgres:latest'];
        yield 'Postgres specific version' => ['postgres:13-alpine'];
    }

    /**
     * @return \Iterator<string[]>
     */
    public function provideInvalidImages(): Iterator
    {
        yield 'unsupported database type' => ['foo:bar'];
        yield 'unrecognized Docker image' => ['foobar'];
    }
}
