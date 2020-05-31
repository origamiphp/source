<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegistryCommand;
use App\Environment\EnvironmentCollection;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Database;
use App\Tests\Command\AbstractCommandWebTestCase;
use Generator;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegistryCommand
 */
final class RegistryCommandTest extends AbstractCommandWebTestCase
{
    /** @var ObjectProphecy */
    private $database;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->prophet->prophesize(Database::class);
    }

    public function testItPrintsNoteMessageWithoutEnvironments(): void
    {
        (new MethodProphecy($this->database, 'getAllEnvironments', []))
            ->shouldBeCalledOnce()
            ->willReturn(new EnvironmentCollection())
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[NOTE] There is no registered environment at the moment.', $display);
    }

    /**
     * @dataProvider provideEnvironmentList
     *
     * @throws InvalidEnvironmentException
     */
    public function testItPrintsEnvironmentDetailsInTable(EnvironmentEntity $environment): void
    {
        (new MethodProphecy($this->database, 'getAllEnvironments', []))
            ->shouldBeCalledOnce()
            ->willReturn(new EnvironmentCollection([$environment]))
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();

        $name = $environment->getName();
        static::assertStringContainsString($name, $display);

        $location = $environment->getLocation();
        static::assertStringContainsString($location, $display);

        $type = $environment->getType();
        static::assertStringContainsString($type, $display);

        if ($domains = $environment->getDomains()) {
            static::assertStringContainsString($domains, $display);
        }

        if ($environment->isActive()) {
            static::assertStringContainsString('Started', $display);
        } else {
            static::assertStringContainsString('Stopped', $display);
        }
    }

    public function provideEnvironmentList(): Generator
    {
        yield 'inactive environment without domains' => [
            new EnvironmentEntity(
                'POC',
                '~/Sites/poc-symfony',
                EnvironmentEntity::TYPE_SYMFONY,
                null,
                false
            ),
        ];

        yield 'active environment with domains' => [
            new EnvironmentEntity(
                'Origami',
                '~/Sites/origami',
                EnvironmentEntity::TYPE_SYMFONY,
                'origami.localhost',
                true
            ),
        ];
    }

    /**
     * Retrieves the \App\Command\Additional\RegistryCommand instance to use within the tests.
     */
    private function getCommand(): RegistryCommand
    {
        return new RegistryCommand(
            $this->database->reveal()
        );
    }
}
