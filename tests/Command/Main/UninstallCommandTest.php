<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UninstallCommand;
use App\Entity\Environment;
use App\Helper\CommandExitCode;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\UninstallCommand
 */
final class UninstallCommandTest extends WebTestCase
{
    private $systemManager;
    private $validator;
    private $dockerCompose;
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->systemManager = $this->prophesize(SystemManager::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->dockerCompose = $this->prophesize(DockerCompose::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    }

    public function testItUninstallsTheCurrentEnvironment(): void
    {
        $environment = new Environment();
        $environment->setLocation('~/Sites/origami');
        $environment->setType('symfony');
        $environment->setActive(false);

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->removeServices()->shouldBeCalledOnce()->willReturn(true);
        $this->eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();
        $this->systemManager->uninstall($environment)->shouldBeCalledOnce();

        $command = new UninstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully uninstalled.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotUninstallARunningEnvironment(): void
    {
        $environment = new Environment();
        $environment->setLocation('~/Sites/origami');
        $environment->setType('symfony');
        $environment->setActive(true);

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->removeServices()->shouldNotBeCalled();
        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $this->systemManager->uninstall($environment)->shouldNotBeCalled();

        $command = new UninstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to uninstall a running environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = new Environment();
        $environment->setLocation('~/Sites/origami');
        $environment->setType('symfony');

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->removeServices()->shouldBeCalledOnce()->willReturn(false);
        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $this->systemManager->uninstall($environment)->shouldNotBeCalled();

        $command = new UninstallCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal()
        );

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while removing the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
