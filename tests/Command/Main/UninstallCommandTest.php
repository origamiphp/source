<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\UninstallCommand;
use App\Helper\CommandExitCode;
use App\Helper\CurrentContext;
use App\Middleware\Binary\DockerCompose;
use App\Middleware\Configuration\ConfigurationUninstaller;
use App\Tests\Command\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
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
final class UninstallCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    /** @var ObjectProphecy */
    private $currentContext;

    /** @var ObjectProphecy */
    private $dockerCompose;

    /** @var ObjectProphecy */
    private $uninstaller;

    /** @var ObjectProphecy */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->currentContext = $this->prophet->prophesize(CurrentContext::class);
        $this->dockerCompose = $this->prophet->prophesize(DockerCompose::class);
        $this->uninstaller = $this->prophet->prophesize(ConfigurationUninstaller::class);
        $this->eventDispatcher = $this->prophet->prophesize(EventDispatcher::class);
    }

    public function testItUninstallsTheCurrentEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->setActive(false);

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'removeServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldBeCalledOnce()
        ;

        (new MethodProphecy($this->uninstaller, 'uninstall', [$environment]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully uninstalled.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDoesNotUninstallARunningEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();
        $environment->setActive(true);

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'removeServices', []))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->uninstaller, 'uninstall', [$environment]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to uninstall a running environment.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        (new MethodProphecy($this->currentContext, 'getEnvironment', [Argument::type(InputInterface::class)]))
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        (new MethodProphecy($this->dockerCompose, 'removeServices', []))
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::any()]))
            ->shouldNotBeCalled()
        ;

        (new MethodProphecy($this->uninstaller, 'uninstall', [$environment]))
            ->shouldNotBeCalled()
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while removing the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Contextual\UninstallCommand instance to use within the tests.
     */
    private function getCommand(): UninstallCommand
    {
        return new UninstallCommand(
            $this->currentContext->reveal(),
            $this->dockerCompose->reveal(),
            $this->uninstaller->reveal(),
            $this->eventDispatcher->reveal()
        );
    }
}
