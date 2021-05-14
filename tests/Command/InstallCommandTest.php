<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\InstallCommand;
use App\Environment\EnvironmentEntity;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\FilesystemException;
use App\Service\ConfigurationFiles;
use App\Service\EnvironmentBuilder;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\PrepareAnswers;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\InstallCommand
 *
 * @uses \App\Environment\EnvironmentFactory
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class InstallCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItInstallTheRequestedEnvironment(): void
    {
        [$environmentBuilder, $configurationFiles, $eventDispatcher] = $this->prophesizeObjectArguments();

        $answers = new PrepareAnswers('name', 'location', 'type', null, []);
        $environment = new EnvironmentEntity('name', 'location', 'type', null);

        $environmentBuilder->prepare(Argument::type(SymfonyStyle::class))->shouldBeCalledOnce()->willReturn($answers);
        $configurationFiles->install($environment, $answers->getSettings())->shouldBeCalledOnce();
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldBeCalledOnce();

        $command = new InstallCommand($environmentBuilder->reveal(), $configurationFiles->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertStringContainsString('[INFO] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        [$environmentBuilder, $configurationFiles, $eventDispatcher] = $this->prophesizeObjectArguments();

        $environmentBuilder->prepare(Argument::type(SymfonyStyle::class))->shouldBeCalledOnce()->willThrow(FilesystemException::class);

        $command = new InstallCommand($environmentBuilder->reveal(), $configurationFiles->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['', EnvironmentEntity::TYPE_SYMFONY, 'no']);
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
            $this->prophesize(EnvironmentBuilder::class),
            $this->prophesize(ConfigurationFiles::class),
            $this->prophesize(EventDispatcher::class),
        ];
    }
}
