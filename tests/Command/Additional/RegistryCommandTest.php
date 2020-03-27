<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegistryCommand;
use App\Environment\EnvironmentCollection;
use App\Environment\EnvironmentEntity;
use App\Tests\AbstractCommandWebTestCase;
use Generator;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegistryCommand
 */
final class RegistryCommandTest extends AbstractCommandWebTestCase
{
    public function testItPrintsNoteMessageWithoutEnvironments(): void
    {
        $this->database->getAllEnvironments()->shouldBeCalledOnce()->willReturn(new EnvironmentCollection());

        $commandTester = new CommandTester($this->getCommand(RegistryCommand::class));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[NOTE] There is no registered environment at the moment.', $display);
    }

    /**
     * @dataProvider provideEnvironmentList
     */
    public function testItPrintsEnvironmentDetailsInTable(EnvironmentEntity $environment): void
    {
        $collection = new EnvironmentCollection([$environment]);
        $this->database->getAllEnvironments()->shouldBeCalledOnce()->willReturn($collection);

        $commandTester = new CommandTester($this->getCommand(RegistryCommand::class));
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();

        /** @var string $name */
        $name = $environment->getName();
        static::assertStringContainsString($name, $display);

        /** @var string $location */
        $location = $environment->getLocation();
        static::assertStringContainsString($location, $display);

        /** @var string $type */
        $type = $environment->getType();
        static::assertStringContainsString($type, $display);

        static::assertStringContainsString($environment->getDomains() ?? '', $display);
    }

    public function provideEnvironmentList(): Generator
    {
        $envinonmentWithoutDomains = new EnvironmentEntity(
            'POC',
            '~/Sites/poc-symfony',
            EnvironmentEntity::TYPE_SYMFONY
        );
        yield [$envinonmentWithoutDomains];

        $envinonmentWithDomains = new EnvironmentEntity(
            'Origami',
            '~/Sites/origami',
            EnvironmentEntity::TYPE_SYMFONY,
            'origami.localhost',
            true
        );
        yield [$envinonmentWithDomains];
    }
}
