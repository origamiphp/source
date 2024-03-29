<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\InstallCommand;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\FilesystemException;
use App\Service\Setup\ConfigurationFiles;
use App\Service\Setup\EnvironmentBuilder;
use App\Service\Wrapper\OrigamiStyle;
use App\Tests\TestCommandTrait;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\EnvironmentEntity;
use App\ValueObject\PrepareAnswers;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\InstallCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class InstallCommandTest extends TestCase
{
    use ProphecyTrait;
    use TestCommandTrait;
    use TestEnvironmentTrait;

    public function testItInstallTheRequestedEnvironment(): void
    {
        $environmentBuilder = $this->prophesize(EnvironmentBuilder::class);
        $configurationFiles = $this->prophesize(ConfigurationFiles::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $answers = new PrepareAnswers('name', 'location', 'type', []);
        $environment = new EnvironmentEntity('name', 'location', 'type');

        $environmentBuilder
            ->prepare(Argument::type(OrigamiStyle::class))
            ->shouldBeCalledOnce()
            ->willReturn($answers)
        ;

        $configurationFiles
            ->install($environment, $answers->getSettings())
            ->shouldBeCalledOnce()
        ;

        $eventDispatcher
            ->dispatch(Argument::type(EnvironmentInstalledEvent::class))
            ->shouldBeCalledOnce()
        ;

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
        $environmentBuilder = $this->prophesize(EnvironmentBuilder::class);
        $configurationFiles = $this->prophesize(ConfigurationFiles::class);
        $eventDispatcher = $this->prophesize(EventDispatcher::class);

        $environmentBuilder
            ->prepare(Argument::type(OrigamiStyle::class))
            ->shouldBeCalledOnce()
            ->willThrow(FilesystemException::class)
        ;

        $command = new InstallCommand($environmentBuilder->reveal(), $configurationFiles->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['', EnvironmentEntity::TYPE_SYMFONY, 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
