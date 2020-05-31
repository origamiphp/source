<?php

declare(strict_types=1);

namespace App\Tests\Command\Main;

use App\Command\Main\InstallCommand;
use App\Environment\Configuration\ConfigurationInstaller;
use App\Environment\EnvironmentEntity;
use App\Environment\EnvironmentMaker;
use App\Event\EnvironmentInstalledEvent;
use App\Exception\FilesystemException;
use App\Helper\CommandExitCode;
use App\Helper\ProcessProxy;
use App\Tests\Command\AbstractCommandWebTestCase;
use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
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
final class InstallCommandTest extends AbstractCommandWebTestCase
{
    /** @var ObjectProphecy */
    private $processProxy;

    /** @var ObjectProphecy */
    private $configurator;

    /** @var ObjectProphecy */
    private $installer;

    /** @var ObjectProphecy */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->processProxy = $this->prophet->prophesize(ProcessProxy::class);
        $this->configurator = $this->prophet->prophesize(EnvironmentMaker::class);
        $this->installer = $this->prophet->prophesize(ConfigurationInstaller::class);
        $this->eventDispatcher = $this->prophet->prophesize(EventDispatcher::class);
    }

    /**
     * @dataProvider provideEnvironmentConfigurations
     */
    public function testItInstallTheRequestedEnvironment(string $name, string $type, string $phpVersion, ?string $domains): void
    {
        $fakeLocation = '/fake/directory';

        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willReturn($fakeLocation)
        ;

        (new MethodProphecy($this->configurator, 'askEnvironmentName', [Argument::type(SymfonyStyle::class), basename($fakeLocation)]))
            ->shouldBeCalledOnce()
            ->willReturn($name)
        ;

        (new MethodProphecy($this->configurator, 'askEnvironmentType', [Argument::type(SymfonyStyle::class), $fakeLocation]))
            ->shouldBeCalledOnce()
            ->willReturn($type)
        ;

        (new MethodProphecy($this->configurator, 'askPhpVersion', [Argument::type(SymfonyStyle::class), $type]))
            ->shouldBeCalledOnce()
            ->willReturn($phpVersion)
        ;

        (new MethodProphecy($this->configurator, 'askDomains', [Argument::type(SymfonyStyle::class), $type]))
            ->shouldBeCalledOnce()
            ->willReturn($domains ?? null)
        ;

        (new MethodProphecy($this->installer, 'install', [$name, $fakeLocation, $type, $phpVersion, $domains]))
            ->shouldBeCalledOnce()
            ->willReturn($this->prophet->prophesize(EnvironmentEntity::class)->reveal())
        ;

        (new MethodProphecy($this->eventDispatcher, 'dispatch', [Argument::type(EnvironmentInstalledEvent::class)]))
            ->shouldBeCalledOnce()
        ;

        $commandTester = new CommandTester($this->getCommand());
        if ($domains) {
            $commandTester->setInputs([$name, $type, $phpVersion, 'yes', $domains]);
        } else {
            $commandTester->setInputs([$name, $type, $phpVersion, 'no']);
        }
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully installed.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function provideEnvironmentConfigurations(): Generator
    {
        yield ['fake-magento', EnvironmentEntity::TYPE_MAGENTO2, 'latest', 'magento.localhost'];
        yield ['fake-magento', EnvironmentEntity::TYPE_MAGENTO2, 'latest', null];

        yield ['fake-symfony', EnvironmentEntity::TYPE_SYMFONY, 'latest', 'symfony.localhost'];
        yield ['fake-symfony', EnvironmentEntity::TYPE_SYMFONY, 'latest', null];
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        (new MethodProphecy($this->processProxy, 'getWorkingDirectory', []))
            ->shouldBeCalledOnce()
            ->willThrow(new FilesystemException('Dummy exception.'))
        ;

        $commandTester = new CommandTester($this->getCommand());
        $commandTester->setInputs(['', EnvironmentEntity::TYPE_SYMFONY, 'no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    /**
     * Retrieves the \App\Command\Additional\InstallCommand instance to use within the tests.
     */
    private function getCommand(): InstallCommand
    {
        return new InstallCommand(
            $this->processProxy->reveal(),
            $this->configurator->reveal(),
            $this->installer->reveal(),
            $this->eventDispatcher->reveal()
        );
    }
}
