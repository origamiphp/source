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
use App\Tests\Command\AbstractCommandWebTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
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
final class RegisterCommandTest extends AbstractCommandWebTestCase
{
    /** @var ObjectProphecy */
    private $processProxy;

    /** @var ObjectProphecy */
    private $installer;

    /** @var ObjectProphecy */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->processProxy = $this->prophet->prophesize(ProcessProxy::class);
        $this->installer = $this->prophet->prophesize(ConfigurationInstaller::class);
        $this->eventDispatcher = $this->prophet->prophesize(EventDispatcher::class);
    }

    public function testItRegistersAnExternalEnvironmentWithDefaultName(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('/fake/directory')
        ;

        (new MethodProphecy($this->installer, 'install', ['directory', '/fake/directory', EnvironmentEntity::TYPE_CUSTOM, null]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes', '']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItRegistersAnExternalEnvironmentWithCustomName(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn('/fake/directory')
        ;

        (new MethodProphecy($this->installer, 'install', ['custom-name', '/fake/directory', EnvironmentEntity::TYPE_CUSTOM, null]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes', 'custom-name']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsTheRegistrationAfterDisapproval(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->installer, 'install', []))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalled()
            ->willThrow(new InvalidEnvironmentException('Unable to determine the current working directory.'))
        ;

        (new MethodProphecy($this->installer, 'install', []))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes', '']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to determine the current working directory.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Additional\RegisterCommand instance to use within the tests.
     */
    private function getCommand(): RegisterCommand
    {
        return new RegisterCommand(
            $this->processProxy->reveal(),
            $this->installer->reveal(),
            $this->eventDispatcher->reveal(),
        );
    }
}
