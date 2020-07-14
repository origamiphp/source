<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegisterCommand;
use App\Environment\Configuration\ConfigurationInstaller;
use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Tests\Command\TestCommandTrait;
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

    public function testItRegistersAnExternalEnvironmentWithDefaultName(): void
    {
        $environmentDetails = ['directory-with-default-name', '/fake/directory-with-default-name', EnvironmentEntity::TYPE_CUSTOM];

        $environment = $this->prophesize(EnvironmentEntity::class);
        [$processProxy, $installer, $eventDispatcher] = $this->prophesizeRegisterCommandArguments();

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('/fake/directory-with-default-name');
        $installer->install(...$environmentDetails)->shouldBeCalledOnce()->willReturn($environment->reveal());
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldBeCalledOnce();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', '']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItRegistersAnExternalEnvironmentWithCustomName(): void
    {
        $environmentDetails = ['custom-name', '/fake/directory-with-custom-name', EnvironmentEntity::TYPE_CUSTOM];

        $environment = $this->prophesize(EnvironmentEntity::class);
        [$processProxy, $installer, $eventDispatcher] = $this->prophesizeRegisterCommandArguments();

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('/fake/directory-with-custom-name');
        $installer->install(...$environmentDetails)->shouldBeCalledOnce()->willReturn($environment->reveal());
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldBeCalledOnce();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', 'custom-name']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsTheRegistrationAfterDisapproval(): void
    {
        [$processProxy, $installer, $eventDispatcher] = $this->prophesizeRegisterCommandArguments();

        $processProxy->getWorkingDirectory()->shouldNotBeCalled();
        $installer->install(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldNotBeCalled();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('[OK] ', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        [$processProxy, $installer, $eventDispatcher] = $this->prophesizeRegisterCommandArguments();

        $processProxy->getWorkingDirectory()->shouldBeCalled()->willThrow(InvalidEnvironmentException::class);
        $installer->install(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldNotBeCalled();

        $command = new RegisterCommand($processProxy->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes', '']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Prophesizes arguments needed by the \App\Command\Additional\RegisterCommand class.
     */
    private function prophesizeRegisterCommandArguments(): array
    {
        return [
            $this->prophesize(ProcessProxy::class),
            $this->prophesize(ConfigurationInstaller::class),
            $this->prophesize(EventDispatcher::class),
        ];
    }
}
