<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegistryCommand;
use App\Entity\Environment;
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
        $this->systemManager->getAllEnvironments()->shouldBeCalledOnce()->willReturn([]);

        $command = new RegistryCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[NOTE] There is no registered environment at the moment.', $display);
    }

    /**
     * @dataProvider provideEnvironmentList
     */
    public function testItPrintsEnvironmentDetailsInTable(Environment $environment): void
    {
        $this->systemManager->getAllEnvironments()->shouldBeCalledOnce()->willReturn([$environment]);

        $command = new RegistryCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
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
        $envinonmentWithoutDomains = new Environment(
            'POC',
            '~/Sites/poc-symfony',
            Environment::TYPE_SYMFONY
        );
        yield [$envinonmentWithoutDomains];

        $envinonmentWithDomains = new Environment(
            'Origami',
            '~/Sites/origami',
            Environment::TYPE_SYMFONY,
            'origami.localhost',
            true
        );
        yield [$envinonmentWithDomains];
    }
}
