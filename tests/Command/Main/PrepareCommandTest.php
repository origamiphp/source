<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\PrepareCommand;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\PrepareCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class PrepareCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    public function testItPreparesTheActiveEnvironment(): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->prepareServices()->shouldBeCalledOnce()->willReturn(true);

        $command = new PrepareCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertStringContainsString('[OK] Docker services successfully prepared.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $environment = $this->getFakeEnvironment();

        $this->systemManager->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->prepareServices()->shouldBeCalledOnce()->willReturn(false);

        $command = new PrepareCommand(
            $this->systemManager->reveal(),
            $this->validator->reveal(),
            $this->dockerCompose->reveal(),
            $this->eventDispatcher->reveal(),
            $this->processProxy->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] An error occurred while preparing the Docker services.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
