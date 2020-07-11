<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UninstallCommand;
use App\Environment\Configuration\ConfigurationUninstaller;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Tests\TestCommandTrait;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\UninstallCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class UninstallCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestFakeEnvironmentTrait;

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItUninstallsTheCurrentEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->deactivate();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $uninstaller = $this->prophesize(ConfigurationUninstaller::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->removeServices()->shouldBeCalledOnce()->willReturn(true);
        $eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();
        $uninstaller->uninstall($environment)->shouldBeCalledOnce();

        $command = new UninstallCommand($currentContext->reveal(), $dockerCompose->reveal(), $uninstaller->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully uninstalled.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItDoesNotUninstallARunningEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->activate();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $uninstaller = $this->prophesize(ConfigurationUninstaller::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->removeServices()->shouldNotBeCalled();
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $uninstaller->uninstall($environment)->shouldNotBeCalled();

        $command = new UninstallCommand($currentContext->reveal(), $dockerCompose->reveal(), $uninstaller->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to uninstall a running environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        $currentContext = $this->prophesize(CurrentContext::class);
        $dockerCompose = $this->prophesize(DockerCompose::class);
        $uninstaller = $this->prophesize(ConfigurationUninstaller::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $currentContext->getEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce()->willReturn($environment);
        $dockerCompose->removeServices()->shouldBeCalledOnce()->willReturn(false);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
        $uninstaller->uninstall($environment)->shouldNotBeCalled();

        $command = new UninstallCommand($currentContext->reveal(), $dockerCompose->reveal(), $uninstaller->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while removing the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
