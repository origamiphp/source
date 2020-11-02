<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\InstallCommand;
use App\Environment\Configuration\ConfigurationInstaller;
use App\Environment\EnvironmentEntity;
use App\Environment\EnvironmentMaker;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\FilesystemException;
use App\Helper\ProcessProxy;
use App\Tests\CustomProphecyTrait;
use App\Tests\TestCommandTrait;
use Generator;
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
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class InstallCommandTest extends WebTestCase
{
    use CustomProphecyTrait;
    use TestCommandTrait;

    /**
     * @dataProvider provideEnvironmentConfigurations
     */
    public function testItInstallTheRequestedEnvironment(string $name, string $type, string $phpVersion, string $databaseVersion, ?string $domains): void
    {
        $fakeLocation = '/fake/directory';

        [$processProxy, $environmentMaker, $installer, $eventDispatcher] = $this->prophesizeObjectArguments();

        $processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn($fakeLocation);
        $environmentMaker->askEnvironmentName(Argument::type(SymfonyStyle::class), basename($fakeLocation))->shouldBeCalledOnce()->willReturn($name);
        $environmentMaker->askEnvironmentType(Argument::type(SymfonyStyle::class), $fakeLocation)->shouldBeCalledOnce()->willReturn($type);
        $environmentMaker->askPhpVersion(Argument::type(SymfonyStyle::class), $type)->shouldBeCalledOnce()->willReturn($phpVersion);
        $environmentMaker->askDatabaseVersion(Argument::type(SymfonyStyle::class))->shouldBeCalledOnce()->willReturn($databaseVersion);
        $environmentMaker->askDomains(Argument::type(SymfonyStyle::class), $name)->shouldBeCalledOnce()->willReturn($domains ?? null);

        $installer->install($fakeLocation, $name, $type, $phpVersion, $databaseVersion, $domains)->shouldBeCalledOnce()->willReturn($this->prophesize(EnvironmentEntity::class)->reveal());
        $eventDispatcher->dispatch(Argument::type(EnvironmentInstalledEvent::class))->shouldBeCalledOnce();

        $command = new InstallCommand($processProxy->reveal(), $environmentMaker->reveal(), $installer->reveal(), $eventDispatcher->reveal());
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
        $technologies = [
            EnvironmentEntity::TYPE_DRUPAL,
            EnvironmentEntity::TYPE_MAGENTO2,
            EnvironmentEntity::TYPE_SYLIUS,
            EnvironmentEntity::TYPE_SYMFONY,
        ];

        foreach ($technologies as $technology) {
            yield "{$technology} environment with domain" => [
                "fake-{$technology}",
                $technology,
                'latest',
                'latest',
                "{$technology}.localhost",
            ];

            yield "{$technology} environment without domain" => [
                "fake-{$technology}",
                $technology,
                'latest',
                'latest',
                null,
            ];
        }
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        [$processProxy, $configurator, $installer, $eventDispatcher] = $this->prophesizeObjectArguments();

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
     * {@inheritdoc}
     */
    protected function prophesizeObjectArguments(): array
    {
        return [
            $this->prophesize(ProcessProxy::class),
            $this->prophesize(EnvironmentMaker::class),
            $this->prophesize(ConfigurationInstaller::class),
            $this->prophesize(EventDispatcher::class),
        ];
    }
}
