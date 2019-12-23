<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegistryCommand;
use App\Entity\Environment;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\Command\CustomCommandsTrait;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegistryCommand
 */
final class RegistryCommandTest extends WebTestCase
{
    use CustomCommandsTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->systemManager = $this->prophesize(SystemManager::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testItPrintsNoteMessageWithoutEnvironments(): void
    {
        $this->systemManager->getAllEnvironments()->shouldBeCalledOnce()->willReturn([]);

        $command = new RegistryCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
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
            $this->eventDispatcher->reveal()
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

        static::assertStringContainsString($environment->getDomains() ?? 'N/A', $display);
    }

    public function provideEnvironmentList(): Generator
    {
        $envinonmentWithoutDomains = new Environment();
        $envinonmentWithoutDomains->setName('POC');
        $envinonmentWithoutDomains->setLocation('~/Sites/poc-symfony');
        $envinonmentWithoutDomains->setType('symfony');
        $envinonmentWithoutDomains->setActive(false);
        yield [$envinonmentWithoutDomains];

        $envinonmentWithDomains = new Environment();
        $envinonmentWithDomains->setName('Origami');
        $envinonmentWithDomains->setLocation('~/Sites/origami');
        $envinonmentWithDomains->setType('symfony');
        $envinonmentWithDomains->setDomains('origami.localhost');
        $envinonmentWithDomains->setActive(true);
        yield [$envinonmentWithDomains];
    }
}
