<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\UpdateCommand;
use App\Environment\Configuration\ConfigurationUpdater;
use App\Environment\EnvironmentMaker;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CurrentContext;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestLocationTrait;
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
    use TestLocationTrait;

    private const DEFAULT_PHP_VERSION = '8.0';
    private const DEFAULT_DATABASE_VERSION = '10.5';
    private const DEFAULT_DOMAINS = 'origami.localhost';

    public function testItSuccessfullyUpdatesTheCurrentEnvironment(): void
    {
        $environment = $this->createEnvironment();
        [$currentContext, $environmentMaker, $updater] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->shouldBeCalledOnce();
        $currentContext->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $environmentMaker->askPhpVersion(Argument::type(SymfonyStyle::class))->shouldBeCalledOnce()->willReturn(self::DEFAULT_PHP_VERSION);
        $environmentMaker->askDatabaseVersion(Argument::type(SymfonyStyle::class))->shouldBeCalledOnce()->willReturn(self::DEFAULT_DATABASE_VERSION);
        $environmentMaker->askDomains(Argument::type(SymfonyStyle::class), $environment->getName())->shouldBeCalledOnce()->willReturn(self::DEFAULT_DOMAINS);
        $updater->update($environment, self::DEFAULT_PHP_VERSION, self::DEFAULT_DATABASE_VERSION, self::DEFAULT_DOMAINS)->shouldBeCalledOnce();

        $command = new UpdateCommand($currentContext->reveal(), $environmentMaker->reveal(), $updater->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsGracefullyTheUpdate(): void
    {
        [$currentContext, $environmentMaker, $updater] = $this->prophesizeObjectArguments();

        $currentContext->loadEnvironment(Argument::type(InputInterface::class))->willThrow(InvalidEnvironmentException::class);
        $currentContext->getActiveEnvironment()->shouldNotBeCalled();

        $command = new UpdateCommand($currentContext->reveal(), $environmentMaker->reveal(), $updater->reveal());
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
            $this->prophesize(EnvironmentMaker::class),
            $this->prophesize(ConfigurationUpdater::class),
        ];
    }
}
