<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegistryCommand;
use App\Entity\Environment;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\TestCustomCommandsTrait;
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
    use TestCustomCommandsTrait;

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
        $this->processProxy = $this->prophesize(ProcessProxy::class);
    }

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
