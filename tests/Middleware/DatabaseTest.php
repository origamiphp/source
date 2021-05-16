<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Environment\EnvironmentCollection;
use App\Environment\EnvironmentEntity;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Database;
use App\Tests\TestEnvironmentTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Middleware\Database
 */
final class DatabaseTest extends TestCase
{
    use TestEnvironmentTrait;

    /**
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItCreatesTheDatabaseFile(): void
    {
        static::assertFileDoesNotExist($this->getDatabasePath());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        new Database($fakeVariables);

        static::assertFileExists($this->getDatabasePath());
    }

    /**
     * @throws InvalidEnvironmentException
     */
    public function testItThrowsAnExceptionIfTheDatabaseIsNotCreated(): void
    {
        $this->expectException(FilesystemException::class);

        $fakeVariables = FakeVariables::fromArray(['HOME' => '/fake/location']);
        new Database($fakeVariables);
    }

    /**
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItRetrievesTheEnvironmentList(): void
    {
        file_put_contents($this->getDatabasePath(), $this->getFakeDatabaseContent());
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
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItRetrievesTheActiveEnvironment(): void
    {
        file_put_contents($this->getDatabasePath(), $this->getFakeDatabaseContent());
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
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItRetrievesAnEnvironmentByName(): void
    {
        file_put_contents($this->getDatabasePath(), $this->getFakeDatabaseContent());
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
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItRetrievesAnEnvironmentByLocation(): void
    {
        file_put_contents($this->getDatabasePath(), $this->getFakeDatabaseContent());
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
        file_put_contents($this->getDatabasePath(), $this->getFakeDatabaseContent());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        $environment = new EnvironmentEntity(
            'fake-environment-3',
            '/fake/location/3',
            'sylius',
            'sylius.test'
        );

        $database->add($environment);
        static::assertStringEqualsFile($this->getDatabasePath(), $this->getFakeDatabaseContent());

        $database->save();
        static::assertStringEqualsFile($this->getDatabasePath(), $this->getFakeDatabaseContentAfterAddition());
    }

    /**
     * @throws InvalidEnvironmentException
     * @throws FilesystemException
     */
    public function testItRemovesAnEnvironment(): void
    {
        file_put_contents($this->getDatabasePath(), $this->getFakeDatabaseContent());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        $environment = new EnvironmentEntity(
            'fake-environment-1',
            '/fake/location/1',
            'magento',
            'magento.test'
        );

        $database->remove($environment);
        static::assertStringEqualsFile($this->getDatabasePath(), $this->getFakeDatabaseContent());

        $database->save();
        static::assertStringEqualsFile($this->getDatabasePath(), $this->getFakeDatabaseContentAfterDeletion());
    }

    /**
     * Retrieves the path to the test database.
     */
    private function getDatabasePath(): string
    {
        return $this->location.\DIRECTORY_SEPARATOR.Database::DATABASE_FILENAME;
    }

    /**
     * Retrieves the fake database content used to perform the tests.
     */
    private function getFakeDatabaseContent(): string
    {
        return <<<'EOT'
[
   {
      "name":"fake-environment-1",
      "location":"\/fake\/location\/1",
      "type":"magento",
      "domains":"magento.test",
      "active":false
   },
   {
      "name":"fake-environment-2",
      "location":"\/fake\/location\/2",
      "type":"symfony",
      "domains":"symfony.test",
      "active":true
   }
]
EOT;
    }

    /**
     * Retrieves the fake database content once the addition test has been performed.
     */
    private function getFakeDatabaseContentAfterAddition(): string
    {
        return '[{"name":"fake-environment-1","location":"\/fake\/location\/1","type":"magento","domains":"magento.test","active":false},{"name":"fake-environment-2","location":"\/fake\/location\/2","type":"symfony","domains":"symfony.test","active":true},{"name":"fake-environment-3","location":"\/fake\/location\/3","type":"sylius","domains":"sylius.test","active":false}]';
    }

    /**
     * Retrieves the fake database content once the deletion test has been performed.
     */
    private function getFakeDatabaseContentAfterDeletion(): string
    {
        return '[{"name":"fake-environment-2","location":"\/fake\/location\/2","type":"symfony","domains":"symfony.test","active":true}]';
    }
}
