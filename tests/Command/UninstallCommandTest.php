<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UninstallCommand;
use App\Environment\Configuration\ConfigurationUninstaller;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Middleware\Binary\Docker;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestLocationTrait;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\UninstallCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class UninstallCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestLocationTrait;

    public function testItUninstallsTheCurrentEnvironment(): void
    {
        $environment = $this->createEnvironment();
        $environment->deactivate();
        $this->installEnvironmentConfiguration($environment);

        [$currentContext, $docker, $uninstaller, $eventDispatcher] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $docker->removeServices()->shouldBeCalledOnce()->willReturn(true);
        $eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();
        $uninstaller->uninstall($environment)->shouldBeCalledOnce();

        $command = new UninstallCommand($currentContext->reveal(), $docker->reveal(), $uninstaller->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItDisplaysWarningWithError(): void
    {
        $environment = $this->createEnvironment();
        $environment->deactivate();
        $this->installEnvironmentConfiguration($environment);

        [$currentContext, $docker, $uninstaller, $eventDispatcher] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $docker->removeServices()->shouldBeCalledOnce()->willReturn(false);
        $eventDispatcher->dispatch(Argument::any())->shouldBeCalledOnce();
        $uninstaller->uninstall($environment)->shouldBeCalledOnce();

        $command = new UninstallCommand($currentContext->reveal(), $docker->reveal(), $uninstaller->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[WARNING] ', $display);
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        [$currentContext, $docker, $uninstaller, $eventDispatcher] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->willThrow(InvalidEnvironmentException::class);
        $currentContext->getActiveEnvironment()->shouldNotBeCalled();

        $command = new UninstallCommand($currentContext->reveal(), $docker->reveal(), $uninstaller->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(CurrentContext::class),
            $this->prophesize(Docker::class),
            $this->prophesize(ConfigurationUninstaller::class),
            $this->prophesize(EventDispatcher::class),
        ];
    }
}
