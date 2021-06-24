<?php

declare(strict_types=1);

namespace App\Tests\Service\Middleware;

use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Service\Middleware\Database;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\EnvironmentCollection;
use App\ValueObject\EnvironmentEntity;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Service\Middleware\Database
 */
final class DatabaseTest extends TestCase
{
    use TestEnvironmentTrait;

    /**
     * @throws FilesystemException
     */
    public function testItCreatesTheDatabaseFile(): void
    {
        static::assertFileDoesNotExist($this->getDatabasePath());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        new Database($fakeVariables);

        static::assertFileExists($this->getDatabasePath());
    }

    public function testItThrowsAnExceptionIfTheDatabaseIsNotCreated(): void
    {
        $this->expectException(FilesystemException::class);

        $fakeVariables = FakeVariables::fromArray(['HOME' => '/fake/location']);
        new Database($fakeVariables);
    }

    /**
     * @throws FilesystemException
     */
    public function testItRetrievesTheEnvironmentList(): void
    {
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        $environments = $database->getAllEnvironments();

        static::assertEquals(
            new EnvironmentCollection(
                [
                    new EnvironmentEntity(
                        'fake-environment-1',
                        '/fake/location/1',
                        'magento',
                        'magento.test',
                        false
                    ),
                    new EnvironmentEntity(
                        'fake-environment-2',
                        '/fake/location/2',
                        'symfony',
                        'symfony.test',
                        true
                    ),
                ]
            ),
            $environments
        );
        static::assertCount(2, $environments);
    }

    /**
     * @throws FilesystemException
     */
    public function testItRetrievesTheActiveEnvironment(): void
    {
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertEquals(
            new EnvironmentEntity(
                'fake-environment-2',
                '/fake/location/2',
                'symfony',
                'symfony.test',
                true
            ),
            $database->getActiveEnvironment()
        );

        file_put_contents($this->getDatabasePath(), '[]');
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertNull($database->getActiveEnvironment());
    }

    /**
     * @throws FilesystemException
     */
    public function testItRetrievesAnEnvironmentByName(): void
    {
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertEquals(
            new EnvironmentEntity(
                'fake-environment-1',
                '/fake/location/1',
                'magento',
                'magento.test'
            ),
            $database->getEnvironmentByName('fake-environment-1')
        );

        static::assertNull($database->getEnvironmentByName('nothing'));
    }

    /**
     * @throws FilesystemException
     */
    public function testItRetrievesAnEnvironmentByLocation(): void
    {
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertEquals(
            new EnvironmentEntity(
                'fake-environment-1',
                '/fake/location/1',
                'magento',
                'magento.test'
            ),
            $database->getEnvironmentByLocation('/fake/location/1')
        );

        static::assertNull($database->getEnvironmentByLocation('nothing'));
    }

    /**
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItAddsAnEnvironment(): void
    {
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        $environment = new EnvironmentEntity(
            'fake-environment-3',
            '/fake/location/3',
            'sylius',
            'sylius.test'
        );

        $database->add($environment);
        static::assertJsonFileEqualsJsonFile($this->getFakeDatabasePath(), $this->getDatabasePath());

        $database->save();
        static::assertJsonFileEqualsJsonFile($this->getFakeDatabaseAfterAdditionPath(), $this->getDatabasePath());
    }

    /**
     * @throws FilesystemException
     */
    public function testItRemovesAnEnvironment(): void
    {
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        $environment = new EnvironmentEntity(
            'fake-environment-1',
            '/fake/location/1',
            'magento',
            'magento.test'
        );

        $database->remove($environment);
        static::assertJsonFileEqualsJsonFile($this->getFakeDatabasePath(), $this->getDatabasePath());

        $database->save();
        static::assertJsonFileEqualsJsonFile($this->getFakeDatabaseAfterDeletionPath(), $this->getDatabasePath());
    }

    /**
     * Retrieves the path to the test database.
     */
    private function getDatabasePath(): string
    {
        return $this->location.\DIRECTORY_SEPARATOR.Database::DATABASE_FILENAME;
    }

    /**
     * Retrieves the path to the fixture database without changes.
     */
    private function getFakeDatabasePath(): string
    {
        return __DIR__.'/../../Fixtures/databases/fake_database.json';
    }

    /**
     * Retrieves the path to the fixture database with an addition.
     */
    private function getFakeDatabaseAfterAdditionPath(): string
    {
        return __DIR__.'/../../Fixtures/databases/fake_database_after_addition.json';
    }

    /**
     * Retrieves the path to the fixture database with a deletion.
     */
    private function getFakeDatabaseAfterDeletionPath(): string
    {
        return __DIR__.'/../../Fixtures/databases/fake_database_after_deletion.json';
    }
}
