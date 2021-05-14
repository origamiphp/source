<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UpdateCommand;
use App\Helper\CurrentContext;
use App\Service\ConfigurationFiles;
use App\Service\EnvironmentBuilder;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\PrepareAnswers;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\UpdateCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class UpdateCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItSuccessfullyUpdatesTheCurrentEnvironment(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $environmentBuilder, $configurationFiles] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $answers = new PrepareAnswers($environment->getName(), $environment->getLocation(), $environment->getType(), null, []);
        $environmentBuilder->prepare(Argument::type(SymfonyStyle::class), $environment)->shouldBeCalledOnce()->willReturn($answers);
        $configurationFiles->install($environment, $answers->getSettings())->shouldBeCalledOnce();

        $command = new UpdateCommand($currentContext->reveal(), $environmentBuilder->reveal(), $configurationFiles->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsGracefullyTheUpdate(): void
    {
        $environment = $this->createEnvironment();
        $environment->activate();
        [$currentContext, $environmentBuilder, $configurationFiles] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);

        $command = new UpdateCommand($currentContext->reveal(), $environmentBuilder->reveal(), $configurationFiles->reveal());
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
            $this->prophesize(EnvironmentBuilder::class),
            $this->prophesize(ConfigurationFiles::class),
        ];
    }
}
