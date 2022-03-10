<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Exception\InvalidEnvironmentException;
use App\Service\ApplicationData;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\EnvironmentCollection;
use App\ValueObject\EnvironmentEntity;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @internal
 *
 * @covers \App\Service\ApplicationData
 */
final class ApplicationDataTest extends TestCase
{
    use TestEnvironmentTrait;

    public function testItCreatesTheDatabaseFile(): void
    {
        static::assertFileDoesNotExist($this->getDatabasePath());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        new ApplicationData($fakeVariables);

        static::assertFileExists($this->getDatabasePath());
    }

    /**
     * @deprecated
     */
    public function testItMovesTheOlderDatabaseFile(): void
    {
        touch($this->getOlderDatabasePath());

        static::assertFileExists($this->getOlderDatabasePath());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        new ApplicationData($fakeVariables);

        static::assertFileDoesNotExist($this->getOlderDatabasePath());
        static::assertFileExists($this->getDatabasePath());
    }

    public function testItThrowsAnExceptionIfTheDatabaseIsNotCreated(): void
    {
        $this->expectException(IOException::class);

        $fakeVariables = FakeVariables::fromArray(['HOME' => '/fake/location']);
        new ApplicationData($fakeVariables);
    }

    public function testItRetrievesTheEnvironmentList(): void
    {
        mkdir($this->getDatabaseFolder(), 0777, true);
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new ApplicationData($fakeVariables);

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

    public function testItRetrievesTheActiveEnvironment(): void
    {
        mkdir($this->getDatabaseFolder(), 0777, true);
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new ApplicationData($fakeVariables);

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
        $database = new ApplicationData($fakeVariables);

        static::assertNull($database->getActiveEnvironment());
    }

    public function testItRetrievesAnEnvironmentByName(): void
    {
        mkdir($this->getDatabaseFolder(), 0777, true);
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new ApplicationData($fakeVariables);

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

    public function testItRetrievesAnEnvironmentByLocation(): void
    {
        mkdir($this->getDatabaseFolder(), 0777, true);
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new ApplicationData($fakeVariables);

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
     */
    public function testItAddsAnEnvironment(): void
    {
        mkdir($this->getDatabaseFolder(), 0777, true);
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new ApplicationData($fakeVariables);

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

    public function testItRemovesAnEnvironment(): void
    {
        mkdir($this->getDatabaseFolder(), 0777, true);
        copy($this->getFakeDatabasePath(), $this->getDatabasePath());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new ApplicationData($fakeVariables);

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
        return $this->location.\DIRECTORY_SEPARATOR.ApplicationData::DATA_FILE_PATH;
    }

    /**
     * Retrieves the older path to the test database.
     *
     * @deprecated
     */
    private function getOlderDatabasePath(): string
    {
        return $this->location.\DIRECTORY_SEPARATOR.ApplicationData::OLDER_DATA_FILENAME;
    }

    /**
     * Retrieves the folder to the test database.
     */
    private function getDatabaseFolder(): string
    {
        return $this->location.\DIRECTORY_SEPARATOR.ApplicationData::DATA_FILE_FOLDER;
    }

    /**
     * Retrieves the path to the fixture database without changes.
     */
    private function getFakeDatabasePath(): string
    {
        return __DIR__.'/../Fixtures/databases/fake_database.json';
    }

    /**
     * Retrieves the path to the fixture database with an addition.
     */
    private function getFakeDatabaseAfterAdditionPath(): string
    {
        return __DIR__.'/../Fixtures/databases/fake_database_after_addition.json';
    }

    /**
     * Retrieves the path to the fixture database with a deletion.
     */
    private function getFakeDatabaseAfterDeletionPath(): string
    {
        return __DIR__.'/../Fixtures/databases/fake_database_after_deletion.json';
    }
}
