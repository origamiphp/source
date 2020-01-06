<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\StartCommand;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\SystemManager;
use App\Tests\TestCustomCommandsTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\StartCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class StartCommandTest extends WebTestCase
{
    use TestCustomCommandsTrait;
    use TestFakeEnvironmentTrait;

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

    public function testItStartsTheEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->startServices()->shouldBeCalledOnce()->willReturn(true);
        $this->eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();

        $command = new StartCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Docker services successfully started.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotStartMultipleEnvironments(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->setActive(true);
        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $this->processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('');

        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->startServices()->shouldNotBeCalled();

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $command = new StartCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to start an environment when there is already a running one.', $display);
        static::assertSame(CommandExitCode::INVALID, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->startServices()->shouldBeCalledOnce()->willReturn(false);

        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $command = new StartCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while starting the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
