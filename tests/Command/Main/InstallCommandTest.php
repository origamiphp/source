<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Environment\Configuration\ConfigurationInstaller;
use App\Environment\EnvironmentEntity;
use App\Environment\EnvironmentMaker;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\FilesystemException;
use App\Helper\ProcessProxy;
use App\Tests\Command\TestCommandTrait;
use Generator;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Main\InstallCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class InstallCommandTest extends WebTestCase
{
    use ProphecyTrait;
    use TestCommandTrait;

    /**
     * @dataProvider provideEnvironmentConfigurations
     */
    public function testItInstallTheRequestedEnvironment(string $name, string $type, string $phpVersion, ?string $domains): void
    {
        $fakeLocation = '/fake/directory';

        [$processProxy, $configurator, $installer, $eventDispatcher] = $this->prophesizeInstallCommandArguments();

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn($fakeLocation);
        $configurator->askEnvironmentName(Argument::type(SymfonyStyle::class), basename($fakeLocation))->shouldBeCalledOnce()->willReturn($name);
        $configurator->askEnvironmentType(Argument::type(SymfonyStyle::class), $fakeLocation)->shouldBeCalledOnce()->willReturn($type);
        $configurator->askPhpVersion(Argument::type(SymfonyStyle::class), $type)->shouldBeCalledOnce()->willReturn($phpVersion);
        $configurator->askDomains(Argument::type(SymfonyStyle::class), $type)->shouldBeCalledOnce()->willReturn($domains ?? null);
        $installer->install($name, $fakeLocation, $type, $phpVersion, $domains)->shouldBeCalledOnce()->willReturn($this->prophesize(EnvironmentEntity::class)->reveal());
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldBeCalledOnce();

        $command = new InstallCommand($processProxy->reveal(), $configurator->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        if ($domains) {
            $commandTester->setInputs([$name, $type, $phpVersion, 'yes', $domains]);
        } else {
            $commandTester->setInputs([$name, $type, $phpVersion, 'no']);
        }
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] ', $display);
        static::assertStringContainsString('[INFO] ', $display);
        static::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function provideEnvironmentConfigurations(): Generator
    {
        yield 'Magento environment with domain' => ['fake-magento', EnvironmentEntity::TYPE_MAGENTO2, 'latest', 'magento.localhost'];
        yield 'Magento environment without domain' => ['fake-magento', EnvironmentEntity::TYPE_MAGENTO2, 'latest', null];

        yield 'Sylius environment with domain' => ['fake-sylius', EnvironmentEntity::TYPE_SYLIUS, 'latest', 'sylius.localhost'];
        yield 'Sylius environment without domain' => ['fake-sylius', EnvironmentEntity::TYPE_SYLIUS, 'latest', null];

        yield 'Symfony environment with domain' => ['fake-symfony', EnvironmentEntity::TYPE_SYMFONY, 'latest', 'symfony.localhost'];
        yield 'Symfony environment without domain' => ['fake-symfony', EnvironmentEntity::TYPE_SYMFONY, 'latest', null];
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        [$processProxy, $configurator, $installer, $eventDispatcher] = $this->prophesizeInstallCommandArguments();

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willThrow(FilesystemException::class);

        $command = new InstallCommand($processProxy->reveal(), $configurator->reveal(), $installer->reveal(), $eventDispatcher->reveal());
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['', EnvironmentEntity::TYPE_SYMFONY, 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] ', $display);
        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Prophesizes arguments needed by the \App\Command\Main\InstallCommand class.
     */
    private function prophesizeInstallCommandArguments(): array
    {
        return [
            $this->prophesize(ProcessProxy::class),
            $this->prophesize(EnvironmentMaker::class),
            $this->prophesize(ConfigurationInstaller::class),
            $this->prophesize(EventDispatcher::class),
        ];
    }
}
