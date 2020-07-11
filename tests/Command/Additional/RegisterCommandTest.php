<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegisterCommand;
use App\Environment\Configuration\ConfigurationInstaller;
use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\FilesystemException;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Tests\TestCommandTrait;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegisterCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class RegisterCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;

    /**
     * @throws FilesystemException
     */
    public function testItRegistersAnExternalEnvironmentWithDefaultName(): void
    {
        $environmentDetails = ['directory', '/fake/directory', EnvironmentEntity::TYPE_CUSTOM];

        $processProxy = $this->prophesize(ProcessProxy::class);
        $installer = $this->prophesize(ConfigurationInstaller::class);
        $environment = $this->prophesize(EnvironmentEntity::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('/fake/directory');
        $installer->install(...$environmentDetails)->shouldBeCalledOnce()->willReturn($environment->reveal());
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldBeCalledOnce();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', '']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     */
    public function testItRegistersAnExternalEnvironmentWithCustomName(): void
    {
        $environmentDetails = ['custom-name', '/fake/directory', EnvironmentEntity::TYPE_CUSTOM];

        $processProxy = $this->prophesize(ProcessProxy::class);
        $installer = $this->prophesize(ConfigurationInstaller::class);
        $environment = $this->prophesize(EnvironmentEntity::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('/fake/directory');
        $installer->install(...$environmentDetails)->shouldBeCalledOnce()->willReturn($environment->reveal());
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldBeCalledOnce();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'custom-name']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     */
    public function testItAbortsTheRegistrationAfterDisapproval(): void
    {
        $processProxy = $this->prophesize(ProcessProxy::class);
        $installer = $this->prophesize(ConfigurationInstaller::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $processProxy->getWorkingDirectory()->shouldNotBeCalled();
        $installer->install(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldNotBeCalled();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @throws FilesystemException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $exception = new InvalidEnvironmentException('Unable to determine the current working directory.');

        $processProxy = $this->prophesize(ProcessProxy::class);
        $installer = $this->prophesize(ConfigurationInstaller::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $processProxy->getWorkingDirectory()->shouldBeCalled()->willThrow($exception);
        $installer->install(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldNotBeCalled();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', '']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to determine the current working directory.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
