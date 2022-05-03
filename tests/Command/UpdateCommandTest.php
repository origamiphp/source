<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UpdateCommand;
use App\Service\ApplicationContext;
use App\Service\Setup\ConfigurationFiles;
use App\Service\Setup\EnvironmentBuilder;
use App\Service\Wrapper\OrigamiStyle;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\PrepareAnswers;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\UpdateCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class UpdateCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItSuccessfullyUpdatesTheCurrentEnvironment(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $environmentBuilder = $this->prophesize(EnvironmentBuilder::class);
        $configurationFiles = $this->prophesize(ConfigurationFiles::class);

        $environment = $this->createEnvironment();
        $answers = new PrepareAnswers($environment->getName(), $environment->getLocation(), $environment->getType(), []);

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $environmentBuilder
            ->prepare(Argument::type(OrigamiStyle::class), $environment)
            ->shouldBeCalledOnce()
            ->willReturn($answers)
        ;

        $configurationFiles
            ->install($environment, $answers->getSettings())
            ->shouldBeCalledOnce()
        ;

        $command = new UpdateCommand($applicationContext->reveal(), $environmentBuilder->reveal(), $configurationFiles->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsGracefullyTheUpdate(): void
    {
        $applicationContext = $this->prophesize(ApplicationContext::class);
        $environmentBuilder = $this->prophesize(EnvironmentBuilder::class);
        $configurationFiles = $this->prophesize(ConfigurationFiles::class);

        $environment = $this->createEnvironment();
        $environment->activate();

        $applicationContext
            ->loadEnvironment(Argument::type(InputInterface::class))
            ->shouldBeCalledOnce()
        ;

        $applicationContext
            ->getActiveEnvironment()
            ->shouldBeCalledOnce()
            ->willReturn($environment)
        ;

        $command = new UpdateCommand($applicationContext->reveal(), $environmentBuilder->reveal(), $configurationFiles->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
